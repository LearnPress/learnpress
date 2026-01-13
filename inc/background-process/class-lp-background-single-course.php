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
			ini_set( 'max_execution_time', 0 );
			try {
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
			ini_set( 'max_execution_time', LearnPress::$time_limit_default_of_sever );
			die;
		}

		/**
		 * Save course via post data
		 *
		 * @throws Exception
		 * @since 4.1.3
		 * @version 1.0.4
		 */
		protected function save_post() {
			if ( ! current_user_can( 'edit_lp_courses' ) ) {
				error_log( 'Not permission save background course' );
			}

			$courseModel = $this->lp_course;
			// Unset value of keys for calculate again
			unset( $courseModel->first_item_id );
			unset( $courseModel->total_items );
			unset( $courseModel->sections_items );
			unset( $courseModel->meta_data->_lp_final_quiz );
			unset( $courseModel->categories );
			unset( $courseModel->tags );
			//unset( $courseModel->image_url );
			$courseModel->get_author_model();
			$courseModel->get_first_item_id();
			$courseModel->get_total_items();
			$sections_items = $courseModel->get_section_items();
			// Update for case data old, section_order and item_order begin = 0
			$section_curd = new LP_Section_CURD( $courseModel->get_id() );
			$section_ids  = LP_Database::get_values_by_key( $sections_items, 'section_id' );
			$section_curd->update_sections_order( $section_ids );

			foreach ( $sections_items as $section_items ) {
				$section_curd->update_section_items( $section_items->section_id, $section_items->items );
			}
			// End
			$courseModel->get_final_quiz();
			$courseModel->get_categories();
			$courseModel->get_tags();
			/*$size_img_setting = LP_Settings::get_option( 'course_thumbnail_dimensions', [] );
			$size_img_send    = [
				$size_img_setting['width'] ?? 500,
				$size_img_setting['height'] ?? 300,
			];
			$courseModel->get_image_url( $size_img_send );*/
			$courseModel->save();

			//$this->save_extra_info();
			$this->clean_data_invalid();
			$this->review_post_author();

			// Old hook, addon wpml and assignment is using
			do_action( 'lp/background/course/save', learn_press_get_course( $this->lp_course->get_id() ), $this->data );
			// New hook from v4.2.6.9
			do_action( 'learnPress/background/course/save', $courseModel, $this->data );

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

			$lp_courses_cache->clear_cache_on_group( LP_Courses_Cache::KEYS_QUERY_COURSES_APP );
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
