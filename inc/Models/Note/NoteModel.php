<?php

namespace LearnPress\Models\Note;

use Exception;
use LearnPress\Databases\NoteDB;
use LearnPress\Filters\NoteFilter;

defined( 'ABSPATH' ) || exit;

/**
 * Student note on a course item (lesson).
 *
 * A note is either a plain "text" note of the item, or a "highlight" note
 * attached to a text selection inside the item content (located by `anchor`).
 *
 * @since 4.4.9.2
 */
class NoteModel {
	const TYPE_TEXT      = 'text';
	const TYPE_HIGHLIGHT = 'highlight';

	const SCOPE_LESSON_CONTENT = 'lesson_content';

	const CONTENT_MAX_LENGTH   = 5000;
	const HIGHLIGHT_MAX_LENGTH = 5000;
	const CONTEXT_MAX_LENGTH   = 64;

	/**
	 * @var int
	 */
	private $note_id = 0;

	/**
	 * @var int
	 */
	public $user_id = 0;

	/**
	 * @var int
	 */
	public $course_id = 0;

	/**
	 * @var int
	 */
	public $item_id = 0;

	/**
	 * @var string
	 */
	public $item_type = LP_LESSON_CPT;

	/**
	 * @var string
	 */
	public $note_type = self::TYPE_TEXT;

	/**
	 * Plain text note content.
	 *
	 * @var string
	 */
	public $content = '';

	/**
	 * Snapshot of the highlighted text (plain text, from anchor quote).
	 *
	 * @var string
	 */
	public $highlight_text = '';

	/**
	 * Location of the highlight in the item content.
	 * [ 'scope' => string, 'quote' => [ 'exact', 'prefix', 'suffix' ], 'position' => [ 'start', 'end' ] ]
	 *
	 * @var array
	 */
	public $anchor = array();

	/**
	 * @var string
	 */
	public $created_at = '';

	/**
	 * @var string|null
	 */
	public $updated_at;

	/**
	 * Initialize model from a DB row or payload.
	 *
	 * @param array|object|null $data Note data.
	 */
	public function __construct( $data = null ) {
		if ( $data ) {
			$this->map_to_object( $data );
		}
	}

	/**
	 * Map data onto the model.
	 *
	 * @param array|object $data Note data.
	 *
	 * @return self
	 */
	public function map_to_object( $data ): self {
		foreach ( $data as $key => $value ) {
			if ( ! property_exists( $this, $key ) ) {
				continue;
			}

			if ( 'anchor' === $key ) {
				$value = $this->decode_anchor( $value );
			} elseif ( in_array( $key, array( 'note_id', 'user_id', 'course_id', 'item_id' ), true ) ) {
				$value = absint( $value );
			} elseif ( in_array( $key, array( 'content', 'highlight_text' ), true ) ) {
				$value = (string) $value;
			}

			$this->{$key} = $value;
		}

		return $this;
	}

	/**
	 * Get note ID.
	 *
	 * @return int
	 */
	public function get_note_id(): int {
		return $this->note_id;
	}

	/**
	 * Creation time as a Unix timestamp (created_at is stored in UTC).
	 *
	 * @return int 0 when unknown.
	 */
	public function get_created_timestamp(): int {
		$timestamp = strtotime( $this->created_at . ' UTC' );

		return $timestamp ? $timestamp : 0;
	}

	/**
	 * Check the note belongs to a user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 */
	public function is_owner( int $user_id ): bool {
		return $user_id > 0 && $this->user_id === $user_id;
	}

	/**
	 * Find a note by ID.
	 *
	 * @param int $note_id Note ID.
	 *
	 * @return static|false
	 * @throws Exception
	 */
	public static function find( int $note_id ) {
		if ( $note_id <= 0 ) {
			return false;
		}

		$row = NoteDB::getInstance()->get_note( $note_id );

		return $row ? new static( $row ) : false;
	}

	/**
	 * Query note models.
	 *
	 * @param NoteFilter $filter     Note query filter.
	 * @param int        $total_rows Total matching rows.
	 *
	 * @return static[]
	 * @throws Exception
	 */
	public static function query( NoteFilter $filter, int &$total_rows = 0 ): array {
		$rows = NoteDB::getInstance()->get_notes( $filter, $total_rows );

		return is_array( $rows ) ? array_map( static fn( $row ) => new static( $row ), $rows ) : array();
	}

