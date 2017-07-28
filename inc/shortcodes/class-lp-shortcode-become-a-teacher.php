<?php

/**
 * Class LP_Checkout_Shortcode
 *
 * Shortcode to display the checkout form
 *
 * @since 3.x.x
 */
class LP_Shortcode_Become_A_Teacher extends LP_Abstract_Shortcode {

	/**
	 * LP_Checkout_Shortcode constructor.
	 *
	 * @param mixed $atts
	 */
	public function __construct( $atts = '' ) {
		parent::__construct( $atts );
	}

	/**
	 * Output form.
	 *
	 * @return string
	 */
	public function output() {

		global $wp;
		ob_start();

		$user    = learn_press_get_current_user();
		$message = '';
		$code    = 0;

		$atts = $this->get_atts();

		if ( ! is_user_logged_in() ) {
			$message = __( "Please login to fill in this form.", 'learnpress' );
			$code    = 1;
		} elseif ( in_array( LP_TEACHER_ROLE, $user->user->roles ) ) {
			$message = __( "You are a teacher now.", 'learnpress' );
			$code    = 2;
		} elseif ( get_transient( 'learn_press_become_teacher_sent_' . $user->id ) == 'yes' ) {
			$message = __( 'Your request has been sent! We will get in touch with you soon!', 'learnpress' );
			$code    = 3;
		} elseif ( learn_press_user_maybe_is_a_teacher() ) {
			$message = __( 'Your role is allowed to create a course.', 'learnpress' );
			$code    = 4;
		}

		if ( ! apply_filters( 'learn_press_become_a_teacher_display_form', true, $code, $message ) ) {

			$atts   = shortcode_atts(
				array(
					'method'                     => 'post',
					'action'                     => '',
					'title'                      => __( 'Become a Teacher', 'learnpress' ),
					'description'                => __( 'Fill in your information and send us to become a teacher.', 'learnpress' ),
					'submit_button_text'         => __( 'Submit', 'learnpress' ),
					'submit_button_process_text' => __( 'Processing', 'learnpress' )
				),
				$atts
			);
			$fields = learn_press_get_become_a_teacher_form_fields();
			ob_start();
			$args = array_merge(
				array(
					'fields'  => $fields,
					'code'    => $code,
					'message' => $message
				),
				$atts
			);

			learn_press_get_template( 'global/become-teacher-form.php', $args );
		}
		return ob_get_clean();
	}

	/**
	 * Output received order information.
	 *
	 * @param int $order_id
	 */
	private function _order_received( $order_id = 0 ) {
		// Get the order
		$order_id  = absint( $order_id );
		$order_key = ! empty( $_GET['key'] ) ? $_GET['key'] : '';

		if ( $order_id > 0 && ( $order = learn_press_get_order( $order_id ) ) && $order->get_data( 'post_status' ) != 'trash' ) {
			if ( $order->order_key != $order_key ) {
				unset( $order );
			}
		} else {
			learn_press_display_message( __( 'Invalid order!', 'learnpress' ), 'error' );

			return;
		}

		LP()->session->order_awaiting_payment = null;

		learn_press_get_template( 'checkout/order-received.php', array( 'order' => $order ) );
	}
}