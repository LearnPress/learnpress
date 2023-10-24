<?php
/**
 * Class LP_Course_CURD
 *
 * @author  ThimPress
 * @package LearnPress/Classes/CURD
 * @since   3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Course_CURD' ) ) {

	/**
	 * Class LP_Course_CURD
	 */
	class LP_Course_CURD extends LP_Object_Data_CURD implements LP_Interface_CURD {

		/**
		 * LP_Course_CURD constructor.
		 */
		public function __construct() {
			$this->_error_messages = array(
				'COURSE_NOT_EXISTS' => __( 'The course does not exist.', 'learnpress' ),
			);
		}

		/**
		 * Create course, with default meta.
		 *
		 * @param $args
		 *
		 * @return int|WP_Error
		 * @since 3.0.0
		 */
		public function create( &$args ) {

			$args = wp_parse_args(
				$args,
				array(
					'id'      => '',
					'status'  => 'publish',
					'title'   => __( 'New Course', 'learnpress' ),
					'content' => '',
					'author'  => learn_press_get_current_user_id(),
				)
			);

			$course_id = wp_insert_post(
				array(
					'ID'           => $args['id'],
					'post_type'    => LP_COURSE_CPT,
					'post_status'  => $args['status'],
					'post_title'   => $args['title'],
					'post_content' => $args['content'],
					'post_author'  => $args['author'],
				)
			);

			if ( $course_id ) {
				// add default meta for new course
				$default_meta = LP_Course::get_default_meta();

				if ( is_array( $default_meta ) ) {
					foreach ( $default_meta as $key => $value ) {
						update_post_meta( $course_id, '_lp_' . $key, $value );
					}
				}
			}

			return $course_id;
		}

		public function update( &$course ) {
			// TODO: Implement update() method.
		}

		/**
		 * Delete course.
		 *
		 * @param object $course_id
		 *
		 * @since 3.0.0
		 * @editor tungnx
		 * @modify 4.1.4.1
		 */
		public function delete( &$course_id ) {
			// section curd
			// $curd = new LP_Section_CURD( $course_id );
			// clear course items
			// $curd->clear();
		}

		/**
		 * Delete course itself and sections.
		 *
		 * @param int|object $course_id
		 * @param bool       $delete_item - Optional. TRUE will delete all items assigned to course
		 */
		public function delete_course( $course_id, $delete_item = false ) {
			if ( $delete_item ) {
				$course = learn_press_get_course( $course_id );

				if ( $course ) {
					$items = $course->get_items();

					if ( $items ) {
						foreach ( $items as $item ) {
							wp_delete_post( $item );
						}
					}
				}
			}

			wp_delete_post( $course_id );
		}

		/**
		 * Duplicate course.
		 *
		 * @param int $course_id
		 * @param array $args
		 *
		 * @return int|WP_Error
		 * @since 3.0.0
		 * @version 1.0.2
		 */
		public function duplicate( &$course_id, $args = array() ) {
			$course_id_new = 0;

			try {
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new Exception( 'Sorry! You don\'t have permission to duplicate this course' );
				}

				$course_origin = learn_press_get_course( $course_id );
				if ( ! $course_origin ) {
					throw new Exception( 'The course is invalid!' );
				}

				$course_id_new = learn_press_duplicate_post( $course_id, $args );

				if ( is_wp_error( $course_id_new ) ) {
					return $course_id_new;
				}

				$this->duplicate_sections( $course_id_new, $course_origin );

				// Call save course duplicate
				LP_Course_Post_Type::instance()->save( $course_id_new );

				do_action( 'learn-press/item/after-duplicate', $course_id, $course_id_new, $args );
			} catch ( Throwable $e ) {
				$course_id_new = new WP_Error( $e->getMessage() );
			}

			return $course_id_new;
		}

		/**
		 * Duplicate sections and items of course
		 *
		 * @param int $course_id_new
		 * @param LP_Course $course_origin
		 */
		public function duplicate_sections( int $course_id_new, LP_Course $course_origin ) {
			try {
				$curriculum = $course_origin->get_curriculum_raw();

				// new course section curd
				$section_curd_new = new LP_Section_CURD( $course_id_new );
				foreach ( $curriculum as $section_origin ) {
					$data = array(
						'section_id'          => $section_origin['id'],
						'section_name'        => $section_origin['title'],
						'section_course_id'   => $course_id_new,
						'section_order'       => $section_origin['order'],
						'section_description' => $section_origin['description'],
					);

					// Hook before clone section.
					$can_clone = true;
					$can_clone = apply_filters( 'lp/section/can-clone', $can_clone, $course_id_new, $course_origin, $section_origin );
					if ( ! $can_clone ) {
						continue;
					}

					// Clone section
					$section_new = $section_curd_new->create( $data );
					// Clone items of section
					if ( isset( $section_new['section_id'] ) ) {
						$this->duplicate_section_items( $section_new['section_id'], $section_curd_new, $section_origin );
						$args = compact( 'section_new', 'section_origin', 'course_id_new', 'course_origin' );
						do_action( 'lp/section/clone/success', $args );
					}
				}
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}
		}

		/**
		 * Duplicate items of section.
		 *
		 * @param int $section_id_new
		 * @param LP_Section_CURD $section_curd_new
		 * @param array $section_origin
		 *
		 * @version 3.0.1
		 * @since 3.0.0
		 * @return void
		 */
		public function duplicate_section_items( int $section_id_new, LP_Section_CURD $section_curd_new, array $section_origin ) {
			try {
				$item_origins = $section_origin['items'] ?? array();
				$new_items    = array();

				foreach ( $item_origins as $key => $item_origin ) {
					if ( ! isset( $item_origin['type'] ) ) {
						continue;
					}

					// Get class CURD of item.
					$class_item_curd_str = ucwords( $item_origin['type'], '_' ) . '_CURD';
					/**
					 * @var LP_Object_Data_CURD $class_item_curd_str
					 */
					if ( class_exists( $class_item_curd_str ) ) {
						$can_clone = true;
						$args      = compact( 'item_origin', 'section_id_new', 'section_curd_new', 'section_origin' );
						$can_clone = apply_filters( 'lp/section/item/can-clone', $can_clone, $args );

						if ( ! $can_clone ) {
							continue;
						}

						$class_item_curd = new $class_item_curd_str();
						$new_item_id     = $class_item_curd->duplicate(
							$item_origin['id'],
							array( 'post_status' => 'publish' )
						);

						// Prepare data to assign item to section.
						$new_item = array(
							'item_id'    => $new_item_id,
							'item_type'  => $item_origin['type'],
							'item_order' => $item_origin['order'],
						);

						$section_curd_new->assign_item_section( $section_id_new, $new_item );
					}
				}
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}
		}

		/**
		 * Load course data
		 *
		 * @param LP_Course|LP_Abstract_Course $course
		 *
		 * @return mixed
		 */
		public function load( &$course ) {
			// $this->read_course_curriculum( $course->get_id() );

			return $course;
		}

		/**
		 * Get all courses that contains an item by item id.
		 * Data returned is an array of all courses found.
		 *
		 * @param int  $item_id - ID of any item
		 * @param bool $check_support - Optional. TRUE will check if course is support that item
		 *
		 * @return array|bool
		 * @since 3.1.0
		 */
		public function get_course_by_item( $item_id, $check_support = true ) {
			if ( $check_support && ! learn_press_is_support_course_item_type( get_post_type( $item_id ) ) ) {
				return false;
			}

			global $wpdb;

			$query = $wpdb->prepare(
				"
				SELECT c.ID
				FROM {$wpdb->posts} c
				INNER JOIN {$wpdb->learnpress_sections} s ON c.ID = s.section_course_id
				INNER JOIN {$wpdb->learnpress_section_items} si ON si.section_id = s.section_id
				WHERE si.item_id = %d
			",
				$item_id
			);

			return $wpdb->get_col( $query );
		}

		/**
		 * Retrieve total sections of a course.
		 *
		 * @param int $course_id
		 *
		 * @return int
		 * @since 3.1.0
		 */
		public function count_sections( $course_id ) {
			global $wpdb;

			$query = $wpdb->prepare(
				"
				SELECT COUNT(section_id)
				FROM {$wpdb->learnpress_sections}
				WHERE section_course_id = %d
			",
				$course_id
			);

			return $wpdb->get_var( $query );
		}

		/**
		 * Read sections of a bundle of courses by ids
		 *
		 * @param int|array $course_id
		 *
		 * @return mixed|array
		 * @version 4.0.0
		 * @deprecated 4.1.6.9
		 * Use in addon co-instructor 4.0.2
		 */
		public function read_course_sections( $course_id ) {
			_deprecated_function( __FUNCTION__, '4.1.6.9 call from ' . debug_backtrace()[0]['function'] );
			return false;
			global $wpdb;

			settype( $course_id, 'array' );
			$first_course_sections = false;

			foreach ( $course_id as $cid ) {
				$course_sections = LP_Course_Utils::get_cached_db_sections( $cid );

				if ( false === $course_sections ) {
					$query = $wpdb->prepare(
						"
						SELECT s.section_id, s.section_name, s.section_course_id, s.section_order, s.section_description, 'asd'
						FROM {$wpdb->posts} p
						INNER JOIN {$wpdb->learnpress_sections} s ON p.ID = s.section_course_id
						WHERE p.ID = %d
						ORDER BY p.ID, `section_order` ASC
					",
						$cid
					);

					$course_sections = $wpdb->get_results( $query );

					if ( ! $course_sections ) {
						$course_sections = array();
					}

					if ( false === $first_course_sections ) {
						$first_course_sections = $course_sections;
					}

					LP_Course_Utils::set_cache_db_sections( $cid, $course_sections );
				}
			}

			return $first_course_sections;
		}

		/**
		 * Remove lesson, quiz from course's curriculum.
		 *
		 * @param int $item_id
		 * @param int $course_id - Optional. Added since 3.1.0
		 *
		 * @since 3.0.0
		 */
		public function remove_item( $item_id, $course_id = 0 ) {
			$learnpress_user_item_db = LP_User_Items_DB::getInstance();
			global $wpdb;
			// allow hook
			do_action( 'learn-press/before-remove-section-item', $item_id, $course_id );

			if ( $course_id ) {
				if ( is_array( $course_id ) ) {
					$format = array_fill( 0, sizeof( $course_id ), '%d' );
					$where  = $wpdb->prepare( 'AND s.section_course_id IN(' . join( ',', $format ) . ')', $course_id );
				} else {
					$where = $wpdb->prepare( 'AND s.section_course_id = %d', $course_id );
				}
				$query = $wpdb->prepare(
					"
					DELETE si
					FROM {$wpdb->learnpress_section_items} si
					INNER JOIN {$wpdb->learnpress_sections} s ON s.section_id = si.section_id
					WHERE item_id = %d
					{$where}
				",
					$item_id
				);
			} else {

				$query = $wpdb->prepare(
					"
					SELECT s.section_course_id
					FROM {$wpdb->learnpress_sections} s
					INNER JOIN {$wpdb->learnpress_section_items} si ON s.section_id = si.section_id
					WHERE si.item_id = %d
				",
					$item_id
				);

				$course_id = $wpdb->get_col( $query );
				$query     = $wpdb->prepare( "DELETE FROM {$wpdb->learnpress_section_items} WHERE item_id = %d", $item_id );
			}

			// delete item from course's section
			$wpdb->query( $query );

			if ( $course_id ) {
				settype( $course_id, 'array' );

				foreach ( $course_id as $cid ) {
					do_action( 'learn-press/removed-item-from-section', $item_id, $cid );
					$learnpress_user_item_db->reset_course_current_item( $cid, $item_id );
				} // end foreach $course_id as $cid
			}

			learn_press_reset_auto_increment( 'learnpress_section_items' );
		}

		/**
		 * Get recent courses.
		 *
		 * @param array $args
		 *
		 * @return array
		 * @since 3.0.0
		 * @version 1.0.1
		 */
		public function get_recent_courses( $args = array() ) {
			global $wpdb;

			$limit = absint( $args['limit'] ?? 5 );
			$order = LP_Helper::sanitize_params_submitted( $args['order'] ?? 'DESC' );
			if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) ) {
				$order = 'DESC';
			}

			$query = apply_filters(
				'learn-press/course-curd/query-recent-courses',
				$wpdb->prepare(
					"
					SELECT DISTINCT p.ID
					FROM $wpdb->posts AS p
					WHERE p.post_type = %s
					AND p.post_status = %s
					ORDER BY p.post_date {$order}
					LIMIT %d
					",
					LP_COURSE_CPT,
					'publish',
					$limit
				)
			);

			return $wpdb->get_col( $query );
		}

		/**
		 * Get all ID of users enrolled course ID.
		 *
		 * @param int $course_id
		 * @param int $limit
		 *
		 * @return array
		 * @Todo tungnx from LP 4.1.3.1 use LP_Course_DB::get_user_ids_enrolled replace
		 * Addon Student List still use
		 */
		public function get_user_enrolled( $course_id, $limit = - 1 ) {
			global $wpdb;
			if ( $limit < 0 ) {
				$limit = PHP_INT_MAX;
			}
			$query = $wpdb->prepare(
				"
				SELECT DISTINCT user.ID FROM {$wpdb->users} user
				INNER JOIN {$wpdb->learnpress_user_items} user_item ON user_item.user_id = user.ID
				WHERE user_item.item_id = %d
				AND user_item.item_type = %s
				LIMIT %d
				",
				$course_id,
				LP_COURSE_CPT,
				$limit
			);

			return $wpdb->get_results( $query );
		}

		public function count_enrolled_users( $course_ids ) {
			global $wpdb;

			if ( ! $course_ids ) {
				return 0;
			}

			$results = LP_Object_Cache::get( 'enrolled-users', 'learn-press/course' );

			if ( $results && is_numeric( $course_ids ) && array_key_exists( $course_ids, $results ) ) {
				return $results[ $course_ids ];
			}
			settype( $course_ids, 'array' );
			$sql = $wpdb->prepare(
				"
					SELECT item_id cid, count(ID) `count`
					FROM(SELECT DISTINCT user.ID,user_item.item_id FROM {$wpdb->users} user
						INNER JOIN {$wpdb->learnpress_user_items} user_item ON user_item.user_id = user.ID
						WHERE user_item.item_id IN(" . join( ',', $course_ids ) . ')
						AND user_item.item_type = %s
					) AS X GROUP BY item_id',
				LP_COURSE_CPT
			);

			$rows = $wpdb->get_results( $sql );
			if ( $rows ) {
				foreach ( $rows as $row ) {
					$results[ $row->cid ] = $row->count;
				}
			}

			$total = 0;
			foreach ( $course_ids as $course_id ) {
				if ( empty( $results[ $course_id ] ) ) {
					$results[ $course_id ] = 0;
				}
				$total += $results[ $course_id ];
			}
			LP_Object_Cache::set( 'enrolled-users', $results, 'learn-press/course' );

			return $total;
		}

		/**
		 * Get feature courses.
		 *
		 * @param array $args
		 *
		 * @return array
		 * @version 1.0.1
		 * @since 3.0.0
		 * @Todo: should call LP_Course_DB
		 */
		public function get_featured_courses( $args = array() ) {
			global $wpdb;
			$lp_course_db = LP_Course_DB::getInstance();
			$courses      = [];

			try {
				$limit    = absint( $args['limit'] ?? 5 );
				$order    = LP_Helper::sanitize_params_submitted( $args['order'] ?? 'DESC' );
				$order    = in_array( $order, array( 'ASC', 'DESC' ) ) ? $order : 'DESC';
				$order_by = LP_Helper::sanitize_params_submitted( $args['order_by'] ?? 'post_date' );
				$cols     = $lp_course_db->get_cols_of_table( $lp_course_db->tb_posts );
				$order_by = in_array( $order_by, $cols ) ? $order_by : 'post_date'; // For security

				if ( $limit <= 0 ) {
					$limit = 0;
				}

				$query = apply_filters(
					'learn-press/course-curd/query-featured-courses',
					$wpdb->prepare(
						"
						SELECT DISTINCT p.ID
						FROM {$wpdb->posts} p
	                    LEFT JOIN {$wpdb->postmeta} as pmeta ON p.ID=pmeta.post_id AND pmeta.meta_key = %s
	                    WHERE p.post_type = %s
							AND p.post_status = %s
							AND pmeta.meta_value = %s
	                    ORDER BY p.{$order_by} {$order}
	                    LIMIT %d
                		",
						'_lp_featured',
						LP_COURSE_CPT,
						'publish',
						'yes',
						$limit
					)
				);

				$courses = $wpdb->get_col( $query );
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}

			return $courses;
		}
	}
}
