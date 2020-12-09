<?php
/**
 * Email for admin when has new order.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_New_Order_Admin' ) ) {

	/**
	 * Class LP_Email_New_Order_Admin
	 */
	class LP_Email_New_Order_Admin extends LP_Email_Type_Order {

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
			$this->recipient  = LP()->settings()->get( 'emails_' . $this->id . '.recipients', $this->_get_admin_email() );

			parent::__construct();

			// new free order
			// add_action( 'learn-press/order/status-pending-to-completed/notification', array( $this, 'trigger' ) );

			// email for new order
			add_action( 'learn-press/checkout-order-processed', array( $this, 'trigger' ) );

			// new paid order
			add_action( 'learn-press/order/status-pending-to-processing/notification', array( $this, 'trigger' ) );

			// remove complete order hook for free course ( default new free order auto create pending from pending to completed )
			remove_action( 'learn-press/order/status-completed/notification', array( $this, 'trigger' ) );

			add_action( 'init', array( $this, 'init' ) );
		}

		public function init() {
			// disable send mail for enable enroll course admin mail
			$email = LP_Emails::get_email( 'enrolled-course-admin' );

			if ( $email->enable() ) {
				remove_action(
					'learn-press/order/status-pending-to-completed/notification',
					array(
						$this,
						'trigger',
					)
				);
			}
		}

		/**
		 * Trigger email.
		 *
		 * @param int $order_id
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

	}
}

return new LP_Email_New_Order_Admin();
