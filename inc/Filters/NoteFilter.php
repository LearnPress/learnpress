<?php

namespace LearnPress\Filters;

defined( 'ABSPATH' ) || exit;

/**
 * Filter query for the learnpress_notes table.
 *
 * @since 4.4.9.2
 */
class NoteFilter extends FilterBase {
	const COL_NOTE_ID        = 'note_id';
	const COL_USER_ID        = 'user_id';
	const COL_COURSE_ID      = 'course_id';
	const COL_ITEM_ID        = 'item_id';
	const COL_ITEM_TYPE      = 'item_type';
	const COL_NOTE_TYPE      = 'note_type';
	const COL_CONTENT        = 'content';
	const COL_HIGHLIGHT_TEXT = 'highlight_text';
	const COL_ANCHOR         = 'anchor';
	const COL_CREATED_AT     = 'created_at';
	const COL_UPDATED_AT     = 'updated_at';

	/**
	 * @var string[]
	 */
	public array $all_fields = array(
		self::COL_NOTE_ID,
		self::COL_USER_ID,
		self::COL_COURSE_ID,
		self::COL_ITEM_ID,
		self::COL_ITEM_TYPE,
		self::COL_NOTE_TYPE,
		self::COL_CONTENT,
		self::COL_HIGHLIGHT_TEXT,
		self::COL_ANCHOR,
		self::COL_CREATED_AT,
		self::COL_UPDATED_AT,
	);

	/**
	 * @var int
	 */
	public $note_id;

	/**
	 * @var int[]
	 */
	public $note_ids = array();

	/**
	 * @var int
	 */
	public $user_id;

	/**
	 * @var int
	 */
	public $course_id;

	/**
	 * Restrict to these courses, e.g. courses of an instructor.
	 * An empty array means no restriction; use `[0]` to match nothing.
	 *
	 * @var int[]
	 */
	public $course_ids = array();

	/**
	 * @var int
	 */
	public $item_id;

	/**
	 * @var string
	 */
	public $item_type;

	/**
	 * @var string
	 */
	public $note_type;

	/**
	 * @var string
	 */
	public $field_count = self::COL_NOTE_ID;
}
