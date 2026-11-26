<?php
/**
 * Class UserCourseAjax
 *
 * Handle user actions on course: enroll, purchase, retake.
 * Replace REST API lp/v1/courses/enroll-course, purchase-course, retake-course.
 * JS (assets/src/js/frontend/single-course.js) reads data-send of buttons
 * (SingleCourseTemplate::html_btn_enroll_course, html_btn_purchase_course, UserCourseTemplate::html_btn_retake)
 * and calls window.lpAJAXG.fetchAJAX.
 *
 * Nonce 'wp_rest' and nonce of class ($action_nonce) are verified on AbstractAjax::catch_lp_ajax.
 *
 * @since 4.5.1
 * @version 1.0.0
 */

namespace LearnPress\Ajax\UserItem;

use Exception;
use LearnPress\Ajax\AbstractAjax;
use LearnPress\Helpers\Response;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LearnPress\Services\UserCourseService;
use LP_Debug;
use LP_Helper;
use LP_Request;
use Throwable;

defined( 'ABSPATH' ) || exit;

class UserCourseAjax extends AbstractAjax {
	/**
	 * Methods allowed to call via 'lp-load-ajax'.
	 *
	 * @var string[]
	 */
	protected static $actions = [
		'user_enroll_course',
		'user_purchase_course',
		'user_retake_course',
	];

	/**
	 * Nonce of class, printed on form retake (UserCourseTemplate::html_btn_retake),
	 * JS sends it via field 'lp_action_nonce' of 'data'.
	 *
	 * @var string
	 */
	protected static $action_nonce = 'lp_user_course_nonce';

	/**
	 * Verify nonce of class only for user logged in.
	 * Guest (enroll, purchase): nonce of guest is same for all guests (public), not protect anything,
	 * and HTML of guest can be cached (nonce expired). Same rule 'wp_rest' nonce on AbstractAjax::catch_lp_ajax.
	 *
	 * @return bool
	 * @since 4.5.1
	 */
	public static function verify_action_nonce(): bool {
		if ( ! is_user_logged_in() ) {
			return true;
		}

		return parent::verify_action_nonce();
	}

	/**
	 * User enroll course.
	 * Guest: course is added to cart, redirect to checkout page.
	 *
	 * @return void
	 * @since 4.5.1
	 * @version 1.0.0
	 */
	public function user_enroll_course() {
		$response = new Response();

		try {
			$courseModel = $this->get_course_model( $this->get_params() );
			$userModel   = UserModel::find( get_current_user_id(), true );

			$result = $this->get_user_course_service()->enroll( $courseModel, $userModel );

			$response->status         = Response::STATUS_SUCCESS;
			$response->message        = $result['message'];
			$response->data->redirect = esc_url_raw( $result['redirect'] );
		} catch ( Throwable $e ) {
			$response->message = $this->get_error_message( $e );
		}

		wp_send_json( $response );
	}

	/**
	 * User purchase course: add to cart, redirect to checkout page.
	 * Course allow repurchase (type popup): return HTML options, JS shows popup and sends again with 'repurchase_type'.
	 *
	 * @return void
	 * @since 4.5.1
	 * @version 1.0.0
	 */
	public function user_purchase_course() {
		$response = new Response();

		try {
			$params      = $this->get_params();
			$courseModel = $this->get_course_model( $params );
			$userModel   = UserModel::find( get_current_user_id(), true );

			// Fields of form purchase (addons can add), save to cart item.
			$item_data = $params['form_data'] ?? [];
			$item_data = is_array( $item_data ) ? map_deep( $item_data, 'sanitize_text_field' ) : [];
			unset( $item_data[ self::FIELD_ACTION_NONCE ] );

			$repurchase_type = $params['repurchase_type'] ?? '';
			$repurchase_type = is_string( $repurchase_type ) ? sanitize_key( $repurchase_type ) : '';

			$result = $this->get_user_course_service()->purchase( $courseModel, $userModel, $item_data, $repurchase_type );

			$response->status = Response::STATUS_SUCCESS;
			if ( ( $result['type'] ?? '' ) === 'allow_repurchase' ) {
				$response->data->type        = 'allow_repurchase';
				$response->data->html        = $result['html'];
				$response->data->title_popup = $result['title'];
			} else {
				$response->message        = $result['message'];
				$response->data->redirect = esc_url_raw( $result['redirect'] );
			}
		} catch ( Throwable $e ) {
			$response->message = $this->get_error_message( $e );
		}

		wp_send_json( $response );
	}

