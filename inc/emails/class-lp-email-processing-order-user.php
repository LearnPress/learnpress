<?php
/**
 * LP_Email_Processing_Order_User.
 *
 * @author  ThimPress
 * @package Learnpress/Classes
 * @extends LP_Email_Type_Order
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Processing_Order_User' ) ) {
	/**
	 * Class LP_Email_Processing_Order_User
	 */
	class LP_Email_Processing_Order_User extends LP_Email_Type_Order {
		/**
		 * LP_Email_Processing_Order_User constructor.
		 */
		public function __construct() {
			$this->id          = 'processing-order-user';
			$this->title       = __( 'User', 'learnpress' );
			$this->description = __( 'Notify users when their course orders are in processing.', 'learnpress' );

			$this->default_subject = __( 'Your order placed on {{order_date}}', 'learnpress' );
			$this->default_heading = __( 'Thank you for your order', 'learnpress' );

			parent::__construct();

			add_action( 'learn-press/order/status-pending-to-processing/notification', array( $this, 'trigger' ) );
		}

		/**
		 * Trigger Email Notification
		 *
		 * @param int $order_id
		 *
		 * @return boolean
		 */
		public function trigger( $order_id ) {
			parent::trigger( $order_id );

			if ( ! $this->enable ) {
				return false;
			}

			$order = learn_press_get_order( $order_id );

			if ( $order->is_guest() ) {
				return false;
			}

			$this->recipient = $order->get_user_email();

			if ( ! $this->recipient ) {
				return false;
			}

			$this->get_object();

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), array(), $this->get_attachments() );

			return $return;
		}
	}
}

return new LP_Email_Processing_Order_User();