	/**
	 * Get all notes of a user on an item of a course, newest first.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 * @param int $item_id   Item ID.
	 *
	 * @return static[]
	 * @throws Exception
	 */
	public static function get_user_item_notes( int $user_id, int $course_id, int $item_id ): array {
		$filter                  = new NoteFilter();
		$filter->user_id         = $user_id;
		$filter->course_id       = $course_id;
		$filter->item_id         = $item_id;
		$filter->limit           = -1;
		$filter->run_query_count = false;
		NoteDB::getInstance()->order_newest_first( $filter );

		return static::query( $filter );
	}

	/**
	 * Save the note.
	 *
	 * @return self
	 * @throws Exception
	 */
	public function save(): self {
		$this->validate();

		$db  = NoteDB::getInstance();
		$now = current_time( 'mysql', true );

		if ( empty( $this->created_at ) ) {
			$this->created_at = $now;
		}

		if ( $this->note_id > 0 ) {
			$this->updated_at = $now;
		}

		$data = array(
			'note_id'        => $this->note_id,
			'user_id'        => $this->user_id,
			'course_id'      => $this->course_id,
			'item_id'        => $this->item_id,
			'item_type'      => $this->item_type,
			'note_type'      => $this->note_type,
			'content'        => $this->content,
			'highlight_text' => self::TYPE_HIGHLIGHT === $this->note_type ? $this->highlight_text : null,
			'anchor'         => self::TYPE_HIGHLIGHT === $this->note_type ? wp_json_encode( $this->anchor ) : null,
			'created_at'     => $this->created_at,
			'updated_at'     => $this->updated_at,
		);

		if ( $this->note_id > 0 ) {
			$db->update_note( $data );
		} else {
			$this->note_id = $db->insert_note( $data );
			if ( $this->note_id <= 0 ) {
				throw new Exception( __( 'Could not save the note.', 'learnpress' ) );
			}
		}

		do_action( 'learn-press/note/saved', $this );

		return $this;
	}

	/**
	 * Delete the note.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function delete(): bool {
		if ( $this->note_id <= 0 ) {
			return false;
		}

		$deleted = NoteDB::getInstance()->delete_notes( array( $this->note_id ) ) > 0;
		if ( $deleted ) {
			do_action( 'learn-press/note/deleted', $this );
		}

		return $deleted;
	}

	/**
	 * Data for frontend/admin responses.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'note_id'        => $this->note_id,
			'user_id'        => $this->user_id,
			'course_id'      => $this->course_id,
			'item_id'        => $this->item_id,
			'item_type'      => $this->item_type,
			'note_type'      => $this->note_type,
			'content'        => $this->content,
			'highlight_text' => $this->highlight_text,
			'anchor'         => $this->anchor,
			'created_at'     => $this->created_at,
			'updated_at'     => $this->updated_at,
		);
	}

	/**
	 * Item post types that support notes.
	 *
	 * @return string[]
	 */
	public static function get_supported_item_types(): array {
		return (array) apply_filters( 'learn-press/note/item-types', array( LP_LESSON_CPT ) );
	}

