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
				'COURSE_NOT_EXISTS' => __( 'Course does not exist.', 'learnpress' ),
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
		 * @param       $course_id
		 * @param array $args
		 *
		 * @return mixed|WP_Error
		 * @since 3.0.0
		 */
		public function duplicate( &$course_id, $args = array() ) {

			if ( ! $course_id ) {
				return new WP_Error( __( '<p>Op! ID not found</p>', 'learnpress' ) );
			}

			if ( learn_press_get_post_type( $course_id ) != LP_COURSE_CPT ) {
				return new WP_Error( __( '<p>Op! The course does not exist</p>', 'learnpress' ) );
			}

			// ensure that user can create course
			if ( ! current_user_can( 'edit_posts' ) ) {
				return new WP_Error(
					__(
						'<p>Sorry! You don\'t have permission to duplicate this course</p>',
						'learnpress'
					)
				);
			}
			// duplicate course
			$new_course_id = learn_press_duplicate_post( $course_id, $args );

			if ( ! $new_course_id || is_wp_error( $new_course_id ) ) {
				return new WP_Error( __( '<p>Sorry! Failed to duplicate course!</p>', 'learnpress' ) );
			} else {

				// original course section curd
				$course = LP_Course::get_course( $course_id );

				// new course section curd
				$new_course_section_curd = new LP_Section_CURD( $new_course_id );

				// curriculum course
				// $course->load_curriculum();
				$curriculum = $course->get_curriculum_raw();

				// quiz curd
				$quiz_curd = new LP_Quiz_CURD();

				if ( is_array( $curriculum ) ) {

					foreach ( $curriculum as $section ) {

						$data = array(
							'section_name'        => $section['title'],
							'section_course_id'   => $new_course_id,
							'section_order'       => $section['order'],
							'section_description' => $section['description'],
						);

						// clone sections to new course
						$new_section = $new_course_section_curd->create( $data );

						// get section items of original course
						$items = $section['items'];

						$new_items = array();

						// duplicate items
						if ( is_array( $items ) ) {
							foreach ( $items as $key => $item ) {
								// duplicate quiz
								if ( $item['type'] == LP_QUIZ_CPT ) {
									$new_item_id = $quiz_curd->duplicate(
										$item['id'],
										array( 'post_status' => 'publish' )
									);
								} else {
									// clone lesson
									$new_item_id = learn_press_duplicate_post(
										$item['id'],
										array( 'post_status' => 'publish' )
									);
								}

								// get new items data to add to section
								$new_items[ $key ] = array(
									'id'   => $new_item_id,
									'type' => $item['type'],
								);
							}

							// add new clone items to section
							$new_course_section_curd->add_items_section( $new_section['section_id'], $new_items );
						}
					}

					return $new_course_id;
				}
			}

			return false;
		}

		/**
		 * Load course data
		 *
		 * @param LP_Course|LP_Abstract_Course $course
		 *
		 * @return mixed
		 */
		public function load( &$course ) {
			$this->read_course_curriculum( $course->get_id() );

			return $course;
		}

		/**
		 * Read all items in a course from database with an array in pair of
		 * post ID and post type.
		 *
		 * @param int  $course_id
		 * @param bool $publish_only
		 *
		 * @return array
		 */
		public function read_course_items( $course_id, $publish_only = true, $section_ids = array() ) {
			static $results = null;

			if ( ! is_null( $results ) ) {
				return $results;
			}

			global $wpdb;
			$where = '';

			if ( $publish_only ) {
				$where = $wpdb->prepare(
					'
					AND c.post_status = %s
					AND it.post_status = %s
				',
					'publish',
					'publish'
				);
			}

			$types  = learn_press_course_get_support_item_types( true );
			$where .= $wpdb->prepare(
				' AND si.item_type IN(' . LP_Helper::db_format_array( $types, '%s' ) . ')',
				$types
			);

			if ( $section_ids ) {
				$where .= $wpdb->prepare(
					' AND s.section_id IN(' . LP_Helper::db_format_array(
						$section_ids,
						'%d'
					) . ')',
					$section_ids
				);
			}

			$query = $wpdb->prepare(
				"
				SELECT item_id id, it.post_type `type`, si.section_id
				FROM {$wpdb->learnpress_section_items} si
				INNER JOIN {$wpdb->learnpress_sections} s ON si.section_id = s.section_id
				INNER JOIN {$wpdb->posts} c ON c.ID = s.section_course_id
				INNER JOIN {$wpdb->posts} it ON it.ID = si.item_id
				WHERE c.ID = %d
				{$where}
				ORDER BY s.section_order, si.item_order, si.section_item_id ASC
			",
				$course_id
			);

			$results = $wpdb->get_results( $query );

			return $results;
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

		public static function update_items_format( $item ) {

			if ( empty( $item['args']['course_id'] ) ) {
				return false;
			}

			$course_id = absint( $item['args']['course_id'] );

			delete_option( 'update_items_format_' . $course_id );

			global $wpdb;

			$course = learn_press_get_course( $course_id );

			if ( ! $course ) {
				return false;
			}

			$item_ids = $course->get_items();

			if ( ! $item_ids ) {
				return false;
			}

			$query = $wpdb->prepare(
				"
				SELECT object_id AS id, REPLACE(slug, 'post-format-', '') AS format
				FROM {$wpdb->terms} AS t
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
				INNER JOIN {$wpdb->term_relationships} AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE tt.taxonomy IN (%s)
					AND tr.object_id IN (" . join( ',', $item_ids ) . ')
				ORDER BY t.name ASC
			',
				'post_format'
			);

			$terms = $wpdb->get_results( $query );

			if ( $terms ) {
				$updated = array();
				foreach ( $terms as $term ) {
					update_post_meta( $term->id, 'post_format', $term->format );
					$updated[] = $term->id;
				}
				$item_ids = array_diff( $item_ids, $updated );
			}

			if ( $item_ids ) {
				foreach ( $item_ids as $item_id ) {
					update_post_meta( $item_id, 'post_format', 'standard' );
				}
			}

			return false;
		}

		/**
		 * Read curriculum of a course from db into cache.
		 *
		 * @param $course_id
		 *
		 * @return array|mixed
		 */
		public function read_course_curriculum( $course_id ) {

			if ( learn_press_get_post_type( $course_id ) != LP_COURSE_CPT ) {
				return false;
			}

			if ( false !== LP_Course_Utils::get_course_items( $course_id ) ) {
				return false;
			}

			if ( $section_ids = $this->get_course_sections( $course_id, 'ids' ) ) {
				return $this->read_course_items( $course_id );
			}

			return false;
		}

		/**
		 * Get sections of course
		 *
		 * @param int    $course_id
		 * @param string $return
		 *
		 * @return array
		 * @version 4.0.0
		 *
		 */
		public function get_course_sections( $course_id, $return = '' ) {
			// if ( false === ( $sections = LP_Object_Cache::get( $course_id, 'learn-press/course-sections' ) ) ) {
			$sections = $this->read_course_sections( $course_id );
			// }

			// return $return === 'ids' ? LP_Object_Cache::get( $course_id, 'learn-press/course-sections-ids' ) : $sections;
			return $return === 'ids' ? LP_Course_Utils::get_cached_db_sections( $course_id, 'ids' ) : $sections;
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
		 * Retrieve total items of a course.
		 *
		 * @param int $course_id
		 *
		 * @return array
		 * @since 3.1.0
		 * @editor tungnx
		 * @modify 4.1.4.1 - comment - not use
		 */
		/*public function count_items( $course_id, $context = 'view' ) {
			global $wpdb;
			$params = array( $course_id );
			$sql    = "
				SELECT COUNT(it.ID) `count`, it.post_type
				FROM {$wpdb->learnpress_section_items} si
				INNER JOIN {$wpdb->learnpress_sections} s ON si.section_id = s.section_id
				INNER JOIN {$wpdb->posts} c ON c.ID = s.section_course_id
				INNER JOIN {$wpdb->posts} it ON it.ID = si.item_id
				WHERE s.section_course_id = %d";
			if ( $context == 'view' ) {
				$sql   .= ' AND c.post_status = %s
					AND it.post_status = %s ';
				$params = array_merge( $params, array( 'publish', 'publish' ) );
			}
			$sql  .= ' GROUP BY it.post_type ';
			$query = $wpdb->prepare( $sql, $params );

			$stats_object = array();
			if ( $results = $wpdb->get_results( $query ) ) {
				foreach ( $results as $result ) {
					$stats_object[ $result->post_type ] = $result->count;
				}
			}

			return $stats_object;
		}*/

		/**
		 * Read sections of a bundle of courses by ids
		 *
		 * @param int|array $course_id
		 *
		 * @return mixed|array
		 * @version 4.0.0
		 */
		public function read_course_sections( $course_id ) {
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
		 * Remove all course data from database, includes section and course's items
		 *
		 * @param $course_id
		 * @editor tungnx
		 * @modify 4.1.4.1 - comment - not use
		 */
		/*public function remove_course( $course_id ) {
			global $wpdb;

			$section_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT section_id FROM {$wpdb->prefix}learnpress_sections WHERE section_course_id = %d",
					$course_id
				)
			);
			if ( $section_ids ) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->prefix}learnpress_section_items WHERE %d AND section_id IN(" . join(
							',',
							$section_ids
						) . ')',
						1
					)
				);
				learn_press_reset_auto_increment( 'learnpress_section_items' );
			}

			// delete all sections
			$query = $wpdb->prepare(
				"
				DELETE FROM {$wpdb->prefix}learnpress_sections
				WHERE section_course_id = %d",
				$course_id
			);
			$wpdb->query( $query );
			learn_press_reset_auto_increment( 'learnpress_sections' );
		}*/

		/**
		 * Get recent courses.
		 *
		 * @param array $args
		 *
		 * @return array
		 */
		public function get_recent_courses( $args = array() ) {
			global $wpdb;

			$limit = ! empty( $args['limit'] ) ? $args['limit'] : - 1;
			$order = ! empty( $args['order'] ) ? $args['order'] : 'DESC';

			if ( $limit <= 0 ) {
				$limit = 0;
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
		 */
		public function get_featured_courses( $args = array() ) {
			global $wpdb;

			$limit    = ! empty( $args['limit'] ) ? $args['limit'] : - 1;
			$order_by = ! empty( $args['order_by'] ) ? $args['order_by'] : 'post_date';
			$order    = ! empty( $args['order'] ) ? $args['order'] : 'DESC';

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

			return $wpdb->get_col( $query );
		}

		/**
		 * Get popular courses.
		 *
		 * @param array $args
		 *
		 * @return array
		 * @author tungnx
		 * @version 1.0.0
		 * @depecated 4.1.6.4
		 */
		/*public function get_popular_courses( array $args = array() ): array {
			$lp_db = LP_Course_DB::getInstance();

			$filter        = new LP_Course_Filter();
			$filter->limit = $args['limit'];

			return $lp_db->get_popular_courses( $filter );
		}*/

		/**
		 * Get popular courses.
		 *
		 * @param array $args
		 *
		 * @return array
		 * @editor tungnx
		 * @modify 4.1.4 - comment - not use
		 */
		/*public function get_popular_courses_bk( $args = array() ) {
			global $wpdb;

			$limit = ! empty( $args['limit'] ) ? $args['limit'] : - 1;
			$order = ! empty( $args['order'] ) ? $args['order'] : 'DESC';

			if ( $limit <= 0 ) {
				$limit = 0;
			}

			$query = apply_filters(
				'learn-press/course-curd/query-popular-courses',
				$wpdb->prepare(
					"
					SELECT DISTINCT p.ID, COUNT(*) AS number_enrolled
					FROM {$wpdb->prefix}learnpress_user_items ui
					INNER JOIN {$wpdb->posts} p ON p.ID = ui.item_id
					WHERE ui.item_type = %s
						AND ( ui.status = %s OR ui.status = %s )
						AND p.post_status = %s
					ORDER BY number_enrolled {$order}
					LIMIT %d
				",
					LP_COURSE_CPT,
					'enrolled',
					'finished',
					'publish',
					$limit
				)
			);

			return $wpdb->get_col( $query );
		}*/

		/**
		 * @param int|array    $course_id
		 * @param string|array $statuses
		 *
		 * @return int
		 * @editor tungnx
		 * @modify 4.1.4 - comment - not use
		 */
		/*public function count_by_orders( $course_id, $statuses = 'completed' ) {
			return $this->count_by_orders2( $course_id, $statuses );
			global $wpdb;

			settype( $statuses, 'array' );
			$statuses_with_prefix = array();

			foreach ( $statuses as $k => $v ) {
				if ( ! preg_match( '/^lp-/', $v ) ) {
					$statuses_with_prefix[ $k ] = 'lp-' . $v;
				}
			}
			$course_ids = $course_id;
			settype( $course_ids, 'array' );
			$fetch_ids = array();

			foreach ( $course_ids as $course_id ) {

				if ( LP_COURSE_CPT !== get_post_type( $course_id ) ) {
					continue;
				}

				if ( false !== ( $count = LP_Object_Cache::get(
					'course-' . $course_id,
					'learn-press/course-orders'
				) ) ) {
					continue;
				} else {
					LP_Object_Cache::set( 'course-' . $course_id, 0, 'learn-press/course-orders' );
				}

				$fetch_ids[] = $course_id;
			}

			if ( $fetch_ids ) {
				$in_clause         = join( ',', array_fill( 0, sizeof( $statuses_with_prefix ), '%s' ) );
				$in_courses_clause = join( ',', array_fill( 0, sizeof( $fetch_ids ), '%d' ) );
				$query_args        = array_merge(
					array(
						'_course_id',
						LP_ORDER_CPT,
					),
					$statuses_with_prefix,
					$fetch_ids
				);

				$query = $wpdb->prepare(
					"
					SELECT oim.meta_value cid, COUNT(oim.meta_id) `count`
					FROM {$wpdb->learnpress_order_itemmeta} oim
					INNER JOIN {$wpdb->learnpress_order_items} oi ON oi.order_item_id = oim.learnpress_order_item_id
						AND oim.meta_key = %s
					INNER JOIN {$wpdb->posts} o ON o.ID = oi.order_id
					WHERE o.post_type = %s
					AND o.post_status IN ($in_clause)
					AND oim.meta_value IN ($in_courses_clause)
					GROUP BY oim.meta_value
				",
					$query_args
				);

				if ( $rows = $wpdb->get_results( $query ) ) {
					foreach ( $rows as $row ) {
						if ( empty( $row->cid ) ) {
							continue;
						}
						LP_Object_Cache::set(
							'course-' . $row->cid,
							intval( $row->count ),
							'learn-press/course-orders'
						);
					}
				}
			}

			$course_id = reset( $course_ids );

			return LP_Object_Cache::get( 'course-' . $course_id, 'learn-press/course-orders' );
		}*/

		/**
		 * @param int|array    $course_id
		 * @param string|array $statuses
		 *
		 * @return int
		 * @editor tungnx
		 * @modify 4.1.4 - comment - not use
		 */
		/*public function count_by_orders2( $course_id, $statuses = 'completed' ) {
			global $wpdb;

			settype( $statuses, 'array' );
			$statuses_with_prefix = array();

			foreach ( $statuses as $k => $v ) {
				if ( ! preg_match( '/^lp-/', $v ) ) {
					$statuses_with_prefix[ $k ] = 'lp-' . $v;
				}
			}
			$course_ids = $course_id;
			settype( $course_ids, 'array' );
			$fetch_ids    = array();
			$all_statuses = learn_press_get_order_statuses( false, true );

			foreach ( $course_ids as $course_id ) {

				if ( LP_COURSE_CPT !== get_post_type( $course_id ) ) {
					continue;
				}

				if ( ! metadata_exists( 'post', $course_id, 'order-' . $all_statuses[0] ) ) {
					$fetch_ids[] = $course_id;
				}
			}

			if ( $fetch_ids ) {
				$this->update_course_orders( $fetch_ids );
			}

			$course_id = reset( $course_ids );
			$count     = 0;
			foreach ( $statuses as $status ) {
				$orders = get_post_meta( $course_id, 'order-' . $status, true );
				if ( $orders ) {
					$count += sizeof( $orders );
				}
			}

			return $count;
		}*/

		/**
		 * @param int|array $course_id
		 *
		 * @return int
		 * @deprecated
		 * @editor tungnx
		 * @modify 4.1.4 - comment - not use
		 */
		/*public function count_enrolled_users_by_orders( $course_id ) {
			_deprecated_function( __FUNCTION__, '4.1.4' );
			$completed  = get_post_meta( $course_id, 'order-completed', true );
			$processing = get_post_meta( $course_id, 'order-processing', true );

			return absint( $completed ) + absint( $processing );

			$statuses = array( 'completed', 'processing' );
			$count    = $this->count_by_orders( $course_id, $statuses );

			return $count;
		}*/

		/*public function update_course_orders( $courses ) {
			LP_Repair_Database::instance()->sync_course_orders();
		}*/
	}
}
