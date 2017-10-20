<?php

/**
 * Class LP_Email_New_Order_Admin
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_New_Order_Admin' ) ) {

	class LP_Email_New_Order_Admin extends LP_Email_Type_Order {
		/**
		 * LP_Email_New_Order_Admin constructor.
		 */
		public function __construct() {
			$this->id          = 'new-order-admin';
			$this->title       = __( 'New order admin', 'learnpress' );
			$this->description = __( 'Send email to admin when new order is placed', 'learnpress' );

			$this->default_subject = __( 'New order placed on {{order_date}}', 'learnpress' );
			$this->default_heading = __( 'New user order', 'learnpress' );

			$this->recipients = get_option( 'admin_email' );
			$this->recipient  = LP()->settings->get( 'emails_' . $this->id . '.recipients', $this->recipients );


//			add_action( 'learn_press_order_status_pending_to_processing_notification', array( $this, 'trigger' ) );
//			add_action( 'learn_press_order_status_pending_to_completed_notification', array( $this, 'trigger' ) );
//			add_action( 'learn_press_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ) );
//
//			add_action( 'learn_press_order_status_failed_to_processing_notification', array( $this, 'trigger' ) );
//			add_action( 'learn_press_order_status_failed_to_completed_notification', array( $this, 'trigger' ) );
//			add_action( 'learn_press_order_status_failed_to_on-hold_notification', array( $this, 'trigger' ) );

			add_action( 'learn-press/order/status-pending-to-processing_status', array( $this, 'trigger' ) );
			add_action( 'learn-press/order/status-pending-completed-status', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ) );

			add_action( 'learn_press_order_status_failed_to_processing_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_failed_to_completed_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_failed_to_on-hold_notification', array( $this, 'trigger' ) );

			parent::__construct();
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
			$this->get_variable();

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			return $return;
		}

	}
}

return new LP_Email_New_Order_Admin();