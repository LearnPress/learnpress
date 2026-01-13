<?php
namespace LearnPress\Filters\Course;

use Edu_Press\Init\init;
use LearnPress\Filters\FilterBase;

defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Course_JSON_Filter
 *
 * Move from LP_Course_JSON_Filter to here
 * Query table learnpress_courses
 *
 * @version 4.3.2.3
 * @version 1.0.0
 */
class CourseJsonFilter extends FilterBase {
	const COL_ID            = 'ID';
	const COL_POST_AUTHOR   = 'post_author';
	const COL_POST_DATE_GMT = 'post_date_gmt';
	const COL_POST_CONTENT  = 'post_content';
	const COL_POST_TITLE    = 'post_title';
	const COL_POST_STATUS   = 'post_status';
	const COL_POST_NAME     = 'post_name';
	const COL_MENU_ORDER    = 'menu_order';
	const COL_JSON          = 'json';
	const COL_PRICE_TO_SORT = 'price_to_sort';
	const COL_IS_SALE       = 'is_sale';
	const COL_LANG          = 'lang'; // For multiple languages, wpml or polylang will store here.
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

	public int $ID;
	public string $post_title;
	public string $post_name;
	public int $post_author;
	public string $lang;
	public int $is_sale; // 0, 1

	/***** Fields not is columns in DB *****/
	public array $post_status  = [];
	public array $post_authors = [];
	public string $taxonomy    = 'course_category';
	public array $term_ids     = [];
	public array $tag_ids      = [];
	public array $ids          = [];
	/**
	 * @var string[] free, paid
	 */
	public array $type_price = [];
	public int $is_feature; // 0, 1
	public array $levels;
	public string $type; // offline, online
	/***** End fields not is columns in DB *****/
}
