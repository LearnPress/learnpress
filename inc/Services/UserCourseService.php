<?php

namespace LearnPress\Services;

use Exception;
use LearnPress;
use LearnPress\Background\LPBackgroundAjax;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LP_Cart;
use LP_Checkout;
use LP_Datetime;
use LP_Helper;
use LP_Order;
use LP_Page_Controller;
use WP_Error;

/**
 * Class UserCourseService
 *
 * Handle logic business user enroll, purchase course.
 * Use for AJAX UserCourseAjax (web) and JWT API LP_REST_Courses_V1_Controller (app).
 * Logic moved from REST API lp/v1/courses/enroll-course, purchase-course.
 *
 * @since 4.5.1
 * @version 1.0.0
 */
class UserCourseService {
	use Singleton;

	/**
	 * Options repurchase course allowed.
	 */
	const REPURCHASE_TYPES = [ 'reset', 'keep' ];

	public function init() {}

	/**
	 * User enroll course.
	 * User bought course: set enrolled. Course free: add to cart, create order and complete it.
	 * Guest: add to cart, redirect to checkout page.
	 *
	 * @param CourseModel $courseModel
	 * @param UserModel|false $userModel
	 *
	 * @return array [ message, redirect ]
	 * @throws Exception
	 * @since 4.5.1
	 * @version 1.0.0
	 */
	public function enroll( CourseModel $courseModel, $userModel ): array {
		$course_id = $courseModel->get_id();
		$user_id   = $userModel instanceof UserModel ? $userModel->get_id() : 0;

		if ( post_password_required( $course_id )
			&& ! current_user_can( 'edit_post', $course_id ) ) {
			throw new Exception( esc_html__( 'This content is password protected.', 'learnpress' ) );
		}

		$can_enroll = $courseModel->can_enroll( $userModel );
		if ( $can_enroll instanceof WP_Error ) {
			throw new Exception( $can_enroll->get_error_message() );
		}

		// Case: user bought course.
		$userCourse = $user_id ? $this->find_user_course( $user_id, $course_id ) : false;
		if ( $userCourse instanceof UserCourseModel && $userCourse->has_purchased() ) {
			$userCourse->graduation = LP_COURSE_GRADUATION_IN_PROGRESS;
			$userCourse->status     = LP_COURSE_ENROLLED;
			$userCourse->start_time = gmdate( LP_Datetime::$format, time() );
			$userCourse->save();

			do_action( 'learnpress/user/course-enrolled', $userCourse->ref_id, $course_id, $user_id );

			/**
			 * Send mail user enrolled course
			 * @uses SendEmailAjax::send_mail_user_enrolled_course()
			 */
			$this->send_mail_enrolled( [ $userCourse->ref_id, $course_id, $user_id ] );
		} else { // Case enroll course free.
			$cart = $this->get_cart();
			$cart->empty_cart();

			$cart_id = $cart->add_to_cart( $course_id );
			if ( ! $cart_id ) {
				throw new Exception( esc_html__( 'Error: The course cannot be added to the cart.', 'learnpress' ) );
			}

			if ( is_user_logged_in() ) {
				$order_id = $this->get_checkout()->create_order();
				$order    = $this->get_order( $order_id );

				// Security: free enrollment must never complete a paid order.
				if ( floatval( $order->get_total() ) > 0 ) {
					$cart->empty_cart();
					throw new Exception( esc_html__( 'Invalid enrollment: the order total is not zero.', 'learnpress' ) );
				}

				$order->payment_complete();

				$cart->empty_cart();
			}
		}

		if ( is_user_logged_in() ) {
			$first_item_id = $courseModel->get_first_item_id();

			return [
				'message'  => esc_html__( 'Congrats! You have enrolled in the course successfully. Redirecting...', 'learnpress' ),
				'redirect' => $first_item_id ? $courseModel->get_item_link( $first_item_id ) : $courseModel->get_permalink(),
			];
		}

		$redirect_url = apply_filters(
			'learnpress/rest-api/courses/enroll/redirect',
			$this->get_checkout_url(),
			$course_id
		);

		return [
			'message'  => esc_html__( 'Redirecting...', 'learnpress' ),
			'redirect' => $this->redirect_url_no_cache( $redirect_url ),
		];
	}

