<?php
/**
 * Class LP_Course_DB
 *
 * @author tungnx
 * @since 3.2.7.5
 */

defined( 'ABSPATH' ) || exit();

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

		$query = $this->wpdb->prepare(
			"
			SELECT section_course_id
			FROM {$this->tb_lp_sections} AS s
			INNER JOIN {$this->tb_lp_section_items} AS si
			ON si.section_id = s.section_id
			WHERE si.item_id = %d
			ORDER BY section_course_id DESC",
			$item_id
		);

		return (int) $this->wpdb->get_var( $query );
	}

	/**
	 * Get user_item_id by order_id, course_id, user_id
	 *
	 * @param int $order_id
	 * @param int $course_id
	 * @param int $user_id
	 *
	 * @return int
	 */
	public function get_user_item_id( $order_id = 0, $course_id = 0, $user_id = 0 ) {
		$query = $this->wpdb->prepare(
			"
			SELECT user_item_id
			FROM {$this->tb_lp_user_items}
			WHERE ref_type = %s
			AND ref_id = %d
			AND item_type = %s
			AND item_id = %d
			AND user_id = %d
			",
			LP_ORDER_CPT,
			$order_id,
			LP_COURSE_CPT,
			$course_id,
			$user_id
		);

		return $this->wpdb->get_var( $query );
	}

	/**
	 * Get first item of course
	 *
	 * @param int $course_id .
	 *
	 * @return int
	 */
	public function get_first_item_id( $course_id = 0 ): int {
		/**
		 * Get cache
		 *
		 * Please clear cache when change first item of course
		 */
		$first_item_id = wp_cache_get( 'first_item_id', LP_COURSE_CPT );

		if ( ! $first_item_id ) {
			$query = $this->wpdb->prepare(
				"
			SELECT item_id FROM $this->tb_lp_section_items AS items
			INNER JOIN $this->tb_lp_sections AS sections
			ON items.section_id = sections.section_id
			AND sections.section_course_id = %d
			",
				$course_id,0
			);
			$first_item_id = (int) $this->wpdb->get_var( $query );

			// Set cache
			wp_cache_set( 'first_item_id', $first_item_id, LP_COURSE_CPT );
		}

		return $first_item_id;
	}

	/**
	 * Get popular courses.
	 *
	 * @param LP_Course_Filter $filter
	 *
	 * @return array
	 * @author tungnx
	 * @version 1.0.0
	 */
	public function get_popular_courses( LP_Course_Filter $filter ): array {
		$query = apply_filters(
			'learn-press/course-curd/query-popular-courses',
			$this->wpdb->prepare(
				"
					SELECT DISTINCT(item_id), COUNT(item_id) as total
					FROM $this->tb_lp_user_items
					WHERE item_type = %s
					AND status = %s
					OR status = %s
					GROUP BY item_id
					ORDER BY total DESC
					LIMIT %d
				",
				LP_COURSE_CPT,
				LP_COURSE_ENROLLED,
				LP_COURSE_FINISHED,
				$filter->limit
			)
		);

		return $this->wpdb->get_col( $query );
	}
}

LP_Course_DB::getInstance();

