<?php
/**
 * Class LP_Course.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 4.0.1
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Course' ) ) {

	/**
	 * Class LP_Course
	 */
	class LP_Course extends LP_Abstract_Course {
		protected $key_info_extra_fast_query = '_lp_info_extra_fast_query';

		/**
		 * LP_Course constructor.
		 *
		 * @param mixed $course
		 */
		public function __construct( $course ) {
			parent::__construct( $course );
		}

		/**
		 * Debug log.
		 *
		 * @param $data
		 *
		 * @return array
		 * @editor tungnx
		 * @modify 4.1.5 - comment - not use
		 */
		/*public static function log( $data ) {
			return $data;
		}*/

		/**
		 * Get default course meta.
		 *
		 * @return mixed
		 * @since 3.0.0
		 */
		public static function get_default_meta() {
			$meta = array(
				'duration'                 => '10 weeks',
				'max_students'             => 1000,
				'students'                 => 0,
				'retake_count'             => 0,
				'featured'                 => 'no',
				//'block_lesson_content'     => 'no',
				'external_link_buy_course' => '',
				'course_result'            => 'evaluate_lesson',
				'passing_condition'        => 80,
				'price'                    => '',
				'sale_price'               => '',
				'sale_start'               => '',
				'sale_end'                 => '',
				'required_enroll'          => 'yes',
				'course_author'            => learn_press_get_current_user_id(),
			);

			return apply_filters( 'learn-press/course/default-meta', $meta );
		}

		/**
		 * Get LP Course.
		 *
		 * @param int   $course_id
		 *
		 * @return mixed|bool|LP_Course
		 * @Todo - Tungnx review to rewrite this method.
		 */
		public static function get_course( int $course_id = 0 ) {
			if ( isset( LP_Global::$courses[ $course_id ] ) ) {
				return LP_Global::$courses[ $course_id ];
			}

			$the_course = self::get_course_object( $course_id );

			if ( ! $the_course ) {
				return false;
			}

			$key_args = wp_parse_args(
				array(
					'id'   => $the_course->ID,
					'type' => $the_course->post_type,
				)
			);

			$key = LP_Helper::array_to_md5( $key_args );

			if ( empty( LP_Global::$courses[ $key ] ) ) {
				$class_name = self::get_course_class( $the_course );
				if ( is_string( $class_name ) && class_exists( $class_name ) ) {
					$course = new $class_name( $the_course->ID );
				} elseif ( $class_name instanceof LP_Abstract_Course ) {
					$course = $class_name;
				} else {
					$course = new self( $the_course->ID );
				}
				LP_Global::$courses[ $key ] = $course;
			} else {
				$course = LP_Global::$courses[ $key ];
			}

			/**
			 * Force to reload course data into cache if it is not
			 * loaded or has been deleted for some reasons.
			 */
			$course->load();

			return $course;
		}

		/**
		 * @param string $course_type
		 *
		 * @return string|false
		 */
		private static function get_class_name_from_course_type( $course_type ) {
			return LP_COURSE_CPT === $course_type ? __CLASS__ : 'LP_Course_' . implode(
				'_',
				array_map( 'ucfirst', explode( '-', $course_type ) )
			);
		}

		/**
		 * Get the course class name
		 *
		 * @param WP_Post $the_course
		 * @param array   $args (default: array())
		 *
		 * @return string
		 */
		private static function get_course_class( $the_course, $args = array() ) {
			$course_id = absint( $the_course->ID );
			$type      = $the_course->post_type;

			$class_name = self::get_class_name_from_course_type( $type );

			// Filter class name so that the class can be overridden if extended.
			return apply_filters( 'learn-press/course/object-class', $class_name, $type, $course_id );
		}

		/**
		 * Get the course object
		 *
		 * @param mixed $the_course
		 *
		 * @return WP_Post|bool false on failure
		 * @uses   WP_Post
		 */
		private static function get_course_object( $the_course ) {
			$the_course_passed = $the_course;
			if ( false === $the_course ) {
				$the_course = get_post_type() === LP_COURSE_CPT ? $GLOBALS['post'] : false;
			} elseif ( is_numeric( $the_course ) ) {
				$the_course = get_post( $the_course );
			} elseif ( $the_course instanceof LP_Abstract_Course ) {
				$the_course = get_post( $the_course->get_id() );
			} elseif ( ! ( $the_course instanceof WP_Post ) ) {
				$the_course = false;
			}

			if ( $the_course && $the_course->post_type !== LP_COURSE_CPT ) {
				$the_course = false;
			}

			return apply_filters( 'learn-press/course/post-object', $the_course, $the_course_passed );
		}

		/**
		 * Check time remaining course when enable duration expire
		 * Value: -1 is no limit (default)
		 * Value: 0 is block
		 * Administrator || (is instructor && is author course) will be not block.
		 *
		 * @return int second
		 * @since 4.0.0
		 * @author tungnx
		 * @version 1.0.1
		 */
		public function timestamp_remaining_duration(): int {
			$timestamp_remaining = - 1;
			$user                = learn_press_get_user( get_current_user_id() );

			if ( current_user_can( 'administrator' ) ||
				( current_user_can( LP_TEACHER_ROLE ) && $this->get_author()->get_id() === $user->get_id() ) ) {
				return $timestamp_remaining;
			}

			if ( 0 === absint( $this->get_data( 'duration' ) ) ) {
				return $timestamp_remaining;
			}

			if ( 'yes' !== $this->get_data( 'block_course_duration_expire' ) ) {
				return $timestamp_remaining;
			}

			if ( $user instanceof LP_User_Guest ) {
				return $timestamp_remaining;
			}

			$course_item_data = $user->get_course_data( $this->get_id() );

			if ( ! $course_item_data ) {
				return $timestamp_remaining;
			}

			$course_start_time   = $course_item_data->get_start_time()->get_raw_date();
			$duration            = $this->get_data( 'duration' );
			$timestamp_expire    = strtotime( $course_start_time . ' +' . $duration );
			$timestamp_current   = time();
			$timestamp_remaining = $timestamp_expire - $timestamp_current;

			if ( $timestamp_remaining < 0 ) {
				$timestamp_remaining = 0;
			}

			return apply_filters( 'learnpress/course/block_duration_expire/timestamp_remaining', $timestamp_remaining );
		}

		/**
		 * Get option enable block course when finish course
		 *
		 * @return bool
		 */
		public function enable_block_item_when_finish(): bool {
			return 'yes' === $this->get_data( 'block_course_finished' );
		}

		public function allow_repurchase() : bool {
			return 'yes' === $this->get_data( 'allow_repurchase' );
		}

		public function allow_repurchase_course_option() : string {
			return $this->get_data( 'allow_repurchase_course_option', 'reset' );
		}

		/**
		 * Get first item of course
		 *
		 * @author tungnx
		 * @since 4.0.0
		 * @modify 4.1.3
		 * @version 1.0.1
		 * @return int
		 */
		public function get_first_item_id(): int {
			$course_id = $this->get_id();

			try {
				// Get cache
				$lp_course_cache = LP_Course_Cache::instance();
				$key_cache       = "$course_id/first_item_id";
				$first_item_id   = $lp_course_cache->get_cache( $key_cache );

				if ( ! $first_item_id ) {
					$extra_info = $this->get_info_extra_for_fast_query();

					if ( ! $extra_info->first_item_id ) {
						$first_item_id             = LP_Course_DB::getInstance()->get_first_item_id( $course_id );
						$extra_info->first_item_id = $first_item_id;

						// Save post meta
						$this->set_info_extra_for_fast_query( $extra_info );
					} else {
						$first_item_id = $extra_info->first_item_id;
					}

					$lp_course_cache->set_cache( $key_cache, $first_item_id );
				}
			} catch ( Throwable $e ) {
				$first_item_id = 0;
			}

			return $first_item_id;
		}

		/**
		 * Get redirect url after enroll course
		 *
		 * @author tungnx
		 * @version 1.0.0
		 * @since 4.0.0
		 * @return false|string|WP_Error
		 */
		public function get_redirect_url_after_enroll() {
			$first_item_id = $this->get_first_item_id();
			$redirect      = $first_item_id ? $this->get_item_link( $first_item_id ) : get_the_permalink( $this->get_id() );

			return apply_filters( 'learnpress/rest-api/enroll-course/redirect', $redirect );
		}

		/**
		 * Get info extra on post meta to query fast
		 *
		 * @since 4.1.3
		 * @author tungnx
		 * @version 1.0.0
		 * @return LP_Course_Extra_Info_Fast_Query_Model
		 */
		public function get_info_extra_for_fast_query(): LP_Course_Extra_Info_Fast_Query_Model {
			$extra_info = new LP_Course_Extra_Info_Fast_Query_Model();

			try {
				$extra_info_str = get_post_meta( $this->get_id(), $this->key_info_extra_fast_query, true );

				if ( $extra_info_str ) {
					$extra_info_stdclass = json_decode( $extra_info_str );

					if ( JSON_ERROR_NONE !== json_last_error() ) {
						throw new Exception( 'Error json decode on ' . __METHOD__ );
					}

					$extra_info = $extra_info->map_stdclass( $extra_info_stdclass );
				}
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}

			return $extra_info;
		}

		/**
		 * Set extra info for query fast on post meta
		 *
		 * @since 4.1.3
		 * @author tungnx
		 * @version 1.0.0
		 * @param LP_Course_Extra_Info_Fast_Query_Model $data_object
		 */
		public function set_info_extra_for_fast_query( LP_Course_Extra_Info_Fast_Query_Model $data_object ) {
			try {
				$extra_info_json = json_encode( $data_object, JSON_UNESCAPED_UNICODE );

				if ( JSON_ERROR_NONE !== json_last_error() ) {
					throw new Exception( 'Error encode on ' . __METHOD__ );
				}

				update_post_meta( $this->get_id(), $this->key_info_extra_fast_query, $extra_info_json );
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}
		}

		/**
		 * Get info total items of Course
		 *
		 * @param string $type
		 * @param bool $include_preview
		 * @author tungnx
		 * @since 4.1.4.1
		 * @version 1.0.0
		 * @return int
		 */
		public function count_items( string $type = '', bool $include_preview = true ): int {
			$course_id = $this->get_id();

			// Get cache
			$lp_course_cache = LP_Course_Cache::instance();
			$key_cache       = "$course_id/total_items";
			$total_items     = $lp_course_cache->get_cache( $key_cache );
			$count_items     = 0;

			if ( ! $total_items ) {
				$extra_info = $this->get_info_extra_for_fast_query();

				if ( ! $extra_info->total_items ) {
					$total_items             = LP_Course_DB::getInstance()->get_total_items( $course_id );
					$extra_info->total_items = $total_items;

					// Save post meta
					$this->set_info_extra_for_fast_query( $extra_info );
				} else {
					$total_items = $extra_info->total_items;
				}

				$lp_course_cache->set_cache( $key_cache, $total_items );
			}

			if ( ! empty( $total_items ) ) {
				if ( ! empty( $type ) ) {
					if ( isset( $total_items->{$type} ) ) {
						$count_items = $total_items->{$type};
					}
				} else {
					$count_items = $total_items->count_items;
				}
			}

			return apply_filters( 'learn-press/course/count-items', intval( $count_items ), $course_id );
		}

		/**
		 * Delete relate data when delete course
		 *
		 * @since 4.1.4.1
		 * @author tungnx
		 * @version 1.0.0
		 */
		public function delete_relate_data_when_delete_course() {
			$lp_section_db    = LP_Section_DB::getInstance();
			$lp_user_items_db = LP_User_Items_DB::getInstance();

			try {
				// Check valid user
				if ( ! current_user_can( 'administrator' ) && $this->get_author( 'id' ) !== get_current_user_id() ) {
					return;
				}

				$section_ids = $lp_section_db->get_section_ids_by_course( $this->get_id() );

				$filter                   = new LP_Section_Filter();
				$filter->section_ids      = $section_ids;
				$filter->author_id_course = $this->get_author( 'id' );

				// Delete section
				$lp_section_db->delete_section( $filter );
				// Delete section items
				$lp_section_db->delete_section_items( $filter );

				$filter_user_items          = new LP_User_Items_Filter();
				$filter_user_items->item_id = $this->get_id();
				$user_course_ids            = $lp_user_items_db->get_user_items_by_course( $filter_user_items );

				$this->delete_user_item_and_result( $user_course_ids );

				// Clear cache total students enrolled.
				$lp_course_cache = new LP_Course_Cache( true );
				$lp_course_cache->clean_total_students_enrolled( $this->get_id() );
				$lp_course_cache->clean_total_students_enrolled_or_purchased( $this->get_id() );
				// Clear cache user course.
				$lp_user_items_cache = new LP_User_Items_Cache( true );
				$lp_user_items_cache->clean_user_items_by_course( $this->get_id() );
			} catch ( Throwable $e ) {
				error_log( __FUNCTION__ . ':' . $e->getMessage() );
			}
		}

		/**
		 * Delete user_items, user_itemmeta, user_item_results
		 * WHERE IN user_item_ids
		 *
		 * @param array $user_course_ids
		 */
		public function delete_user_item_and_result( array $user_course_ids ) {
			$lp_user_items_db     = LP_User_Items_DB::getInstance();
			$lp_user_item_results = LP_User_Items_Result_DB::instance();

			try {
				if ( empty( $user_course_ids ) ) {
					return;
				}

				// Get user_item_ids has parent in $user_course_ids
				$filter                = new LP_User_Items_Filter();
				$filter->user_item_ids = $user_course_ids;
				$user_item_ids         = $lp_user_items_db->get_item_ids_of_user_course( $filter );

				$user_item_ids_concat = array_merge( $user_course_ids, $user_item_ids );

				// Delete on tb lp_user_items
				$filter_delete                = new LP_User_Items_Filter();
				$filter_delete->user_item_ids = $user_item_ids_concat;
				$lp_user_items_db->remove_user_item_ids( $filter_delete );

				// Delete user_itemmeta
				$lp_user_items_db->remove_user_itemmeta( $filter_delete );

				// Delete user_item_results
				$lp_user_item_results->remove_user_item_results( $filter_delete );
			} catch ( Throwable $e ) {
				error_log( __FUNCTION__ . ':' . $e->getMessage() );
			}
		}

		/**
		 * Get list course
		 * Order By: price, title, rating, date ...
		 * Order: ASC, DES
		 *
		 * @param LP_Course_Filter $filter
		 * @param int $total_rows
		 *
		 * @return array|null|string|int
		 * @author tungnx
		 * @version 1.0.0
		 * @sicne 4.1.5
		 */
		public static function get_courses( LP_Course_Filter $filter, int &$total_rows = 0 ) {
			$lp_course_db = LP_Course_DB::getInstance();

			try {
				$key_cache            = md5( json_encode( $filter ) );
				$key_cache_total_rows = md5( json_encode( $filter ) . 'total_rows' );
				$courses_cache        = LP_Courses_Cache::instance()->get_cache( $key_cache );

				if ( false !== $courses_cache ) {
					$total_rows = LP_Courses_Cache::instance()->get_cache( $key_cache_total_rows );
					return $courses_cache;
				}

				// Sort by
				$filter->sort_by = (array) $filter->sort_by;
				foreach ( $filter->sort_by as $sort_by ) {
					$filter_tmp                      = clone $filter;
					$filter_tmp->only_fields         = array( 'ID' );
					$filter_tmp->return_string_query = true;

					switch ( $sort_by ) {
						case 'on_sale':
							$filter_tmp = $lp_course_db->get_courses_sort_by_sale( $filter_tmp );
							break;
						case 'on_feature':
							$filter_tmp = $lp_course_db->get_courses_sort_by_feature( $filter_tmp );
							break;
						default:
							$filter_tmp = apply_filters( 'lp/courses/filter/sort_by/' . $sort_by, $filter_tmp );
							break;
					}

					$query_courses_str = $lp_course_db->get_courses( $filter_tmp );

					$filter->where[] = "AND ID IN ({$query_courses_str})";
				}

				// Order by
				switch ( $filter->order_by ) {
					case 'price':
					case 'price_low':
						if ( 'price_low' === $filter->order_by ) {
							$filter->order = 'ASC';
						} else {
							$filter->order = 'DESC';
						}

						$filter = $lp_course_db->get_courses_order_by_price( $filter );
						break;
					case 'popular':
						$filter = $lp_course_db->get_courses_order_by_popular( $filter );
						break;
					default:
						$filter = apply_filters( 'lp/courses/filter/order_by/' . $filter->order_by, $filter );
						break;
				}

				// Query get results
				$filter  = apply_filters( 'lp/courses/filter', $filter );
				$courses = LP_Course_DB::getInstance()->get_courses( $filter, $total_rows );

				LP_Courses_Cache::instance()->set_cache( $key_cache, $courses );
				LP_Courses_Cache::instance()->set_cache( $key_cache_total_rows, $total_rows );

				/**
				 * Save key cache to array to clear
				 * @see LP_Background_Single_Course::save_post() - clear cache when save post
				 */
				LP_Courses_Cache::instance()->save_cache_keys( $key_cache );
				LP_Courses_Cache::instance()->save_cache_keys( $key_cache_total_rows );
			} catch ( Throwable $e ) {
				$courses = [];
				error_log( __FUNCTION__ . ': ' . $e->getMessage() );
			}

			return $courses;
		}

		/**
		 * Get full sections, items of course via Cache, extra info (if it has)
		 *
		 * @return array
		 * @since 4.1.6.9
		 * @version 1.0.0
		 * @author tungnx
		 */
		public function get_full_sections_and_items_course(): array {
			$sections_items = [];
			$course_id      = $this->get_id();

			try {
				// Get cache
				$lp_course_cache = LP_Course_Cache::instance();
				$key_cache       = "$course_id/sections_items";
				$sections_items  = $lp_course_cache->get_cache( $key_cache );

				if ( ! $sections_items ) {
					$extra_info = $this->get_info_extra_for_fast_query();

					if ( empty( $extra_info->sections_items ) ) {
						$sections_items             = $this->get_sections_and_items_course_from_db_and_sort();
						$extra_info->sections_items = $sections_items;

						// Save post meta
						$this->set_info_extra_for_fast_query( $extra_info );
					} else {
						$sections_items = $extra_info->sections_items;
					}

					$lp_course_cache->set_cache( $key_cache, $sections_items );
				}
			} catch ( Throwable $e ) {
				if ( LP_Debug::is_debug() ) {
					error_log( $e->getMessage() );
				}
			}

			return $sections_items;
		}

		/**
		 * Get all sections and items from database, then handle sort
		 * Only call when data change or not set
		 *
		 * @return array
		 * @since 4.1.6.9
		 * @version 1.0.0
		 * @author tungnx
		 */
		public function get_sections_and_items_course_from_db_and_sort(): array {
			$sections_items  = [];
			$course_id       = $this->get_id();
			$lp_course_db    = LP_Course_DB::getInstance();
			$lp_course_cache = LP_Course_Cache::instance();
			$key_cache       = "$course_id/sections_items";

			try {
				$sections_results       = $lp_course_db->get_sections( $course_id );
				$sections_items_results = $lp_course_db->get_full_sections_and_items_course( $course_id );
				$count_items            = count( $sections_items_results );
				$index_items_last       = $count_items - 1;
				$section_current        = 0;

				foreach ( $sections_items_results as $index => $sections_item ) {
					$section_new   = $sections_item->section_id;
					$section_order = $sections_item->section_order;
					$item          = new stdClass();
					$item->id      = $sections_item->item_id;
					$item->order   = $sections_item->item_order;
					$item->type    = $sections_item->item_type;

					if ( $section_new != $section_current ) {
						$sections_items[ $section_new ]              = new stdClass();
						$sections_items[ $section_new ]->id          = $section_new;
						$sections_items[ $section_new ]->order       = $section_order;
						$sections_items[ $section_new ]->title       = html_entity_decode( $sections_item->section_name );
						$sections_items[ $section_new ]->description = html_entity_decode( $sections_item->section_description );
						$sections_items[ $section_new ]->items       = [];

						// Sort item by item_order
						if ( $section_current != 0 ) {
							usort(
								$sections_items[ $section_current ]->items,
								function ( $item1, $item2 ) {
									return $item1->order - $item2->order;
								}
							);
						}

						$section_current = $section_new;
					}

					$sections_items[ $section_new ]->items[ $item->id ] = $item;

					if ( $index_items_last === $index ) {
						usort(
							$sections_items[ $section_current ]->items,
							function ( $item1, $item2 ) {
								return $item1->order - $item2->order;
							}
						);
					}
				}

				// Check case if section empty items
				foreach ( $sections_results as $section ) {
					$section_id = $section->section_id;
					if ( isset( $sections_items[ $section_id ] ) ) {
						continue;
					}

					$section_obj                   = new stdClass();
					$section_obj->id               = $section_id;
					$section_obj->order            = $section->section_order;
					$section_obj->title            = html_entity_decode( $section->section_name );
					$section_obj->description      = html_entity_decode( $section->section_description );
					$section_obj->items            = [];
					$sections_items[ $section_id ] = $section_obj;
				}

				// Sort section by section_order
				usort(
					$sections_items,
					function ( $section1, $section2 ) {
						return $section1->order - $section2->order;
					}
				);

				$lp_course_cache->set_cache( $key_cache, $sections_items );
			} catch ( Throwable $e ) {
				if ( LP_Debug::is_debug() ) {
					error_log( $e->getMessage() );
				}
			}

			return $sections_items;
		}

		/**
		 * Get sections of course.
		 *
		 * @param string $return.
		 * @param int    $section_id.
		 *
		 * @return array|bool|LP_Course_Section[]|LP_Course_Section
		 * @version 4.0.0
		 */
		public function get_sections( $return = 'object', $section_id = 0 ) {
			// $this->load_curriculum();
			// $sections = LP_Course_Utils::get_cached_db_sections( $this->get_id() );

			$sections_items = $this->get_full_sections_and_items_course();

			/*if ( false === $sections ) {
				return false;
			}*/

			//$position        = 0;
			$sections = array();
			foreach ( $sections_items as $k => $section_items ) {
				$position          = $k + 1;
				$section_items_tmp = [
					'section_id'          => $section_items->id,
					'section_name'        => $section_items->title,
					'section_course_id'   => $this->get_id(),
					'section_order'       => $section_items->order,
					'section_description' => $section_items->description,
					'items'               => $section_items->items,
				];
				$sid               = $section_items->id;
				$section           = new LP_Course_Section( $section_items_tmp );
				$section->set_position( $position );
				$sections[ $sid ] = $section;
			}

			if ( $section_id ) {
				$sections = $sections[ $section_id ] ?? [];
			}

			return apply_filters( 'learn-press/course-sections', $sections, $this->get_id(), $return, $section_id );
		}

		/**
		 * Get sections arr
		 *
		 * @param int $section_id
		 *
		 * @return array
		 * @version 1.0.0
		 * @sicne 4.1.7
		 */
		public function get_sections_data_arr( int $section_id = 0 ): array {
			$sections_items = $this->get_full_sections_and_items_course();
			$sections       = array();

			foreach ( $sections_items as $section_items ) {
				$section = [
					'section_id'          => $section_items->id,
					'section_name'        => $section_items->title,
					'section_course_id'   => $this->get_id(),
					'section_order'       => $section_items->order,
					'section_description' => $section_items->description,
					'items'               => $section_items->items,
				];

				if ( $section_id && $section_id == $section['section_id'] ) {
					$sections = $section;
					break;
				}

				$sections[] = $section;
			}

			return $sections;
		}

		/**
		 * Get raw data curriculum.
		 *
		 * @return array
		 * @since 3.0.0
		 * @editor tungnx
		 * @version 1.0.1
		 */
		public function get_curriculum_raw(): array {
			$sections_data = array();

			$sections_items = $this->get_full_sections_and_items_course();
			foreach ( $sections_items as $section_items ) {
				$section_items_arr              = (array) $section_items;
				$section_items_arr['course_id'] = $this->get_id();
				$section_items_arr['items']     = [];

				foreach ( $section_items->items as $item ) {
					$itemObject = $this->get_item( $item->id );

					if ( ! $itemObject instanceof LP_Course_Item ) {
						continue;
					}

					$item_arr                     = (array) $item;
					$item_arr['title']            = html_entity_decode( $itemObject->get_title() );
					$item_arr['preview']          = $itemObject->is_preview();
					$section_items_arr['items'][] = apply_filters( 'learn-press/item/to_array', $item_arr );
				}

				$sections_data[] = $section_items_arr;
			}

			return $sections_data;
		}

		/**
		 * Get all curriculum of this course.
		 *
		 * @param int  $section_id
		 * @param bool $force
		 *
		 * @return bool|LP_Course_Section[]
		 */
		public function get_curriculum( $section_id = 0, $force = false ) {
			return $this->get_sections( 'object', $section_id );
		}

		/**
		 * Return list of item's ids in course's curriculum.
		 *
		 * @param string|array $type
		 *
		 * @return array
		 * @since 3.0.0
		 * @version 3.0.2
		 */
		public function get_items( $type = '' ) {
			//$this->load();

			$sections_items = $this->get_full_sections_and_items_course();

			$items = array();

			foreach ( $sections_items as $section_items ) {
				foreach ( $section_items->items as $item ) {
					if ( ! empty( $type ) ) {
						if ( $type === $item->type ) {
							$items[] = $item->id;
						}
					} else {
						$items[] = $item->id;
					}
				}
			}

			return $items;
		}

		/**
		 * Get all items in a course.
		 *
		 * @param string $type . Type of items, eg: lp_lesson, lp_quiz...
		 *
		 * @return array
		 * @deprecated
		 */
		public function get_curriculum_items( $type = '' ) {
			return $this->get_items( $type );
		}

		/**
		 * Get evaluation type
		 *
		 * @since 4.2.1
		 * @version 1.0.0
		 * @return string
		 */
		public function get_evaluation_type(): string {
			$evaluation_type = get_post_meta( $this->get_id(), '_lp_course_result', true );
			if ( ! $evaluation_type ) {
				$evaluation_type = 'evaluate_lesson';
			}

			return $evaluation_type;
		}
	}
}
