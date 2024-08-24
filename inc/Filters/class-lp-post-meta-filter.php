<?php
/**
 * Class LP_Post_Meta_Filter
 *
 * Filter post type of LP
 *
 * @author  tungnx
 * @package LearnPress/Classes/Filters
 * @version 1.0.0
 * @since 4.2.6.9
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

class LP_Post_Meta_Filter extends LP_Filter {
	const COL_META_ID = 'meta_id';
	const COL_POST_ID = 'post_id';
	const COL_META_VALUE = 'meta_value';
	const COL_META_KEY = 'meta_key';
	/**
	 * @var string[]
	 */
	public $all_fields = [
		self::COL_META_ID,
		self::COL_POST_ID,
		self::COL_META_VALUE,
		self::COL_META_KEY
	];

	/**
	 * @var int
	 */
	public $meta_id;
	/**
	 * @var int
	 */
	public $post_id;
	/**
	 * @var string
	 */
	public $meta_value;
	/**
	 * @var string
	 */
	public $meta_key;
}


