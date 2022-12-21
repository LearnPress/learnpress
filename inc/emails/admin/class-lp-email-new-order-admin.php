<?php
/**
 * Email for admin when has new order.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.1
 * @editor tungnx
 * @modify 4.1.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_New_Order_Admin' ) ) {
	class LP_Email_New_Order_Admin extends LP_Email_Type_Order_Admin {

		/**
		 * LP_Email_New_Order_Admin constructor.
		 */
		public function __construct() {
			$this->id          = 'new-order-admin';
			$this->title       = __( 'Admin', 'learnpress' );
			$this->description = __( 'Notify admin when a new order is placed.', 'learnpress' );

			$this->default_subject = __( 'New order placed on {{order_date}}', 'learnpress' );
			$this->default_heading = __( 'New user order', 'learnpress' );

			$this->recipients = get_option( 'admin_email' );
			$this->recipient  = LP_Settings::instance()->get( 'emails_' . $this->id . '.recipients', $this->_get_admin_email() );

			parent::__construct();
		}
	}

	return new LP_Email_New_Order_Admin();
}

