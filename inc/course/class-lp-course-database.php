<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Lesson_DB
 *
 * @since 3.2.7.5
 */
class LP_Course_DB extends LP_Database {
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
	 * Get course_id of item
	 *
	 * item type lp_lesson, lp_quiz
	 *
	 * @param int $item_id
	 *
	 * @return int
	 */
	public function learn_press_get_item_course( $item_id = 0 ) {

		$query = $this->wpdb->prepare( "
			SELECT section_course_id
			FROM {$this->tb_lp_sections} AS s
			INNER JOIN {$this->tb_lp_section_items} AS si 
			ON si.section_id = s.section_id
			WHERE si.item_id = %d
			ORDER BY section_course_id DESC",
			$item_id );

		return (int) $this->wpdb->get_var( $query );
	}

	/**
	 * Get first item of course
	 *
	 * @param int $course_id
	 *
	 * @return int
	 */
	public function get_first_item( $course_id = 0 ) {
		/**
		 * Get cache
		 */
		$first_item_id = wp_cache_get( 'first_item_id', LP_COURSE_CPT );

		if ( ! $first_item_id ) {
			$query = $this->wpdb->prepare( "
			SELECT item_id FROM $this->tb_lp_section_items AS items
			INNER JOIN $this->tb_lp_sections AS sections
			ON items.section_id = sections.section_id
			AND sections.section_course_id = %d
			",
				$course_id
			);

			$first_item_id = (int) $this->wpdb->get_var( $query );

			//Set cache
			wp_cache_set( 'first_item_id', $first_item_id, LP_COURSE_CPT );
		}

		return $first_item_id;
	}

	/**
	 * Get items of course
	 *
	 * @param int $course_id
	 * @param bool $publish_only
	 */
	public function get_items_of_course( $course_id = 0, $publish_only = true ) {
		$where = '';

		/**
		 * Get course
		 *
		 * Please clear cache when add/delete item to course
		 */
		$course_items = wp_cache_get( 'lp-course-items-' . $course_id, 'lp-course-items' );

		if ( ! $course_items ) {
			if ( $publish_only ) {
				$where = $this->wpdb->prepare( "
					AND c.post_status = %s 
					AND it.post_status = %s
				", 'publish', 'publish' );
			}

			$query = $this->wpdb->prepare( "
				SELECT item_id id, it.post_type `type`, si.section_id
				FROM {$this->tb_lp_section_items} si 
				INNER JOIN {$this->tb_lp_sections} s ON si.section_id = s.section_id
				INNER JOIN {$this->tb_posts} c ON s.section_course_id = c.ID
				INNER JOIN {$this->tb_posts} it ON it.ID = si.item_id
				WHERE c.ID = %d
				{$where}
				ORDER BY s.section_order, si.item_order, si.section_item_id ASC
			", $course_id );

			$course_items = $this->wpdb->get_results( $query );

			//Set course
			wp_cache_set( 'lp-course-items-' . $course_id, $course_items, 'lp-course-items' );
		}

		return $course_items;
	}
}

LP_Course_DB::getInstance();

