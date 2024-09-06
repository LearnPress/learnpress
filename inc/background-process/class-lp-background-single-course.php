<?php
/**
 * Class LP_Background_Single_Course
 *
 * Single to run not schedule, run one time and done when be call
 *
 * @since 4.1.1
 * @author tungnx
 * @version 1.0.2
 */

use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\PostModel;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Background_Single_Course' ) ) {
	class LP_Background_Single_Course extends LP_Async_Request {
		protected $action = 'background_single_course';
		protected static $instance;
		/**
		 * @var $lp_course CourseModel
		 */
		protected $lp_course;
		/**
		 * @var array
		 */
		protected $data = array();

		/**
		 * Get params via $_POST and handle
		 *
		 * @see LP_Course_Post_Type::save_post()
		 */
		protected function handle() {
			try {
				@set_time_limit( 0 );
				$handle_name = LP_Request::get_param( 'handle_name', '', 'key', 'post' );
				$course_id   = intval( $_POST['course_id'] ?? 0 );
				if ( empty( $handle_name ) || ! $course_id ) {
					return;
				}

				$this->data      = LP_Request::get_param( 'data', [], 'text', 'post' );
				$this->lp_course = CourseModel::find( $course_id, true );
				if ( ! $this->lp_course instanceof CourseModel ) {
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
		 * Save course via post data
		 *
		 * @throws Exception
		 */
		protected function save_post() {
			if ( ! current_user_can( 'edit_lp_courses' ) ) {
				error_log( 'Not permission save background course' );
			}

			$courseModel = $this->lp_course;
			// Unset value of keys for calculate again
			unset( $courseModel->author );
			unset( $courseModel->first_item_id );
			unset( $courseModel->total_items );
			unset( $courseModel->sections_items );
			unset( $courseModel->meta_data->_lp_final_quiz );
			unset( $courseModel->categories );
			unset( $courseModel->image_url );
			$courseModel->get_author_model();
			$courseModel->get_first_item_id();
			$courseModel->get_total_items();
			$courseModel->get_section_items();
			$courseModel->get_final_quiz();
			$courseModel->get_categories();
			$courseModel->get_image_url();
			$courseModel->save();

			$this->save_extra_info();
			$this->clean_data_invalid();
			$this->review_post_author();

			// Old hook, addon wpml and assignment is using
			do_action( 'lp/background/course/save', learn_press_get_course( $this->lp_course->get_id() ), $this->data );
			// New hook from v4.2.6.9
			do_action( 'learnPress/background/course/save', $this->lp_course, $this->data );

			/**
			 * Clean cache courses
			 */
			$keys_cache = LP_Courses_Cache::instance()->get_cache( LP_Courses_Cache::$keys );
			if ( $keys_cache ) {
				foreach ( $keys_cache as $key ) {
					LP_Courses_Cache::instance()->clear( $key );
				}
				LP_Courses_Cache::instance()->clear( LP_Courses_Cache::$keys );
			}

			// Clear total courses free.
			$lp_courses_cache = new LP_Courses_Cache( true );
			$lp_courses_cache->clear_cache_on_group( LP_Courses_Cache::KEYS_COUNT_COURSES_FREE );
			// End
		}

		/**
		 * Clean data invalid
		 *
		 * @throws Exception
		 * @since 4.2.6.4
		 * @version 1.0.0
		 */
		protected function clean_data_invalid() {
			// Delete items of course not in table Post, can error from old data, delete item, but not remove it in sections course
			LP_Section_Items_DB::getInstance()->delete_item_not_in_tb_post( $this->lp_course->get_id() );
		}

		/**
		 * Save price course
		 *
		 * @return void
		 */
		protected function save_price_old() {
			if ( ! isset( $this->data['_lp_regular_price'] ) ) {
				return;
			}

			$has_sale_price = false;
			$regular_price  = $this->data['_lp_regular_price'];

			$sale_price = $this->data['_lp_sale_price'] ?? '';
			$start_date = $this->data['_lp_sale_start'] ?? '';
			$end_date   = $this->data['_lp_sale_end'] ?? '';
			$price      = 0;

			if ( '' != $regular_price ) {
				$regular_price = floatval( $regular_price );
				$price         = $regular_price;

				if ( '' != $sale_price ) {
					$sale_price = floatval( $sale_price );
					if ( $sale_price < $regular_price ) {
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

			//error_log( 'price: ' . $price );
			update_post_meta( $this->lp_course->get_id(), '_lp_price', $price );

			// Update course is sale
			if ( $has_sale_price ) {
				update_post_meta( $this->lp_course->get_id(), '_lp_course_is_sale', 1 );
			} else {
				delete_post_meta( $this->lp_course->get_id(), '_lp_course_is_sale' );
			}
		}

		/**
		 * Save price course
		 *
		 * @return void
		 */
		protected function save_price( CourseModel &$courseObj ) {
			$coursePost = new CoursePostModel( $courseObj );

			$regular_price = $courseObj->get_regular_price();
			$sale_price    = $courseObj->get_sale_price();
			if ( (float) $regular_price < 0 ) {
				$courseObj->meta_data->{CoursePostModel::META_KEY_REGULAR_PRICE} = '';
				$regular_price                                                   = $courseObj->get_regular_price();
			}

			if ( (float) $sale_price > (float) $regular_price ) {
				$courseObj->meta_data->{CoursePostModel::META_KEY_SALE_PRICE} = '';
				$sale_price                                                   = $courseObj->get_sale_price();
			}

			// Save sale regular price and sale price to table postmeta
			$coursePost->save_meta_value_by_key( CoursePostModel::META_KEY_REGULAR_PRICE, $regular_price );
			$coursePost->save_meta_value_by_key( CoursePostModel::META_KEY_SALE_PRICE, $sale_price );

			$has_sale = $courseObj->has_sale_price();
			if ( $has_sale ) {
				$courseObj->is_sale = 1;
				$coursePost->save_meta_value_by_key( CoursePostModel::META_KEY_IS_SALE, 1 );
			} else {
				$courseObj->is_sale = 0;
				delete_post_meta( $courseObj->get_id(), CoursePostModel::META_KEY_IS_SALE );
			}

			// Set price to sort on lists.
			$courseObj->price_to_sort = $courseObj->get_price();
		}

		/**
		 * Save Extra info of course
		 *
		 * @TODO after use value from CourseModel, will not use this function
		 * @since 4.1.4.1
		 * @version 1.0.1
		 */
		protected function save_extra_info() {
			$lp_course_db    = LP_Course_DB::getInstance();
			$lp_course       = learn_press_get_course( $this->lp_course->get_id() );
			$lp_course_cache = LP_Course_Cache::instance();
			$course_id       = $lp_course->get_id();

			try {
				$extra_info = $lp_course->get_info_extra_for_fast_query();

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
				$this->delete_user_items_data();

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
				$course = learn_press_get_course( $this->lp_course->get_id() );

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

			if ( $user->is_instructor() && $required_review && $lp_course->post_status === 'publish' ) {
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
