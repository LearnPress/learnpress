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

	public function test_editable_notes_use_shared_bar_and_persistent_selection_button(): void {
		$state    = array( 'mode' => CourseNoteTemplate::MODE_EDIT, 'owner_id' => 1 );
		$template = $this->template( $state );
		$items    = $template->register_learning_bar_item( array() );
		$html     = $items['notes']['html'];

		$this->assertStringContainsString( '<template id="lp-notes-template">', $html );
		$this->assertStringContainsString( 'lp-learning-bar-item-head', $html );
		$this->assertStringContainsString( 'lp-learning-bar-item-content', $html );
		$this->assertStringContainsString( 'lp-notes__form', $html );
		$this->assertStringContainsString( 'lp-notes__card-edit', $html );
		$this->assertSame( 1, substr_count( $html, 'id="lp-notes-content"' ) );
		$this->assertStringContainsString( 'for="lp-notes-input"', $html );
		$this->assertStringContainsString( 'id="lp-notes-input"', $html );
		$this->assertStringContainsString( 'learn-press-form', $html );
		$this->assertStringContainsString( 'learn-press-message info', $html );
		$this->assertStringContainsString( 'class="lp-button lp-notes__add"', $html );
		$this->assertStringNotContainsString( 'lp-notes__panel', $html );
		$this->assertStringNotContainsString( 'lp-notes__selection-btn', $html );
		$this->assertStringContainsString( 'lp-notes__selection-btn', $template->html_widget( $state ) );
	}

	public function test_readonly_notes_have_no_edit_or_selection_controls(): void {
		$state    = array( 'mode' => CourseNoteTemplate::MODE_READONLY, 'owner_id' => 1 );
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
