<?php

/**
 * Class LP_Email_Cancelled_Order_Admin
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 * @editor tungnx
 * @modify 4.1.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Cancelled_Order_Admin' ) ) {
	class LP_Email_Cancelled_Order_Admin extends LP_Email_Type_Order_Admin {
		/**
		 * LP_Email_Cancelled_Order_Admin constructor.
		 */
		public function __construct() {
			$this->id              = 'cancelled-order-admin';
			$this->title           = __( 'Admin', 'learnpress' );
			$this->description     = __( 'Send an email to admin when the order has been canceled.', 'learnpress' );
			$this->default_subject = __( 'The order placed on {{order_date}} has been cancelled', 'learnpress' );
			$this->default_heading = __( 'The user order has been cancelled', 'learnpress' );
			$this->recipient       = LP_Settings::instance()->get( 'emails_' . $this->id . '.recipients', $this->_get_admin_email() );

			parent::__construct();
		}
	}

	return new LP_Email_Cancelled_Order_Admin();
}
