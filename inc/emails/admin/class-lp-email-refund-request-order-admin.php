<?php

/**
 * Class LP_Email_Refund_Request_Order_Admin
 *
 * @package LearnPress/Classes
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Refund_Request_Order_Admin' ) ) {
	class LP_Email_Refund_Request_Order_Admin extends LP_Email_Type_Order_Admin {
		/**
		 * LP_Email_Refund_Request_Order_Admin constructor.
		 */
		public function __construct() {
			$this->id              = 'refund-request-order-admin';
			$this->title           = __( 'Admin', 'learnpress' );
			$this->description     = __( 'Send an email to admin when a customer submits a refund request.', 'learnpress' );
			$this->default_subject = __( 'A refund request was submitted on {{order_date}}', 'learnpress' );
			$this->default_heading = __( 'A customer submitted a refund request', 'learnpress' );
			$this->recipient       = LP_Settings::instance()->get( 'emails_' . $this->id . '.recipients', $this->_get_admin_email() );

			parent::__construct();
		}
	}

	return new LP_Email_Refund_Request_Order_Admin();
}
