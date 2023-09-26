<?php
/**
 * Class LP_Background_Single_Course
 *
 * Single to run not schedule, run one time and done when be call
 *
 * @since 4.1.1
 * @author tungnx
 * @version 1.0.1
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Background_Single_Course' ) ) {
	class LP_Background_Single_Course extends LP_Async_Request {
		protected $action = 'background_single_course';
		protected static $instance;
		/**
		 * @var $lp_course LP_Course
		 */
		protected $lp_course;
		/**
		 * @var array
		 */
		protected $data = array();

		/**
		 * Get params via $_POST and handle
		 * @in_array
		 * @see LP_Course_Post_Type::save
		 */
		protected function handle() {
			try {
				$handle_name = LP_Helper::sanitize_params_submitted( $_POST['handle_name'] ?? '' );
				$course_id   = intval( $_POST['course_id'] ?? 0 );
				if ( empty( $handle_name ) || ! $course_id ) {
					return;
				}

				$this->lp_course = learn_press_get_course( $course_id );
				$this->data      = LP_Helper::sanitize_params_submitted( $_POST['data'] ?? '' );

				if ( empty( $this->lp_course ) ) {
					return;
				}

				switch ( $handle_name ) {
					case 'save_post':
						$this->save_post();
						break;
					default:
						break;
				}
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}
		}

		/**
		 * Save course post data
		 *
		 * @throws Exception
		 */
		protected function save_post() {
			$this->save_price();
			$this->save_extra_info();
			$this->review_post_author();

			do_action( 'lp/background/course/save', $this->lp_course, $this->data );

			/**
			 * Clean cache courses
			 *
			 * @see LP_Course::get_courses() where set cache
			 */
			$keys_cache = LP_Courses_Cache::instance()->get_cache( LP_Courses_Cache::$keys );
			if ( $keys_cache ) {
				foreach ( $keys_cache as $key ) {
					LP_Courses_Cache::instance()->clear( $key );
				}
				LP_Courses_Cache::instance()->clear( LP_Courses_Cache::$keys );
			}
			// End
		}

		/**
		 * Save price course
		 *
		 * @return void
		 */
		protected function save_price() {
			$has_sale_price = false;
			$regular_price  = $this->data['_lp_regular_price'] ?? '';
			/*if ( empty( $regular_price ) ) {
				return;
			}*/

			$sale_price = $this->data['_lp_sale_price'] ?? '';
			$start_date = $this->data['_lp_sale_start'] ?? '';
			$end_date   = $this->data['_lp_sale_end'] ?? '';
			$price      = 0;

			if ( '' != $regular_price ) {
				$price = $regular_price;

				if ( '' != $sale_price ) {
					if ( floatval( $sale_price ) < floatval( $regular_price ) ) {
						$price          = $sale_price;
						$has_sale_price = true;
					}

					// Check in days sale
					if ( '' !== $start_date && '' !== $end_date ) {
						$now   = strtotime( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', time() ) ) );
						$end   = strtotime( $end_date );
						$start = strtotime( $start_date );

						$has_sale_price = $now >= $start && $now <= $end;
					}
				}
			}

			update_post_meta( $this->lp_course->get_id(), '_lp_price', $price );

			// Update course is sale
			if ( $has_sale_price ) {
				update_post_meta( $this->lp_course->get_id(), '_lp_course_is_sale', 1 );
			} else {
				delete_post_meta( $this->lp_course->get_id(), '_lp_course_is_sale' );
			}
		}

		/**
		 * Save Extra info of course
		 *
		 * @author tungnx
		 * @since 4.1.4.1
		 * @version 1.0.1
		 */
		protected function save_extra_info() {
			$lp_course_db    = LP_Course_DB::getInstance();
			$lp_course       = $this->lp_course;
			$lp_course_cache = LP_Course_Cache::instance();
			$course_id       = $lp_course->get_id();

			try {
				$extra_info = $this->lp_course->get_info_extra_for_fast_query();

				// Get and set first item id
				// Clean cache
				$key_cache_first_item_id = "$course_id/first_item_id";
				$lp_course_cache->clear( $key_cache_first_item_id );
				$first_item_id             = $lp_course_db->get_first_item_id( $lp_course->get_id() );
				$extra_info->first_item_id = $first_item_id;

				// Get and set total items course
				// Clean cache
				$key_cache_total_items = "$course_id/total_items";
				$lp_course_cache->clear( $key_cache_total_items );
				$total_items             = $lp_course_db->get_total_items( $lp_course->get_id() );
				$extra_info->total_items = $total_items;

				// Get and set sections, items of course
				// Clean cache
				$key_cache_sections_items = "$course_id/sections_items";
				$lp_course_cache->clear( $key_cache_sections_items );
				$sections_items             = $lp_course->get_sections_and_items_course_from_db_and_sort();
				$extra_info->sections_items = $sections_items;

				// Check items removed course, will delete on 'learnpress_user_items', 'learnpress_user_item_results' table
				$this->delete_user_items_data( $sections_items );

				// @since 4.2.1
				do_action( 'lp/course/extra-info/before-save', $lp_course, $extra_info );

				// Save post meta
				$lp_course->set_info_extra_for_fast_query( $extra_info );
				// End set first item id
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}
		}

		/**
		 * Delete items removed on course on tables:
		 * learnpress_user_items, learnpress_user_itemmeta, learnpress_user_item_results
		 *
		 * @return void
		 * @since 4.1.6.9
		 * @version 1.0.0
		 */
		private function delete_user_items_data() {
			// Get all user_item_id
			$lp_user_items_db        = LP_User_Items_DB::getInstance();
			$lp_user_item_results_db = LP_User_Items_Result_DB::instance();

			try {
				$course = $this->lp_course;

				// Get all items of course is attend
				$filter_user_items              = new LP_User_Items_Filter();
				$filter_user_items->only_fields = [ 'item_id', 'user_item_id' ];
				$filter_user_items->ref_id      = $course->get_id();
				$filter_user_items->ref_type    = LP_COURSE_CPT;
				$users_items_result             = $lp_user_items_db->get_user_items( $filter_user_items );

				$item_ids = $course->get_item_ids();

				$users_items_ids_need_delete = [];
				foreach ( $users_items_result as $user_item ) {
					//$users_items[ $user_item->item_id ] = $user_item->user_item_id;
					$item_id = $user_item->item_id;
					if ( ! in_array( $item_id, $item_ids ) ) {
						$users_items_ids_need_delete[] = $user_item->user_item_id;
					}
				}

				if ( empty( $users_items_ids_need_delete ) ) {
					return;
				}

				// Delete on tb lp_user_items
				$filter_delete                = new LP_User_Items_Filter();
				$filter_delete->user_item_ids = $users_items_ids_need_delete;
				$lp_user_items_db->remove_user_item_ids( $filter_delete );

				// Delete user_itemmeta
				$lp_user_items_db->remove_user_itemmeta( $filter_delete );

				// Delete user_item_results
				$lp_user_item_results_db->remove_user_item_results( $filter_delete );
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}
		}

		/**
		 * Check user is Instructor and enable review post of Instructor
		 *
		 * @author tungnx
		 * @since 4.1.4.1
		 * @version 1.0.0
		 */
		protected function review_post_author() {
			$lp_course = $this->lp_course;

			$user            = learn_press_get_current_user();
			$required_review = LP_Settings::get_option( 'required_review', 'yes' ) === 'yes';

			if ( $user->is_instructor() && $required_review && $lp_course->get_status() === 'publish' ) {
				wp_update_post(
					array(
						'ID'          => $lp_course->get_id(),
						'post_status' => 'pending',
					),
					array( '%d', '%s' )
				);
			}
			// End
		}

		/**
		 * @return LP_Background_Single_Course
		 */
		public static function instance(): self {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

	// Must run instance to register ajax.
	LP_Background_Single_Course::instance();
}
