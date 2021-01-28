<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_User_Items_DB
 *
 * @since 3.2.8.6
 */
class LP_User_Items_DB extends LP_Database {
	private static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get items by user_item_id | this is id where item_id = course_id
	 *
	 * @param int $user_item_id_by_course_id
	 * @param int $user_id
	 *
	 * @return object
	 */
	public function get_course_items_by_user_item_id( $user_item_id_by_course_id ) {
		if ( empty( $user_item_id_by_course_id ) ) {
			return null;
		}

		/**
		 * Get cache
		 *
		 * Please clear cache when user action vs item. Ex: completed lesson, quiz. Start quiz...
		 */
		$course_items = wp_cache_get( 'lp-course-items-' . $user_item_id_by_course_id,
			'lp-user-course-items' );

		if ( ! $course_items ) {
			$query = $this->wpdb->prepare( "
			SELECT * FROM $this->tb_lp_user_items
			WHERE parent_id = %d
			AND ref_type = %s
			GROUP BY user_item_id ASC;
		", $user_item_id_by_course_id, LP_COURSE_CPT );

			$course_items = $this->wpdb->get_results( $query );

			//Set cache
			wp_cache_set( 'lp-course-items-' . $user_item_id_by_course_id, $course_items,
				'lp-user-course-items' );
		}

		return $course_items;
	}
}

LP_Course_DB::getInstance();

