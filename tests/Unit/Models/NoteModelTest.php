<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models;

use Brain\Monkey\Functions;
use Exception;
use LearnPress\Models\Note\NoteModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * Unit tests for NoteModel validation and anchor normalization.
 * No DB access: only validate()/sanitize_anchor()/map_to_object() are exercised.
 */
class NoteModelTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'absint' )->alias( static fn( $v ) => abs( (int) $v ) );
		Functions\when( 'sanitize_key' )->alias(
			static fn( $v ) => preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) )
		);
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( $v ) => strip_tags( (string) $v ) );
		Functions\when( 'wp_check_invalid_utf8' )->returnArg();
		Functions\when( 'apply_filters' )->alias( static fn( $hook, $value ) => $value );
	}

	private function make_note( array $data = [] ): NoteModel {
		return new NoteModel(
			array_merge(
				[
					'user_id'   => 5,
					'course_id' => 10,
					'item_id'   => 20,
					'item_type' => 'lp_lesson',
					'note_type' => NoteModel::TYPE_TEXT,
					'content'   => 'My note',
				],
				$data
			)
		);
	}

	private function valid_anchor(): array {
		return [
			'scope'    => NoteModel::SCOPE_LESSON_CONTENT,
			'quote'    => [
				'exact'  => 'LearnPress is free',
				'prefix' => 'Website. ',
				'suffix' => ' and always',
			],
			'position' => [
				'start' => 100,
				'end'   => 118,
			],
		];
	}

	// -------------------------------------------------------------------------
	// map_to_object
	// -------------------------------------------------------------------------

	public function test_map_from_db_row_casts_ids_and_decodes_anchor(): void {
		$note = new NoteModel(
			(object) [
				'note_id'        => '7',
				'user_id'        => '5',
				'course_id'      => '10',
				'item_id'        => '20',
				'note_type'      => 'highlight',
				'content'        => null,
				'highlight_text' => 'LearnPress is free',
				'anchor'         => json_encode( $this->valid_anchor() ),
			]
		);

		$this->assertSame( 7, $note->get_note_id() );
		$this->assertSame( 5, $note->user_id );
		$this->assertSame( '', $note->content );
		$this->assertSame( 'LearnPress is free', $note->anchor['quote']['exact'] );
		$this->assertTrue( $note->is_owner( 5 ) );
		$this->assertFalse( $note->is_owner( 6 ) );
		$this->assertFalse( $note->is_owner( 0 ) );
	}

	// -------------------------------------------------------------------------
	// validate: text notes
	// -------------------------------------------------------------------------

	public function test_valid_text_note_passes_and_drops_highlight_data(): void {
		$note = $this->make_note(
			[
				'highlight_text' => 'should be removed',
				'anchor'         => $this->valid_anchor(),
			]
		);

		$note->validate();

		$this->assertSame( 'My note', $note->content );
		$this->assertSame( '', $note->highlight_text );
		$this->assertSame( [], $note->anchor );
	}

	public function test_text_note_requires_content(): void {
		$this->expectException( Exception::class );
		$this->make_note( [ 'content' => '   ' ] )->validate();
	}

	public function test_content_is_plain_text(): void {
		$note = $this->make_note( [ 'content' => '<script>x</script>Hello <b>world</b>' ] );
		$note->validate();

		$this->assertSame( 'xHello world', $note->content );
	}

	public function test_content_over_max_length_is_rejected(): void {
		$this->expectException( Exception::class );
		$this->make_note( [ 'content' => str_repeat( 'a', NoteModel::CONTENT_MAX_LENGTH + 1 ) ] )->validate();
	}

	public function test_missing_ids_are_rejected(): void {
		$this->expectException( Exception::class );
		$this->make_note( [ 'course_id' => 0 ] )->validate();
	}

	public function test_unsupported_item_type_is_rejected(): void {
		$this->expectException( Exception::class );
		$this->make_note( [ 'item_type' => 'lp_quiz' ] )->validate();
	}

	public function test_invalid_note_type_is_rejected(): void {
		$this->expectException( Exception::class );
		$this->make_note( [ 'note_type' => 'bookmark' ] )->validate();
	}

	// -------------------------------------------------------------------------
	// validate: highlight notes
	// -------------------------------------------------------------------------

	public function test_highlight_note_without_content_is_valid(): void {
		$note = $this->make_note(
			[
				'note_type' => NoteModel::TYPE_HIGHLIGHT,
				'content'   => '',
				'anchor'    => $this->valid_anchor(),
			]
		);

		$note->validate();

		$this->assertSame( '', $note->content );
		// Falls back to the anchor quote when highlight_text is not sent.
		$this->assertSame( 'LearnPress is free', $note->highlight_text );
		$this->assertSame( 100, $note->anchor['position']['start'] );
	}

	public function test_highlight_note_requires_anchor(): void {
		$this->expectException( Exception::class );
		$this->make_note(
			[
				'note_type' => NoteModel::TYPE_HIGHLIGHT,
				'anchor'    => [],
			]
		)->validate();
	}

	// -------------------------------------------------------------------------
	// sanitize_anchor
	// -------------------------------------------------------------------------

	public function test_anchor_accepts_json_string(): void {
		$anchor = ( new NoteModel() )->sanitize_anchor( json_encode( $this->valid_anchor() ) );

		$this->assertSame( 'lesson_content', $anchor['scope'] );
		$this->assertSame( 'Website. ', $anchor['quote']['prefix'] );
	}

	public function test_anchor_with_unknown_scope_is_invalid(): void {
		$anchor          = $this->valid_anchor();
		$anchor['scope'] = 'question:12';

		$this->assertSame( [], ( new NoteModel() )->sanitize_anchor( $anchor ) );
	}

	public function test_anchor_with_empty_quote_is_invalid(): void {
		$anchor                   = $this->valid_anchor();
		$anchor['quote']['exact'] = '   ';

		$this->assertSame( [], ( new NoteModel() )->sanitize_anchor( $anchor ) );
	}

	public function test_anchor_keeps_quote_text_untouched(): void {
		$anchor                   = $this->valid_anchor();
		$anchor['quote']['exact'] = 'if a < b then <tag>';

		$result = ( new NoteModel() )->sanitize_anchor( $anchor );

		$this->assertSame( 'if a < b then <tag>', $result['quote']['exact'] );
	}

	public function test_anchor_trims_context_and_fixes_position(): void {
		$anchor                    = $this->valid_anchor();
		$anchor['quote']['prefix'] = str_repeat( 'p', 100 ) . 'END';
		$anchor['quote']['suffix'] = 'START' . str_repeat( 's', 100 );
		$anchor['position']        = [
			'start' => 50,
			'end'   => 10,
		];

		$result = ( new NoteModel() )->sanitize_anchor( $anchor );

		$this->assertSame( NoteModel::CONTEXT_MAX_LENGTH, mb_strlen( $result['quote']['prefix'] ) );
		$this->assertStringEndsWith( 'END', $result['quote']['prefix'] );
		$this->assertStringStartsWith( 'START', $result['quote']['suffix'] );
		$this->assertSame( 50 + mb_strlen( 'LearnPress is free' ), $result['position']['end'] );
	}
}
