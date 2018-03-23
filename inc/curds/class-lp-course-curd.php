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
				'COURSE_NOT_EXISTS' => __( 'Course does not exist.', 'learnpress' )
			);
		}

		/**
		 * Create course, with default meta.
		 *
		 * @since 3.0.0
		 *
		 * @param $args
		 *
		 * @return int|WP_Error
		 */
		public function create( &$args ) {

			$args = wp_parse_args( $args, array(
					'id'      => '',
					'status'  => 'publish',
					'title'   => __( 'New Course', 'learnpress' ),
					'content' => '',
					'author'  => learn_press_get_current_user_id()
				)
			);

			$course_id = wp_insert_post( array(
				'ID'           => $args['id'],
				'post_type'    => LP_COURSE_CPT,
				'post_status'  => $args['status'],
				'post_title'   => $args['title'],
				'post_content' => $args['content'],
				'post_author'  => $args['author']
			) );

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
		 * @since 3.0.0
		 *
		 * @param object $course_id
		 */
		public function delete( &$course_id ) {
			// section curd
			$curd = new LP_Section_CURD( $course_id );
			// clear course items
			$curd->clear();
		}

		/**
		 * Duplicate course.
		 *
		 * @since 3.0.0
		 *
		 * @param       $course_id
		 * @param array $args
		 *
		 * @return mixed|WP_Error
		 */
		public function duplicate( &$course_id, $args = array() ) {

			if ( ! $course_id ) {
				return new WP_Error( __( '<p>Op! ID not found</p>', 'learnpress' ) );
			}

			if ( get_post_type( $course_id ) != LP_COURSE_CPT ) {
				return new WP_Error( __( '<p>Op! The course does not exist</p>', 'learnpress' ) );
			}

			// ensure that user can create course
			if ( ! current_user_can( 'edit_posts' ) ) {
				return new WP_Error( __( '<p>Sorry! You don\'t have permission to duplicate this course</p>', 'learnpress' ) );
			}
			// duplicate course
			$new_course_id = learn_press_duplicate_post( $course_id, $args );

			if ( ! $new_course_id || is_wp_error( $new_course_id ) ) {
				return new WP_Error( __( '<p>Sorry! Failed to duplicate course!</p>', 'learnpress' ) );
			} else {

				// original course section curd
				$curd = new LP_Section_CURD( $course_id );

				// get course sections
				$sections = $this->get_course_sections( $course_id );
				// new course section curd
				$new_course_section_curd = new LP_Section_CURD( $new_course_id );

				$quiz_curd = new LP_Quiz_CURD();

				if ( is_array( $sections ) ) {

					foreach ( $sections as $section ) {

						$data = array(
							'section_name'        => $section->section_name,
							'section_course_id'   => $new_course_id,
							'section_order'       => $section->section_order,
							'section_description' => $section->section_description
						);

						// clone sections to new course
						$new_section = $new_course_section_curd->create( $data );

						// get section items of original course
						$items = $curd->get_section_items( $section->section_id );

						$new_items = array();

						// duplicate items
						if ( is_array( $items ) ) {
							foreach ( $items as $key => $item ) {
								// duplicate quiz
								if ( $item['type'] == LP_QUIZ_CPT ) {
									$new_item_id = $quiz_curd->duplicate( $item['id'], array( 'post_status' => 'publish' ) );
								} else {
									// clone lesson
									$new_item_id = learn_press_duplicate_post( $item['id'], array( 'post_status' => 'publish' ) );
								}

								// get new items data to add to section
								$new_items[ $key ] = array( 'id' => $new_item_id, 'type' => $item['type'] );
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
			$this->load_curriculum( $course );
			$this->load_data( $course );

			return $course;
		}

		/**
		 * Load course curriculum.
		 *
		 * @param LP_Course $course
		 */
		protected function load_curriculum( &$course ) {
			$course_id = $course->get_id();
			$this->read_course_curriculum( $course_id );
		}

		/**
		 * @param LP_Course $course
		 */
		public function load_data( &$course ) {

		}

		/**
		 * Read curriculum of a course from db into cache.
		 *
		 * @param $course_id
		 *
		 * @return bool
		 */
		public function read_course_curriculum( $course_id ) {
			global $wpdb;

			if ( get_post_type( $course_id ) != LP_COURSE_CPT ) {
				return false;
			}

			/**
			 * Get course's data from cache and if it is already existed
			 * then ignore that course.
			 */
			if ( wp_cache_get( 'course-' . $course_id, 'lp-course-curriculum' ) ) {
				return false;
			}

			// Set cache
			wp_cache_set( 'course-' . $course_id, array(), 'lp-course-curriculum' );

			$item_ids       = array();
			$meta_cache_ids = array( $course_id );
			$quiz_ids       = array();
			$lesson_ids     = array();
			$preview_ids    = array();

			$section_ids = array();

			if ( $sections = $this->get_course_sections( $course_id ) ) {
				$section_ids = wp_list_pluck( $sections, 'section_id' );
			}

			if ( false === ( $curriculum = LP_Hard_Cache::get( $course_id, 'lp-course-curriculum' ) ) ) {
				$all_section_ids = $section_ids;

				if ( $all_section_ids ) {
					$format        = array_fill( 0, sizeof( $all_section_ids ), '%d' );
					$post_statuses = array( 'publish' );
					$query_args    = array_merge( array( '_lp_preview' ), $all_section_ids, $post_statuses );

					$query = $wpdb->prepare( "
						SELECT s.*, si.*, IF( si.item_type, si.item_type, p.post_type ) as item_type, pm.meta_value as preview
						FROM {$wpdb->posts} p
						INNER JOIN {$wpdb->learnpress_section_items} si ON si.item_id = p.ID
						INNER JOIN {$wpdb->learnpress_sections} s ON s.section_id = si.section_id
						LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
						WHERE s.section_id IN(" . join( ',', $format ) . ")
						AND p.post_status IN(%s)
						ORDER BY s.section_course_id, s.section_order, si.item_order ASC
					", $query_args );

					if ( $results = $wpdb->get_results( $query ) ) {
						$curriculum = array();
						$cur_id     = 0;

						foreach ( $results as $row ) {
							// Switch to other course
							if ( $row->section_course_id !== $cur_id ) {

								// If $cur_id is already set to a course
								if ( $cur_id ) {
									wp_cache_set( 'course-' . $cur_id, $curriculum, 'lp-course-curriculum' );
								}

								// Set $cur_id to new course and reset $curriculum
								$cur_id = $row->section_course_id;

								// Reset
								$curriculum = $quiz_ids = $lesson_ids = $preview_ids = array();
							}

							$curriculum[] = $row;

							if ( empty( $item_ids[ $row->section_course_id ] ) ) {
								$item_ids[ $row->section_course_id ] = array( $row->item_id );
							} else {
								$item_ids[ $row->section_course_id ][] = $row->item_id;
							}

							$meta_cache_ids[] = $row->item_id;

							if ( $row->item_type === LP_QUIZ_CPT ) {
								$quiz_ids[] = $row->item_id;
							} elseif ( $row->item_type === LP_LESSON_CPT ) {
								$lesson_ids[] = $row->item_id;
							}

							if ( $row->preview === 'yes' ) {
								$preview_ids[] = $row->item_id;
							}
						}

						wp_cache_set( 'course-' . $cur_id, $curriculum, 'lp-course-curriculum' );
						wp_cache_set( 'course-' . $cur_id, $quiz_ids, 'lp-course-' . LP_QUIZ_CPT );
						wp_cache_set( 'course-' . $cur_id, $lesson_ids, 'lp-course-' . LP_LESSON_CPT );
						wp_cache_set( 'course-' . $cur_id, $preview_ids, 'lp-course-preview-items' );

						// Cache items ids for using in some cases
						foreach ( $item_ids as $cid => $ids ) {
							wp_cache_set( 'course-' . $cid, $ids, 'lp-course-items' );
						}

						LP_Hard_Cache::set( $course_id, $curriculum, 'lp-course-curriculum' );
					}
				}
			} elseif ( is_array( $curriculum ) ) {

				foreach ( $curriculum as $item ) {
					$meta_cache_ids[] = $item->item_id;
					$item_ids[]       = $item->item_id;

					if ( $item->item_type === LP_QUIZ_CPT ) {
						$quiz_ids[] = $item->item_id;
					} elseif ( $item->item_type === LP_LESSON_CPT ) {
						$lesson_ids[] = $item->item_id;
					}

					if ( $item->preview === 'yes' ) {
						$preview_ids[] = $item->item_id;
					}
				}

				// Set cache
				wp_cache_set( 'course-' . $course_id, $curriculum, 'lp-course-curriculum' );
				wp_cache_set( 'course-' . $course_id, $item_ids, 'lp-course-items' );
				wp_cache_set( 'course-' . $course_id, $lesson_ids, 'lp-course-' . LP_LESSON_CPT );
				wp_cache_set( 'course-' . $course_id, $quiz_ids, 'lp-course-' . LP_QUIZ_CPT );
				wp_cache_set( 'course-' . $course_id, $preview_ids, 'lp-course-preview-items' );

			}

			if ( $meta_cache_ids ) {
				LP_Helper_CURD::cache_posts( $meta_cache_ids );
				LP_Helper_CURD::update_meta_cache( 'post', $meta_cache_ids );
			}

			if ( $quiz_ids ) {
				$quiz_factory = new LP_Quiz_CURD();
				$quiz_factory->load_questions( $quiz_ids );
			}

			return true;
		}

		/**
		 * Get course curriculum.
		 *
		 * @param int $course_id
		 *
		 * @return array
		 */
		public function get_curriculum( $course_id ) {
			LP_Debug::log_function( __CLASS__ . '::' . __FUNCTION__ );

			$course = learn_press_get_course( $course_id );

			if ( false === ( $curriculum = wp_cache_get( 'course-' . $course_id, 'lp-course-curriculum-sections' ) ) ) {

				if ( $sections = wp_cache_get( 'course-' . $course_id, 'lp-course-sections' ) ) {
					$position = 0;
					foreach ( $sections as $k => $section ) {
						$_section = new LP_Course_Section( $section );
						$_section->set_position( ++ $position );
						$curriculum[ $section->section_id ] = $_section;
					}

					// Update post meta
					if ( $items = $course->get_items() ) {

						sort( $items );
						$cache_key = md5( serialize( $items ) );

						if ( false === ( $item_formats = LP_Hard_Cache::get( $cache_key, 'lp-item-formats' ) ) ) {
							global $wpdb;
							$item_formats = array_fill_keys( $items, 'standard' );
							$query        = $wpdb->prepare( "
								SELECT t.term_id, REPLACE(slug, 'post-format-', '') as format, object_id
								FROM {$wpdb->terms} AS t 
								INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
								INNER JOIN {$wpdb->term_relationships} AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
								WHERE tt.taxonomy IN (%s)
									AND tr.object_id IN (" . join( ',', $items ) . ")
								ORDER BY t.name ASC
							", 'post_format' );

							if ( $terms = $wpdb->get_results( $query ) ) {
								foreach ( $terms as $term ) {
									$item_formats[ $term->object_id ] = $term->format;
								}
							}

							LP_Hard_Cache::set( $cache_key, $item_formats, 'lp-item-formats' );
						}

						foreach ( $items as $item_id ) {
							if ( empty( $item_formats[ $item_id ] ) ) {
								$item_formats[ $item_id ] = 'standard';
							}
							wp_cache_set( 'item-format-' . $item_id, $item_formats[ $item_id ], 'lp-item-formats' );
						}
					}
				}
				wp_cache_set( 'course-' . $course_id, $curriculum, 'lp-course-curriculum-sections' );

			}
			LP_Debug::log_function( __CLASS__ . '::' . __FUNCTION__ );

			return $curriculum;
		}

		/**
		 * Get sections of course
		 *
		 * @param $course_id
		 *
		 * @return array
		 */
		public function get_course_sections( $course_id ) {
			$this->read_course_sections( $course_id );

			return wp_cache_get( 'course-' . $course_id, 'lp-course-sections' );
		}

		/**
		 * Read sections of a bundle of courses by ids
		 *
		 * @param int|array $course_id
		 *
		 * @return mixed|array
		 */
		public function read_course_sections( $course_id ) {

			global $wpdb;

			settype( $course_id, 'array' );
			sort( $course_id );
			$fetch_ids = array();

			foreach ( $course_id as $fetch_id ) {
				if ( get_post_type( $fetch_id ) != LP_COURSE_CPT ) {
					continue;
				}

				/**
				 * Get course's data from cache and if it is already existed
				 * then ignore that course.
				 */
				if ( false !== wp_cache_get( 'course-' . $fetch_id, 'lp-course-sections' ) ) {
					continue;
				}

				wp_cache_set( 'course-' . $fetch_id, array(), 'lp-course-sections' );

				$section_curd = new LP_Section_CURD( $fetch_id );
				$section_curd->read_sections_ids();

				$fetch_ids[] = $fetch_id;
			}

			if ( ! $fetch_ids ) {
				return false;
			}

			$cache_key = md5( serialize( $fetch_ids ) );

			if ( false === ( $course_sections = LP_Hard_Cache::get( $cache_key, 'lp-course-sections' ) ) ) {
				$format = array_fill( 0, sizeof( $fetch_ids ), '%d' );
				$query  = $wpdb->prepare( "
					SELECT s.* FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->learnpress_sections} s ON p.ID = s.section_course_id
					WHERE p.ID IN(" . join( ',', $format ) . ")
					ORDER BY p.ID, `section_order` ASC
				", $fetch_ids );

				$course_sections = array();

				if ( $results = $wpdb->get_results( $query ) ) {
					$cur_id = 0;
					foreach ( $results as $row ) {
						if ( $row->section_course_id !== $cur_id ) {
							if ( $cur_id ) {
								wp_cache_set( 'course-' . $cur_id, $course_sections, 'lp-course-sections' );
							}
							$cur_id          = $row->section_course_id;
							$course_sections = array();
						}
						$course_sections[] = $row;
					}
					wp_cache_set( 'course-' . $cur_id, $course_sections, 'lp-course-sections' );
				}

				LP_Hard_Cache::set( $cache_key, $course_sections, 'lp-course-sections' );
			} else {
				foreach ( $fetch_ids as $cid ) {
					wp_cache_set( 'course-' . $cid, $course_sections, 'lp-course-sections' );
				}
			}
			unset( $course_sections );

			return true;
		}

		/**
		 * Remove lesson, quiz from course's curriculum.
		 *
		 * @since 3.0.0
		 *
		 * @param $item_id
		 */
		public function remove_item( $item_id ) {

			global $wpdb;

			// allow hook
			do_action( 'learn-press/before-remove-section-item', $item_id );

			// delete item from course's section
			$wpdb->query(
				$wpdb->prepare( "DELETE FROM {$wpdb->prefix}learnpress_section_items WHERE item_id = %d", $item_id )
			);

			learn_press_reset_auto_increment( 'learnpress_section_items' );
		}

		/**
		 * Remove all course data from database, includes section and course's items
		 *
		 * @param $course_id
		 */
		public function remove_course( $course_id ) {
			global $wpdb;

			$section_ids = $wpdb->get_col( $wpdb->prepare( "SELECT section_id FROM {$wpdb->prefix}learnpress_sections WHERE section_course_id = %d", $course_id ) );
			if ( $section_ids ) {
				$wpdb->query(
					$wpdb->prepare( "DELETE FROM {$wpdb->prefix}learnpress_section_items WHERE %d AND section_id IN(" . join( ',', $section_ids ) . ")", 1 )
				);
				learn_press_reset_auto_increment( 'learnpress_section_items' );
			}

			// delete all sections
			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->prefix}learnpress_sections
				WHERE section_course_id = %d",
				$course_id
			);
			$wpdb->query( $query );
			learn_press_reset_auto_increment( 'learnpress_sections' );
		}

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

			$query = apply_filters( 'learn-press/course-curd/query-recent-courses',
				$wpdb->prepare( "
					SELECT DISTINCT p.ID 
						FROM $wpdb->posts AS p
						WHERE p.post_type = %s
						AND p.post_status = %s
						ORDER BY p.post_date {$order}
						LIMIT %d
				", LP_COURSE_CPT, 'publish', $limit
				)
			);

			return $wpdb->get_col( $query );
		}

		/**
		 * Get all users enrolled course ID.
		 *
		 * @param     $course_id
		 * @param int $limit
		 *
		 * @return array|null|object
		 */
		public function get_user_enrolled( $course_id, $limit = - 1 ) {
			global $wpdb;
			if ( $limit < 0 ) {
				$limit = PHP_INT_MAX;
			}
			$query = $wpdb->prepare( "
				SELECT DISTINCT user.ID FROM {$wpdb->users} user
				INNER JOIN {$wpdb->prefix}learnpress_user_items user_item ON user_item.user_id = user.ID
				WHERE user_item.item_id = %d
				AND user_item.item_type = %s
				LIMIT %d
			", $course_id, LP_COURSE_CPT, $limit );

			return $wpdb->get_results( $query );
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

			$query = apply_filters( 'learn-press/course-curd/query-featured-courses',
				$wpdb->prepare( "
					SELECT DISTINCT p.ID 
					FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->postmeta} as pmeta ON p.ID=pmeta.post_id AND pmeta.meta_key = %s
                    WHERE p.post_type = %s
						AND p.post_status = %s
						AND pmeta.meta_value = %s
                    ORDER BY p.{$order_by} {$order}
                    LIMIT %d
                ", '_lp_featured', LP_COURSE_CPT, 'publish', 'yes', $limit )
			);

			return $wpdb->get_col( $query );
		}

		/**
		 * Get popular courses.
		 *
		 * @param array $args
		 *
		 * @return array
		 */
		public function get_popular_courses( $args = array() ) {
			global $wpdb;

			$limit = ! empty( $args['limit'] ) ? $args['limit'] : - 1;
			$order = ! empty( $args['order'] ) ? $args['order'] : 'DESC';

			if ( $limit <= 0 ) {
				$limit = 0;
			}

			$query = apply_filters( 'learn-press/course-curd/query-popular-courses',
				$wpdb->prepare( "
					SELECT DISTINCT p.ID, COUNT(*) AS number_enrolled 
					FROM {$wpdb->prefix}learnpress_user_items ui
					INNER JOIN {$wpdb->posts} p ON p.ID = ui.item_id
					WHERE ui.item_type = %s
						AND ( ui.status = %s OR ui.status = %s )
						AND p.post_status = %s
					ORDER BY number_enrolled {$order}
					LIMIT %d
				", LP_COURSE_CPT, 'enrolled', 'finished', 'publish', $limit )
			);

			return $wpdb->get_col( $query );
		}

		/**
		 * @param int          $course_id
		 * @param string|array $statuses
		 *
		 * @return int
		 */
		public function count_by_orders( $course_id, $statuses = 'completed' ) {
			global $wpdb;

			settype( $statuses, 'array' );
			foreach ( $statuses as $k => $v ) {
				if ( ! preg_match( '/^lp-/', $v ) ) {
					$statuses[ $k ] = 'lp-' . $v;
				}
			}
			sort( $statuses );
			$cache_key = md5( serialize( $statuses ) );

			if ( false === ( $count = wp_cache_get( 'course-' . $course_id . '-' . $cache_key, 'lp-course-orders' ) ) ) {
				$in_clause  = join( ',', array_fill( 0, sizeof( $statuses ), '%s' ) );
				$query_args = array_merge( array( '_course_id', $course_id, LP_ORDER_CPT ), $statuses );

				$query = $wpdb->prepare( "
					SELECT count(oim.meta_id)
					FROM {$wpdb->learnpress_order_itemmeta} oim
					INNER JOIN {$wpdb->learnpress_order_items} oi ON oi.order_item_id = oim.learnpress_order_item_id
						AND oim.meta_key = %s
						AND oim.meta_value = %d
					INNER JOIN {$wpdb->posts} o ON o.ID = oi.order_id
					WHERE o.post_type = %s
					AND o.post_status IN ($in_clause)
				", $query_args );

				$count = absint( $wpdb->get_var( $query ) );

				wp_cache_set( 'course-' . $course_id . '-' . $cache_key, $count, 'lp-course-orders' );
			}

			return $count;
		}
	}
}