	/**
	 * Validate and normalize model values.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function validate(): void {
		$this->user_id   = absint( $this->user_id );
		$this->course_id = absint( $this->course_id );
		$this->item_id   = absint( $this->item_id );
		$this->item_type = sanitize_key( $this->item_type );
		$this->note_type = sanitize_key( $this->note_type );
		$this->content   = self::sanitize_plain_text( (string) $this->content );

		if ( ! $this->user_id || ! $this->course_id || ! $this->item_id ) {
			throw new Exception( __( 'Invalid note data.', 'learnpress' ) );
		}

		if ( ! in_array( $this->item_type, self::get_supported_item_types(), true ) ) {
			throw new Exception( __( 'Notes are not supported for this item.', 'learnpress' ) );
		}

		if ( ! in_array( $this->note_type, array( self::TYPE_TEXT, self::TYPE_HIGHLIGHT ), true ) ) {
			throw new Exception( __( 'Invalid note type.', 'learnpress' ) );
		}

		if ( self::str_length( $this->content ) > self::CONTENT_MAX_LENGTH ) {
			throw new Exception(
				sprintf(
					/* translators: %d: max characters */
					__( 'Note content must not exceed %d characters.', 'learnpress' ),
					self::CONTENT_MAX_LENGTH
				)
			);
		}

		if ( self::TYPE_TEXT === $this->note_type ) {
			if ( '' === $this->content ) {
				throw new Exception( __( 'Note content is required.', 'learnpress' ) );
			}

			$this->highlight_text = '';
			$this->anchor         = array();

			return;
		}

		$this->anchor = $this->sanitize_anchor( $this->anchor );
		if ( empty( $this->anchor ) ) {
			throw new Exception( __( 'Invalid highlight position.', 'learnpress' ) );
		}

		// Always taken from the anchor so it can't differ from the highlighted text.
		$this->highlight_text = self::sanitize_plain_text( $this->anchor['quote']['exact'] );
		if ( '' === $this->highlight_text ) {
			throw new Exception( __( 'Highlighted text is required.', 'learnpress' ) );
		}

		$this->highlight_text = self::str_cut( $this->highlight_text, self::HIGHLIGHT_MAX_LENGTH );
	}

	/**
	 * Sanitize the anchor structure.
	 * Quote strings are matched against text content in JS (never rendered as HTML),
	 * so only invalid UTF-8 is removed to keep them identical to the source text.
	 *
	 * @param mixed $anchor Raw anchor (array or JSON string).
	 *
	 * @return array Empty array when invalid.
	 */
	public function sanitize_anchor( $anchor ): array {
		if ( is_string( $anchor ) ) {
			$anchor = json_decode( $anchor, true );
		}

		if ( ! is_array( $anchor ) ) {
			return array();
		}

		$scope = sanitize_key( $anchor['scope'] ?? '' );
		if ( self::SCOPE_LESSON_CONTENT !== $scope ) {
			return array();
		}

		$quote = isset( $anchor['quote'] ) && is_array( $anchor['quote'] ) ? $anchor['quote'] : array();
		$exact = self::clean_string( $quote['exact'] ?? '' );
		if ( '' === trim( $exact ) || self::str_length( $exact ) > self::HIGHLIGHT_MAX_LENGTH ) {
			return array();
		}

		$position = isset( $anchor['position'] ) && is_array( $anchor['position'] ) ? $anchor['position'] : array();
		$start    = absint( $position['start'] ?? 0 );
		$end      = absint( $position['end'] ?? 0 );
		if ( $end <= $start ) {
			$end = $start + self::str_length( $exact );
		}

		return array(
			'scope'    => $scope,
			'quote'    => array(
				'exact'  => $exact,
				'prefix' => self::str_cut( self::clean_string( $quote['prefix'] ?? '' ), self::CONTEXT_MAX_LENGTH, true ),
				'suffix' => self::str_cut( self::clean_string( $quote['suffix'] ?? '' ), self::CONTEXT_MAX_LENGTH ),
			),
			'position' => array(
				'start' => $start,
				'end'   => $end,
			),
		);
	}

	/**
	 * Sanitize plain text: strip HTML tags but keep a lone "<" (e.g. "a < b"),
	 * which sanitize_textarea_field() turns into "&lt;".
	 * "&lt;" is restored only when it cannot start a tag (not followed by a letter, "/", "!" or "?"),
	 * so decoding never creates markup (e.g. a typed "&lt;img ...>" stays encoded).
	 * The value is plain text: always escape it on output.
	 *
	 * @param string $value Raw text.
	 *
	 * @return string
	 */
	public static function sanitize_plain_text( string $value ): string {
		return trim( preg_replace( '/&lt;(?![a-zA-Z\/!?])/', '<', sanitize_textarea_field( $value ) ) );
	}

	/**
	 * Decode anchor stored as JSON.
	 *
	 * @param mixed $anchor Raw anchor.
	 *
	 * @return array
	 */
	protected function decode_anchor( $anchor ): array {
		if ( is_string( $anchor ) && '' !== $anchor ) {
			$anchor = json_decode( $anchor, true );
		}

		return is_array( $anchor ) ? $anchor : array();
	}

	/**
	 * Cast to string and drop invalid UTF-8.
	 *
	 * @param mixed $value Value.
	 *
	 * @return string
	 */
	protected static function clean_string( $value ): string {
		return is_scalar( $value ) ? wp_check_invalid_utf8( (string) $value, true ) : '';
	}

	/**
	 * Multibyte safe string length.
	 *
	 * @param string $value Value.
	 *
	 * @return int
	 */
	protected static function str_length( string $value ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}

	/**
	 * Multibyte safe cut.
	 *
	 * @param string $value     Value.
	 * @param int    $length    Max length.
	 * @param bool   $from_end  Keep the end of the string instead of the start.
	 *
	 * @return string
	 */
	protected static function str_cut( string $value, int $length, bool $from_end = false ): string {
		if ( self::str_length( $value ) <= $length ) {
			return $value;
		}

		if ( function_exists( 'mb_substr' ) ) {
			return $from_end ? mb_substr( $value, -$length ) : mb_substr( $value, 0, $length );
		}

		return $from_end ? substr( $value, -$length ) : substr( $value, 0, $length );
	}
}
