<?php
/**
 * class CourseBuilderAjax
 *
 * @since 4.3
 * @version 1.0.0
 */

namespace LearnPress\Ajax\CourseBuilder;

use Exception;
use LearnPress\Ajax\AbstractAjax;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\PostModel;
use LearnPress\Models\UserModel;
use LearnPress\Services\CourseService;
use LearnPress\TemplateHooks\CourseBuilder\Course\BuilderEditCourseTemplate;
use LearnPress\TemplateHooks\CourseBuilder\Course\BuilderListCoursesTemplate;
use LearnPress\TemplateHooks\CourseBuilder\CourseBuilderTemplate;
use LP_Course_CURD;
use LP_Datetime;
use LP_Helper;
use LP_REST_Response;
use stdClass;
use Throwable;
use WP_Post;

class CBEditCourseAjax extends AbstractAjax {
	/**
	 * Check permissions and validate parameters.
	 *
	 * @throws Exception
	 *
	 * @since 4.3
	 * @version 1.0.0
	 */
	public static function check_valid_course() {
		$params = wp_unslash( $_POST['data'] ?? '' );
		if ( empty( $params ) ) {
			throw new Exception( 'Error: params invalid!' );
		}

		// Check permission
		$userModel = UserModel::find( get_current_user_id(), true );
		if ( ! $userModel || ! $userModel->is_instructor() ) {
			throw new Exception( __( 'You are not allowed to edit courses', 'learnpress' ) );
		}

		$params      = LP_Helper::json_decode( $params, true );
		$course_id   = $params['course_id'] ?? 0;
		$courseModel = CourseModel::find( $course_id, true );
		if ( $courseModel ) {
			$params['courseModel'] = $courseModel;
		}
		$params['userModel'] = $userModel;

		return $params;
	}

	/**
	 * Save Course.
	 *
	 * @since 4.3.6
	 * @version 1.0.0
	 */
	public static function cb_save_course() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();
		$courseService  = CourseService::instance();

