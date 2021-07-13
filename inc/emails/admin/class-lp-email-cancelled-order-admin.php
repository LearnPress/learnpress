<?php

/**
 * Class LP_Email_Cancelled_Order_Admin
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Cancelled_Order_Admin' ) ) {

	/**
	 * Class LP_Email_Cancelled_Order_Admin
	 */
	class LP_Email_Cancelled_Order_Admin extends LP_Email_Type_Order {
		/**
		 * LP_Email_Cancelled_Order_Admin constructor.
		 */
		public function __construct() {
			$this->id          = 'cancelled-order-admin';
			$this->title       = __( 'Admin', 'learnpress' );
			$this->description = __( 'Send email to admin when order has been cancelled.', 'learnpress' );

			$this->default_subject = __( 'Order placed on {{order_date}} has been cancelled', 'learnpress' );
			$this->default_heading = __( 'User order has been cancelled', 'learnpress' );

			$this->recipient = LP()->settings->get( 'emails_' . $this->id . '.recipients', $this->_get_admin_email() );

			parent::__construct();

			// Cancelled
			add_action( 'learn-press/order/status-cancelled/notification', array( $this, 'trigger' ) );
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

			$this->get_object();

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			return $return;
		}


		/**
		 * Get email plain template.
		 *
		 * @param string $format
		 *
		 * @return array|object
		 */
		public function get_template_data( $format = 'plain' ) {
			return $this->object;
		}
	}
}

return new LP_Email_Cancelled_Order_Admin();