	/**
	 * User retake course.
	 *
	 * @return void
	 * @since 4.5.1
	 * @version 1.0.0
	 */
	public function user_retake_course() {
		$response = new Response();

		try {
			$courseModel = $this->get_course_model( $this->get_params() );

			$userModel = UserModel::find( get_current_user_id(), true );
			if ( ! $userModel instanceof UserModel ) {
				throw new Exception( esc_html__( 'You have to log in to retake the course.', 'learnpress' ) );
			}

			$userCourseModel = UserCourseModel::find( $userModel->get_id(), $courseModel->get_id(), true );
			if ( ! $userCourseModel instanceof UserCourseModel ) {
				throw new Exception( esc_html__( 'User not enrolled course!', 'learnpress' ) );
			}

			$userCourseModel->handle_retake();

			$item_continue = $userCourseModel->get_item_continue();
			if ( $item_continue ) {
				$url_redirect = $courseModel->get_item_link( $item_continue->ID );
			} else {
				$url_redirect = $courseModel->get_permalink();
			}

			$response->status             = Response::STATUS_SUCCESS;
			$response->message            = esc_html__( 'Now you can begin this course', 'learnpress' );
			$response->data->url_redirect = esc_url_raw( $url_redirect );
		} catch ( Throwable $e ) {
			$response->message = $this->get_error_message( $e );
		}

		wp_send_json( $response );
	}

	/**
	 * Get course from param 'course_id'.
	 *
	 * @param array $params
	 *
	 * @return CourseModel
	 * @throws Exception
	 * @since 4.5.1
	 * @version 1.0.0
	 */
	protected function get_course_model( array $params ): CourseModel {
		$course_id = absint( $params['course_id'] ?? 0 );
		if ( ! $course_id ) {
			throw new Exception( esc_html__( 'Params invalid!', 'learnpress' ) );
		}

		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel instanceof CourseModel ) {
			throw new Exception( esc_html__( 'The course is invalid', 'learnpress' ) );
		}

		return $courseModel;
	}

	/**
	 * @return UserCourseService
	 */
	protected function get_user_course_service(): UserCourseService {
		return UserCourseService::instance();
	}

	/**
	 * Message of Exception is shown to user,
	 * other errors (PHP Error...) are logged, not show to avoid exposing file paths.
	 *
	 * @param Throwable $e
	 *
	 * @return string
	 * @since 4.5.1
	 * @version 1.0.0
	 */
	protected function get_error_message( Throwable $e ): string {
		if ( $e instanceof Exception ) {
			return $e->getMessage();
		}

		LP_Debug::error_log( $e );

		return esc_html__( 'Something went wrong, please try again!', 'learnpress' );
	}

	/**
	 * Get params from request, key 'data' is JSON string sent by window.lpAJAXG.fetchAJAX.
	 *
	 * @return array
	 * @throws Exception
	 * @since 4.5.1
	 * @version 1.0.0
	 */
	protected function get_params(): array {
		$data   = LP_Request::get_param(
			'data',
			'',
			'wp_check_invalid_utf8'
		);
		$params = is_string( $data ) ? LP_Helper::json_decode( $data, true ) : null;
		if ( ! is_array( $params ) ) {
			throw new Exception( esc_html__( 'Params invalid!', 'learnpress' ) );
		}

		return $params;
	}
}
