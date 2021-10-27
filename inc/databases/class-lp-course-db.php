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
	 * Get first item id of course
	 *
	 * @param int $course_id .
	 *
	 * @return int
	 * @throws Exception
	 * @since 4.0.0
	 * @version 1.0.2
	 * @modify 4.1.3
	 * @author tungnx
	 */
	public function get_first_item_id( int $course_id = 0 ): int {
		// Get cache
		$lp_course_cache = LP_Course_Cache::instance();
		$key_cache       = "$course_id/first_item_id";
		$first_item_id   = $lp_course_cache->get_cache( $key_cache );

		if ( ! $first_item_id ) {
			$query = $this->wpdb->prepare(
				"
				SELECT item_id FROM $this->tb_lp_section_items AS items
				INNER JOIN $this->tb_lp_sections AS sections
				ON items.section_id = sections.section_id
				AND sections.section_course_id = %d
				ORDER BY items.item_order ASC, sections.section_order ASC
				LIMIT %d
				",
				$course_id,
				1
			);

			$first_item_id = (int) $this->wpdb->get_var( $query );

			$this->check_execute_has_error();

			// Set cache
			$lp_course_cache->set_cache( $key_cache, $first_item_id );
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
		$offset    = ( absint( $filter->page ) - 1 ) * $filter->limit;
		$sql_limit = $this->wpdb->prepare( 'LIMIT %d, %d', $offset, $filter->limit );

		$query = apply_filters(
			'learn-press/course-curd/query-popular-courses',
			$this->wpdb->prepare(
				"SELECT DISTINCT(item_id), COUNT(item_id) as total
					FROM $this->tb_lp_user_items
					WHERE item_type = %s
					AND ( status = %s OR status = %s OR status = %s )
					GROUP BY item_id
					ORDER BY total DESC
					{$sql_limit}
				",
				LP_COURSE_CPT,
				LP_COURSE_ENROLLED,
				LP_COURSE_FINISHED,
				LP_COURSE_PURCHASED
			)
		);

		return $this->wpdb->get_col( $query );
	}

	public function get_recent_courses( LP_Course_Filter $filter ) : array {
		global $wpdb;

		$limit = $filter->limit ?? - 1;
		$order = ! empty( $filter->order ) ? $filter->order : 'DESC';

		if ( $limit <= 0 ) {
			$limit = 0;
		}

		$query = apply_filters(
			'learnpress/databases/widgets/recent_courses',
			$wpdb->prepare(
				"SELECT DISTINCT p.ID
					FROM $wpdb->posts AS p
					WHERE p.post_type = %s
					AND p.post_status = %s
					ORDER BY p.post_date {$order}
					LIMIT %d",
				LP_COURSE_CPT,
				'publish',
				$limit
			)
		);

		return $wpdb->get_col( $query );
	}

	public function get_featured_courses( LP_Course_Filter $filter ) : array {
		global $wpdb;

		$limit    = ! empty( $filter->limit ) ? $filter->limit : -1;
		$order_by = ! empty( $filter->order_by ) ? $filter->order_by : 'post_date';
		$order    = ! empty( $filter->order ) ? $filter->order : 'DESC';

		if ( $limit <= 0 ) {
			$limit = 0;
		}

		$query = apply_filters(
			'learnpress/databases/widgets/featured_courses',
			$wpdb->prepare(
				"SELECT DISTINCT p.ID
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} as pmeta ON p.ID=pmeta.post_id AND pmeta.meta_key = %s
				WHERE p.post_type = %s
					AND p.post_status = %s
					AND pmeta.meta_value = %s
				ORDER BY p.{$order_by} {$order}
				LIMIT %d",
				'_lp_featured',
				LP_COURSE_CPT,
				'publish',
				'yes',
				$limit
			)
		);

		return $wpdb->get_col( $query );
	}

	public function get_courses_on_sale( $order = 'ASC' ) {
		$args = array(
			'post_type'      => LP_COURSE_CPT,
			'orderby'        => 'meta_value_num',
			'order'          => $order,
			'meta_key'       => '_lp_sale_price',
			'posts_per_page' => -1,
		);

		$courses = get_posts( $args );

		$output = array();

		if ( ! empty( $courses ) ) {
			foreach ( (array) $courses as $course_object ) {
				$course_id = $course_object->ID;

				$course = learn_press_get_course( $course_object->ID );

				if ( ! $course || empty( $course_id ) ) {
					continue;
				}

				if ( $course->has_sale_price() ) {
					$output[] = $course_id;
				}
			}
		}

		return $output;
	}

	/**
	 * Get list user ids enrolled by course
	 *
	 * @return array|object|null
	 * @throws Exception
	 * @version 1.0.0
	 * @author tungnx
	 * @since 4.1.3.1
	 */
	public function get_user_ids_enrolled( int $course_id ) {
		$query = $this->wpdb->prepare(
			"
				SELECT DISTINCT user_id FROM {$this->tb_lp_user_items}
				WHERE item_id = %d
				AND item_type = %s
				AND (status = %s OR status = %s )
			",
			$course_id,
			LP_COURSE_CPT,
			'enrolled',
			'finished'
		);

		$result = $this->wpdb->get_results( $query, OBJECT_K );

		$this->check_execute_has_error();

		return $result;
	}

	/**
	 * Count total user enrolled by course
	 *
	 * @param int $course_id
	 *
	 * @return int
	 * @version 1.0.0
	 * @author tungnx
	 * @since 4.1.4
	 */
	public function get_total_user_enrolled( int $course_id ): int {
		$query = $this->wpdb->prepare(
			"
				SELECT COUNT(DISTINCT user_id) AS total FROM {$this->tb_lp_user_items}
				WHERE item_id = %d
				AND item_type = %s
				AND (status = %s OR status = %s )
			",
			$course_id,
			LP_COURSE_CPT,
			LP_COURSE_ENROLLED,
			LP_COURSE_FINISHED
		);

		return (int) $this->wpdb->get_var( $query );
	}

	/**
	 * Count total user enrolled or purchase by course
	 *
	 * @param int $course_id
	 *
	 * @return int
	 * @version 1.0.0
	 * @author tungnx
	 * @since 4.1.4
	 */
	public function get_total_user_enrolled_or_purchased( int $course_id ): int {
		$query = $this->wpdb->prepare(
			"
				SELECT COUNT(DISTINCT user_id) AS total FROM {$this->tb_lp_user_items}
				WHERE item_id = %d
				AND item_type = %s
				AND (status = %s OR status = %s OR status = %s )
			",
			$course_id,
			LP_COURSE_CPT,
			LP_COURSE_ENROLLED,
			LP_COURSE_FINISHED,
			LP_COURSE_PURCHASED
		);

		return (int) $this->wpdb->get_var( $query );
	}

	/**
	 * Count total time enrolled by course
	 *
	 * @param int $course_id
	 *
	 * @return int
	 * @version 1.0.0
	 * @author tungnx
	 * @since 4.1.3.1
	 */
	public function get_total_time_enrolled_course( int $course_id ): int {
		$query = $this->wpdb->prepare(
			"
				SELECT COUNT(user_item_id) AS total FROM {$this->tb_lp_user_items}
				WHERE item_id = %d
				AND item_type = %s
				AND (status = %s OR status = %s OR status = %s )
			",
			$course_id,
			LP_COURSE_CPT,
			LP_COURSE_ENROLLED
		);

		return (int) $this->wpdb->get_var( $query );
	}
}

LP_Course_DB::getInstance();

