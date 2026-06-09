<?php

/**
 * Class LP_Email_Refunded_Order_Admin
 *
 * @package LearnPress/Classes
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Refunded_Order_Admin' ) ) {
	class LP_Email_Refunded_Order_Admin extends LP_Email_Type_Order_Admin {
		/**
		 * LP_Email_Refunded_Order_Admin constructor.
		 */
		public function __construct() {
			$this->id              = 'refunded-order-admin';
			$this->title           = __( 'Admin', 'learnpress' );
			$this->description     = __( 'Send an email to admin when the order has been refunded.', 'learnpress' );
			$this->default_subject = __( 'The order placed on {{order_date}} has been refunded', 'learnpress' );
			$this->default_heading = __( 'The user order has been refunded', 'learnpress' );
			$this->recipient       = LP_Settings::instance()->get( 'emails_' . $this->id . '.recipients', $this->_get_admin_email() );
			$this->template_html   = 'emails/refund/refunded-order-admin.php';

			parent::__construct();
		}
	}

	return new LP_Email_Refunded_Order_Admin();
}
