<?php

/**
 * Class LP_Email_Cancelled_Order_User
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Cancelled_Order_User' ) ) {

	class LP_Email_Cancelled_Order_User extends LP_Email_Type_Order {
		/**
		 * LP_Email_Cancelled_Order_User constructor.
		 */
		public function __construct() {
			$this->id          = 'cancelled-order-user';
			$this->title       = __( 'Cancelled order user', 'learnpress' );
			$this->description = __( 'Send email to admin when order has been cancelled', 'learnpress' );

			$this->default_subject = __( 'Your order on {{order_date}} has been cancelled', 'learnpress' );
			$this->default_heading = __( 'Your order has been cancelled', 'learnpress' );

			add_action( 'learn_press_order_status_pending_to_failed_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_processing_to_failed_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_completed_to_failed_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_on-hold_to_failed_notification', array( $this, 'trigger' ) );

			parent::__construct();
		}

		/**
		 * Trigger email
		 *
		 * @param $order_id
		 *
		 * @return bool
		 */
		public function trigger( $order_id ) {
			parent::trigger( $order_id );

			if ( ! $this->enable ) {
				return false;
			}

			$order = $this->get_order();

			if ( $order->is_guest() ) {
				return false;
			}

			if ( ! $this->recipient = $order->get_user_email() ) {
				return false;
			}

			$this->get_object();
			$this->get_variable();

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			return $return;
		}
	}
}

return new LP_Email_Cancelled_Order_User();