		try {
			$data = self::check_valid_course();
			/** @var CourseModel $courseModel */
			$courseModel           = $data['courseModel'] ?? null;
			$userModel             = $data['userModel'] ?? null;
			$settings              = $data['course_settings'] ?? false;
			$is_edit               = $courseModel instanceof CourseModel;
			$course_title          = trim( LP_Helper::sanitize_params_submitted( $data['course_title'] ?? '' ) );
			$course_description    = LP_Helper::sanitize_params_submitted( $data['course_description'] ?? '', 'html', false );
			$course_status         = LP_Helper::sanitize_params_submitted( $data['course_status'] ?? 'draft' );
			$course_visibility     = LP_Helper::sanitize_params_submitted( $data['course_visibility'] ?? '', 'key' );
			$course_password       = LP_Helper::sanitize_params_submitted( $data['course_password'] ?? '' );
			$course_post_date      = LP_Helper::sanitize_params_submitted( $data['course_post_date'] ?? '' );
			$course_permalink      = LP_Helper::sanitize_params_submitted( $data['course_permalink'] ?? '', 'sanitize_title' );
			$course_thumbnail_id   = LP_Helper::sanitize_params_submitted( $data['course_thumbnail_id'] ?? '', 'int' );
			$course_categories_str = LP_Helper::sanitize_params_submitted( $data['course_categories'] ?? '' );
			$course_categories     = array_map( 'absint', explode( ',', $course_categories_str ) );
			$course_tags_str       = LP_Helper::sanitize_params_submitted( $data['course_tags'] ?? '' );
			$course_tags           = array_map( 'absint', explode( ',', $course_tags_str ) );

			if ( ! $userModel instanceof UserModel ) {
				throw new Exception( __( 'Invalid user.', 'learnpress' ) );
			}

			if ( empty( $course_title ) ) {
				throw new Exception( __( 'Course title is required.', 'learnpress' ) );
			}

			$status_allows = [
				PostModel::STATUS_PUBLISH,
				PostModel::STATUS_PENDING,
				PostModel::STATUS_DRAFT,
				PostModel::STATUS_PRIVATE,
				PostModel::STATUS_TRASH,
				PostModel::STATUS_FEATURE,
			];
			if ( ! in_array( $course_status, $status_allows ) ) {
				throw new Exception( __( 'Invalid course status.', 'learnpress' ) );
			}

			$lp_date_modified = new LP_Datetime( current_time( 'mysql' ) );

			$data_save = [
				'post_title'        => $course_title,
				'post_content'      => $course_description,
				'post_status'       => $course_status,
				'post_author'       => $userModel->get_id(),
				'post_modified'     => $lp_date_modified->format( LP_DateTime::$format ),
				'post_modified_gmt' => $lp_date_modified->to_gmt_string( $lp_date_modified ),
			];

			// Handle status
			if ( $course_visibility === PostModel::VISIBILITY_PASSWORD ) {
				if ( ! empty( $course_password ) ) {
					$data_save['post_password'] = $course_password;
				}
			}

			// Handle date
			if ( ! empty( $course_post_date ) ) {
				// Time by local
				$lp_date               = new LP_Datetime( $course_post_date );
				$lp_date_timestamp     = $lp_date->getTimestamp();
				$lp_date_now           = new LP_Datetime( current_time( 'mysql', 1 ) );
				$lp_date_now_timestamp = $lp_date_now->getTimestamp();

				// Check with time now and status
				if ( $lp_date_timestamp > $lp_date_now_timestamp && $course_status === PostModel::STATUS_PUBLISH ) {
					$data_save['post_status'] = PostModel::STATUS_FEATURE;
				} elseif ( $lp_date_timestamp <= $lp_date_now_timestamp && $course_status === PostModel::STATUS_FEATURE ) {
					$data_save['post_status'] = PostModel::STATUS_PUBLISH;
				}

				$data_save['post_date']     = $lp_date->format( LP_DateTime::$format );
				$data_save['post_date_gmt'] = $lp_date->to_gmt_string( $lp_date );
			}

			// Check visibility
			if ( $course_visibility === PostModel::STATUS_PRIVATE ) {
				$data_save['post_status'] = PostModel::STATUS_PRIVATE;
			}

			if ( ! $is_edit ) {
				// Create new course
				$coursePostModelNew = $courseService->create_info_main( $data_save );
				$courseModel        = new CourseModel( $coursePostModelNew );
				$course_id          = $courseModel->ID;
			} else {
				// Update course
				$coursePostModel = new CoursePostModel( $courseModel );
				foreach ( $data_save as $key => $value ) {
					if ( isset( $coursePostModel->{$key} ) ) {
						$coursePostModel->{$key} = $value;
					}
				}

				// Update permalink/slug if provided
				if ( ! empty( $course_permalink ) ) {
					$coursePostModel->post_name = $course_permalink;
				}

				if ( $settings ) {
					$courseBuilderAjax = new CourseBuilderAjax();
					$courseBuilderAjax->save_course_settings_to_model( $coursePostModel, $data );
				}

				$coursePostModel->save();

				$course_id = $courseModel->ID;

				$courseModel = CourseModel::find( $course_id, true );
			}

			// Set categories and tags
			$courseService->update_categories( $course_id, $course_categories );
			$courseService->update_tags( $course_id, $course_tags );

			// Save or remove thumbnail
			if ( isset( $data['course_thumbnail_id'] ) ) {
				$post = new WP_Post( $courseModel );
				if ( $course_thumbnail_id > 0 ) {
					set_post_thumbnail( $post, $course_thumbnail_id );
				} else {
					delete_post_thumbnail( $post );
				}
			}

			ob_start();
			$data_edit_course_html = [
				'userModel' => $userModel,
				'item_id'   => $course_id,
			];
			BuilderEditCourseTemplate::instance()->layout( $data_edit_course_html );
			$html_edit_course = ob_get_clean();

			$response->status     = 'success';
			$response->message    = ! $is_edit ?
				__( 'Create course successfully!', 'learnpress' ) :
				__( 'Update course successfully!', 'learnpress' );
			$response->data->html = $html_edit_course;

			// Return redirect detail if new course
			if ( ! $is_edit && $course_id ) {
				$response->data->redirect_url = CourseBuilder::get_link_course_builder(
					CourseBuilderTemplate::MENU_COURSES . "/{$course_id}"
				);
			}
		} catch ( Throwable $th ) {
			$response->message = $th->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Quick edit course.
	 *
	 * @return void
	 */
	public static function cb_quick_edit_save_course() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();
		$courseService  = CourseService::instance();

		try {
			$data = self::check_valid_course();
			/** @var CourseModel $courseModel */
			$courseModel = $data['courseModel'] ?? null;
			$userModel   = $data['userModel'] ?? null;
			$action_type = $data['action_type'] ?? null;
			if ( ! $action_type || ! $courseModel instanceof CourseModel ) {
				throw new Exception( __( 'Invalid request.', 'learnpress' ) );
			}

			$coursePostModel = new CoursePostModel( $courseModel );

			switch ( $action_type ) {
				case 'duplicate':
					//$courseService->duplicate( $courseModel );
					// Use old class temporarily
					include_once LP_PLUGIN_PATH . 'inc/admin/class-lp-admin.php';
					$course_curd                  = new LP_Course_CURD();
					$course_id                    = $courseModel->get_id();
					$course_id_new                = $course_curd->duplicate( $course_id );
					$response->data->redirect_url = CourseBuilder::get_link_course_builder(
						CourseBuilderTemplate::MENU_COURSES . "/{$course_id_new}"
					);
					$response->message            = __( 'Duplicate course successfully. Redirecting...!', 'learnpress' );
					break;
				case PostModel::STATUS_TRASH:
				case PostModel::STATUS_PUBLISH:
				case PostModel::STATUS_PENDING:
				case PostModel::STATUS_DRAFT:
					$coursePostModel->post_status = $action_type;
					$coursePostModel->save();
					$courseModel          = CourseModel::find( $coursePostModel->get_id(), true );
					$response->message    = __( 'Update course status successfully!', 'learnpress' );
					$response->data->html = BuilderListCoursesTemplate::render_course( $courseModel );
					break;
				case 'restore':
					$coursePostModel->post_status = PostModel::STATUS_DRAFT;
					$coursePostModel->save();
					$courseModel          = CourseModel::find( $coursePostModel->get_id(), true );
					$response->message    = __( 'Restore course successfully!', 'learnpress' );
					$response->data->html = BuilderListCoursesTemplate::render_course( $courseModel );
					break;
				case 'delete':
					if ( $courseModel->get_status() !== PostModel::STATUS_TRASH ) {
						throw new Exception( __( 'Course must be trashed before deleting.', 'learnpress' ) );
					}
					$coursePostModel->delete();
					$response->message = __( 'Delete course successfully!', 'learnpress' );
					break;
			}

			$response->status = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