	/**
	 * User purchase course: add to cart, redirect to checkout page.
	 * User purchased (course allow repurchase, type popup): return HTML options for user choose before add to cart.
	 *
	 * @param CourseModel $courseModel
	 * @param UserModel|false $userModel
	 * @param array $item_data data of form purchase (sanitized), save to cart item, key 'quantity' is quantity.
	 * @param string $repurchase_type 'reset' | 'keep', user chose on popup repurchase.
	 *
	 * @return array [ type => 'allow_repurchase', html, title ] or [ message, redirect ]
	 * @throws Exception
	 * @since 4.5.1
	 * @version 1.0.0
	 */
	public function purchase( CourseModel $courseModel, $userModel, array $item_data = [], string $repurchase_type = '' ): array {
		$course_id = $courseModel->get_id();
		$user_id   = $userModel instanceof UserModel ? $userModel->get_id() : 0;

		if ( '' !== $repurchase_type && ! in_array( $repurchase_type, self::REPURCHASE_TYPES, true ) ) {
			throw new Exception( esc_html__( 'Error: Invalid repurchase option.', 'learnpress' ) );
		}

		$can_purchase = $courseModel->can_purchase( $userModel );
		if ( $can_purchase instanceof WP_Error ) {
			throw new Exception( $can_purchase->get_error_message() );
		}

		$userCourse = $user_id ? $this->find_user_course( $user_id, $course_id ) : false;
		if ( $courseModel->enable_allow_repurchase()
			&& $userCourse
			&& '' === $repurchase_type
			&& 'popup' === $courseModel->get_type_repurchase() ) {
			return [
				'type'  => 'allow_repurchase',
				'html'  => $this->html_repurchase_options(),
				'title' => esc_html__( 'Repurchase Options', 'learnpress' ),
			];
		}

		$cart = $this->get_cart();
		if ( ! learn_press_enable_cart() ) {
			$cart->empty_cart();
		}

		$quantity = absint( $item_data['quantity'] ?? 1 );
		if ( $quantity <= 0 ) {
			$quantity = 1;
		}
		unset( $item_data['quantity'] );

		$cart_id = $cart->add_to_cart( $course_id, $quantity, $item_data );
		if ( empty( $cart_id ) ) {
			throw new Exception( esc_html__( 'Error: The course cannot be added to the cart.', 'learnpress' ) );
		}

		if ( '' !== $repurchase_type && $userCourse instanceof UserCourseModel ) {
			learn_press_update_user_item_meta( $userCourse->get_user_item_id(), '_lp_allow_repurchase_type', $repurchase_type );
		}

		$redirect_url = apply_filters(
			'learnpress/rest-api/courses/purchase/redirect',
			$this->get_checkout_url(),
			$course_id,
			$cart_id
		);

		return [
			'message'  => sprintf(
				/* translators: %s: course title */
				esc_html__( '"%s" has been added to your cart. Redirecting...', 'learnpress' ),
				$courseModel->get_title()
			),
			'redirect' => $this->redirect_url_no_cache( $redirect_url ),
		];
	}

	/**
	 * HTML options repurchase (radio), JS shows on popup.
	 *
	 * @return string
	 * @since 4.5.1
	 * @version 1.0.0
	 */
	public function html_repurchase_options(): string {
		$options = [
			'reset' => esc_html__( 'Reset course progress', 'learnpress' ),
			'keep'  => esc_html__( 'Keep course progress', 'learnpress' ),
		];

		$html_li = '';
		foreach ( $options as $key => $label ) {
			$html_li .= sprintf(
				'<li><label><input name="_lp_allow_repurchase_type" value="%s" type="radio"%s/> %s</label></li>',
				esc_attr( $key ),
				// Same old HTML (all options had checked, browser checked the last one).
				'keep' === $key ? ' checked="checked"' : '',
				$label
			);
		}

		return Template::combine_components(
			[
				'wrapper'     => '<div class="lp_allow_repurchase_select">',
				'ul'          => '<ul>',
				'list'        => $html_li,
				'ul_end'      => '</ul>',
				'wrapper_end' => '</div>',
			]
		);
	}

	/**
	 * Link no cache of checkout page (or link filtered).
	 *
	 * @param mixed $redirect_url
	 *
	 * @return string
	 * @throws Exception
	 */
	private function redirect_url_no_cache( $redirect_url ): string {
		if ( empty( $redirect_url ) || ! is_string( $redirect_url ) ) {
			throw new Exception( esc_html__( 'Error: Please set up a page for checkout.', 'learnpress' ) );
		}

		return esc_url_raw( $this->link_no_cache( $redirect_url ) );
	}

	/**
	 * @param string $link
	 *
	 * @return string
	 */
	protected function link_no_cache( string $link ): string {
		return LP_Helper::get_link_no_cache( $link );
	}

	/**
	 * @param int $user_id
	 * @param int $course_id
	 *
	 * @return UserCourseModel|false
	 */
	protected function find_user_course( int $user_id, int $course_id ) {
		return UserCourseModel::find( $user_id, $course_id, true );
	}

	/**
	 * @return LP_Cart
	 */
	protected function get_cart() {
		return LearnPress::instance()->cart;
	}

	/**
	 * @return LP_Checkout
	 */
	protected function get_checkout() {
		return LP_Checkout::instance();
	}

	/**
	 * @param int $order_id
	 *
	 * @return LP_Order
	 */
	protected function get_order( $order_id ) {
		return new LP_Order( $order_id );
	}

	/**
	 * @return string
	 */
	protected function get_checkout_url(): string {
		return LP_Page_Controller::get_link_page( 'checkout', [], true );
	}

	/**
	 * Send mail user enrolled course in background.
	 *
	 * @param array $params [ order_id, course_id, user_id ]
	 */
	protected function send_mail_enrolled( array $params ) {
		LPBackgroundAjax::handle(
			[
				'params'       => $params,
				'lp-load-ajax' => 'send_mail_user_enrolled_course',
			]
		);
	}
}
