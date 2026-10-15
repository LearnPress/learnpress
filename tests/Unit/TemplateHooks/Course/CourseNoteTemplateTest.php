<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Course;

use Brain\Monkey\Functions;
use LearnPress\TemplateHooks\Course\CourseNoteTemplate;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use ReflectionClass;

class CourseNoteTemplateTest extends BrainMonkeyTestCase {
	private function template( $state ): CourseNoteTemplate {
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'htmlentities2' )->returnArg();
		Functions\when( 'lp_skeleton_animation_html' )->alias( static function () { echo 'Loading'; } );
		$reflection = new ReflectionClass( CourseNoteLayoutStub::class );
		$template   = $reflection->newInstanceWithoutConstructor();
		$reflection->getProperty( 'render_state_resolved' )->setValue( $template, true );
		$reflection->getProperty( 'render_state' )->setValue( $template, $state );
		return $template;
	}

	public function test_notes_item_is_omitted_when_render_access_is_denied(): void {
		$items = array( 'ai-assistant' => array( 'label' => 'AI Assistant' ) );
		$this->assertSame( $items, $this->template( false )->register_learning_bar_item( $items ) );
	}

	public function test_course_notes_ajax_callback_is_registered_without_removing_existing_callbacks(): void {
		$callbacks = $this->template( false )->register_ajax_callback( array( 'Existing:render' ) );
		$this->assertSame( array( 'Existing:render', CourseNoteTemplate::class . ':render_course_notes' ), $callbacks );
	}

	public function test_course_notes_ajax_rejects_guests(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'wp_kses_post' )->returnArg();
		$response = CourseNoteTemplate::render_course_notes( array( 'course_id' => 10, 'owner_id' => 5 ) );
		$this->assertStringContainsString( 'learn-press-message error', $response->content );
		$this->assertStringContainsString( 'Notes are unavailable.', $response->content );
	}

	public function test_editable_notes_use_shared_bar_and_persistent_selection_button(): void {
		$state    = array( 'mode' => CourseNoteTemplate::MODE_EDIT, 'owner_id' => 1, 'course_id' => 10 );
		$template = $this->template( $state );
		$items    = $template->register_learning_bar_item( array() );
		$html     = $items['notes']['html'];

		$this->assertStringContainsString( '<template id="lp-notes-template">', $html );
		$this->assertStringContainsString( 'lp-learning-bar-item-head', $html );
		$this->assertStringContainsString( 'lp-learning-bar-item-content', $html );
		$this->assertStringContainsString( 'lp-notes__form', $html );
		$this->assertStringContainsString( 'lp-notes__card-edit', $html );
		$this->assertSame( 1, substr_count( $html, 'id="lp-notes-content"' ) );
		$this->assertStringContainsString( 'aria-label="Note content"', $html );
		$this->assertStringContainsString( 'id="lp-notes-input"', $html );
		$this->assertStringContainsString( 'learn-press-form', $html );
		$this->assertStringContainsString( 'learn-press-message info', $html );
		$this->assertStringContainsString( 'class="lp-button lp-notes__add"', $html );
		$this->assertStringNotContainsString( 'lp-notes__panel', $html );
		$this->assertStringNotContainsString( 'lp-notes__selection-btn', $html );
		$this->assertStringContainsString( 'lp-notes__selection-btn', $template->html_widget( $state ) );
		$this->assertStringContainsString( '<a href="#" class="lp-notes__view-all" aria-haspopup="dialog">View all</a>', $html );
		$this->assertStringContainsString( 'lp-load-ajax-element', $template->html_widget( $state ) );
		$this->assertStringContainsString( 'render_course_notes', $template->html_widget( $state ) );
	}

	public function test_course_notes_group_interleaved_notes_by_lesson_and_escape_content(): void {
		$this->template( false );
		$note = array( 'note_id' => 1, 'item_id' => 20, 'item_title' => 'Lesson A', 'created_at_display' => '2026-10-12', 'note_type' => 'text', 'highlight_text' => '', 'content' => '<script>alert(1)</script>' );
		$other = array_merge( $note, array( 'item_id' => 21, 'item_title' => 'Lesson B', 'note_type' => 'highlight', 'highlight_text' => '<b>Quote</b>' ) );
		$html = CourseNoteTemplate::html_course_note_groups( array( $note, $other, $note ) );
		$this->assertSame( 2, substr_count( $html, '<details ' ) );
		$this->assertSame( 3, substr_count( $html, '<article ' ) );
		$this->assertSame( 1, substr_count( $html, 'Lesson A' ) );
		$this->assertSame( 1, substr_count( $html, 'Lesson B' ) );
		$this->assertStringContainsString( 'open><summary', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
		$this->assertStringContainsString( '&lt;b&gt;Quote&lt;/b&gt;', $html );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertTrue( strpos( $html, 'Lesson B' ) > strrpos( $html, 'is-text' ) );
	}

	public function test_manage_mode_has_edit_delete_without_creating_student_notes(): void {
		$template = $this->template( array( 'mode' => CourseNoteTemplate::MODE_MANAGE, 'owner_id' => 0, 'course_id' => 10 ) );
		$html = $template->register_learning_bar_item( array() )['notes']['html'];
		$this->assertStringContainsString( 'lp-notes__form', $html );
		$this->assertStringContainsString( 'lp-notes__card-edit', $html );
		$this->assertStringContainsString( 'lp-notes__card-delete', $html );
		$this->assertStringNotContainsString( 'lp-notes__add', $html );
		$this->assertStringNotContainsString( 'lp-notes__selection-btn', $template->html_widget( array( 'mode' => CourseNoteTemplate::MODE_MANAGE, 'owner_id' => 0, 'course_id' => 10 ) ) );
	}

	public function test_manage_mode_with_create_permission_has_add_and_selection_buttons(): void {
		$state    = array( 'mode' => CourseNoteTemplate::MODE_MANAGE, 'can_create' => true, 'owner_id' => 0, 'course_id' => 10 );
		$template = $this->template( $state );
		$html     = $template->register_learning_bar_item( array() )['notes']['html'];
		$this->assertStringContainsString( 'class="lp-button lp-notes__add"', $html );
		$this->assertStringContainsString( 'lp-notes__card-edit', $html );
		$this->assertStringContainsString( 'lp-notes__card-delete', $html );
		$this->assertStringContainsString( 'lp-notes__selection-btn', $template->html_widget( $state ) );
	}

	public function test_course_popup_actions_follow_manage_permission(): void {
		$this->template( false );
		$note = array( 'note_id' => 1, 'item_id' => 20, 'item_title' => 'Lesson', 'created_at_display' => '', 'note_type' => 'text', 'content' => 'Note', 'can_manage' => false );
		$this->assertStringNotContainsString( 'lp-notes__card-edit', CourseNoteTemplate::html_course_note_groups( array( $note ) ) );
		$note['can_manage'] = true;
		$note['student_name'] = '<script>Student A</script>';
		$html = CourseNoteTemplate::html_course_note_groups( array( $note ) );
		$this->assertStringContainsString( 'lp-notes__card-edit', $html );
		$this->assertStringContainsString( 'lp-notes__card-delete', $html );
		$this->assertStringNotContainsString( 'Student A', $html );
		$this->assertStringNotContainsString( 'lp-notes__card-student', $html );
		$this->assertStringNotContainsString( 'lp-notes__card-student', $this->template( false )->html_card_template( true ) );
		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_readonly_notes_have_no_edit_or_selection_controls(): void {
		$state    = array( 'mode' => CourseNoteTemplate::MODE_READONLY, 'owner_id' => 1, 'course_id' => 10 );
		$template = $this->template( $state );
		$html = $template->register_learning_bar_item( array() )['notes']['html'];

		$this->assertStringContainsString( 'lp-notes__readonly', $html );
		$this->assertStringContainsString( 'lp-notes__list', $html );
		$this->assertStringNotContainsString( 'lp-notes__form', $html );
		$this->assertStringNotContainsString( 'lp-notes__card-edit', $html );
		$this->assertStringNotContainsString( 'lp-notes__card-delete', $html );
		$this->assertStringNotContainsString( 'lp-notes__selection-btn', $template->html_widget( $state ) );
	}
}

class CourseNoteLayoutStub extends CourseNoteTemplate {
	public function html_readonly_notice( int $owner_id ): string {
		return '<div class="lp-notes__readonly">Read only</div>';
	}
}
