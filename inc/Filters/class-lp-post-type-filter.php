<?php
/**
 * Class LP_Post_Type_Filter
 *
 * Filter post type of LP
 *
 * @author  tungnx
 * @package LearnPress/Classes/Filters
 * @version 1.0.1
 * @since 4.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

class LP_Post_Type_Filter extends LP_Filter {
	const COL_ID = 'ID';
	const COL_POST_AUTHOR = 'post_author';
	const COL_POST_DATE = 'post_date';
	const COL_POST_DATE_GMT = 'post_date_gmt';
	const COL_POST_CONTENT = 'post_content';
	const COL_POST_TITLE = 'post_title';
	const COL_POST_EXCERPT = 'post_excerpt';
	const COL_POST_STATUS = 'post_status';
	const COL_COMMENT_STATUS = 'comment_status';
	const COL_PING_STATUS = 'ping_status';
	const COL_POST_PASSWORD = 'post_password';
	const COL_POST_NAME = 'post_name';
	const COL_TO_PING = 'to_ping';
	const COL_PINGED = 'pinged';
	const COL_POST_MODIFIED = 'post_modified';
	const COL_POST_MODIFIED_GMT = 'post_modified_gmt';
	const COL_POST_CONTENT_FILTERED = 'post_content_filtered';
	const COL_POST_PARENT = 'post_parent';
	const COL_GUID = 'guid';
	const COL_MENU_ORDER = 'menu_order';
	const COL_POST_TYPE = 'post_type';
	const COL_POST_MIME_TYPE = 'post_mime_type';
	const COL_COMMENT_COUNT = 'comment_count';
	/**
	 * @var string[]
	 */
	public $all_fields = [
		self::COL_ID,
		self::COL_POST_AUTHOR,
		self::COL_POST_DATE,
		self::COL_POST_DATE_GMT,
		self::COL_POST_CONTENT,
		self::COL_POST_TITLE,
		self::COL_POST_EXCERPT,
		self::COL_POST_STATUS,
		self::COL_COMMENT_STATUS,
		self::COL_PING_STATUS,
		self::COL_POST_PASSWORD,
		self::COL_POST_NAME,
		self::COL_TO_PING,
		self::COL_PINGED,
		self::COL_POST_MODIFIED,
		self::COL_POST_MODIFIED_GMT,
		self::COL_POST_CONTENT_FILTERED,
		self::COL_POST_PARENT,
		self::COL_GUID,
		self::COL_MENU_ORDER,
		self::COL_POST_TYPE,
		self::COL_POST_MIME_TYPE,
		self::COL_COMMENT_COUNT,
	];
	/**
	 * @var int
	 */
	public $ID;
	/**
	 * @var string
	 */
	public $post_type = 'post';
	/**
	 * @var string
	 */
	public $post_title = '';
	/**
	 * @var string
	 */
	public $post_name = '';
	/**
	 * @var string[]
	 */
	public $post_status = [];
	/**
	 * @var int
	 */
	public $post_author;
	/**
	 * @var int[]
	 */
	public $post_authors = array();
	/**
	 * @var array
	 */
	public $term_ids = array();
	/**
	 * @var array
	 */
	public $tag_ids = array();
	/**
	 * @var array
	 */
	public $post_ids = array();
	/**
	 * @var string
	 */
	public $taxonomy = 'category';

	public function __construct() {

	}
}


