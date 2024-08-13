<?php
/**
 * Class LP_Course_JSON_Filter
 *
 * @author  ThimPress
 * @package LearnPress/Classes/Filters
 * @version 4.2.6.9
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

class LP_Course_JSON_Filter extends LP_Filter {
	const COL_ID = 'ID';
	const COL_POST_AUTHOR = 'post_author';
	const COL_POST_DATE_GMT = 'post_date_gmt';
	const COL_POST_CONTENT = 'post_content';
	const COL_POST_TITLE = 'post_title';
	const COL_POST_STATUS = 'post_status';
	const COL_POST_NAME = 'post_name';
	const COL_MENU_ORDER = 'menu_order';
	const COL_JSON = 'json';
	const COL_PRICE_TO_SORT = 'price_to_sort';
	const COL_IS_SALE = 'is_sale';
	const COL_LANG = 'lang'; // For multiple languages, wpml or polylang will store here.
	/**
	 * @var string[]
	 */
	public $all_fields = [
		self::COL_ID,
		self::COL_POST_AUTHOR,
		self::COL_POST_DATE_GMT,
		self::COL_POST_CONTENT,
		self::COL_POST_TITLE,
		self::COL_POST_STATUS,
		self::COL_POST_NAME,
		self::COL_MENU_ORDER,
		self::COL_JSON,
		self::COL_PRICE_TO_SORT,
		self::COL_IS_SALE,
		self::COL_LANG,
	];
	/**
	 * @var int
	 */
	public $ID;
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
	public $post_author = 0;
	/**
	 * @var int[]
	 */
	public $post_authors = [];
	/**
	 * @var array
	 */
	public $term_ids = [];
	/**
	 * @var array
	 */
	public $tag_ids = [];
	/**
	 * @var array
	 */
	public $ids = [];
	/**
	 * @var string
	 */
	public $taxonomy = 'course_category';
	/**
	 * @var string
	 */
	public $lang = '';
	/**
	 * @var int
	 */
	public $is_sale = 0;
}
