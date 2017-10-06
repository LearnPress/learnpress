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

			$this->default_subject                = __( 'New order placed on {{order_date}}', 'learnpress' );
			$this->default_heading                = __( 'New user order', 'learnpress' );
			$this->email_text_message_description = sprintf( '%s {{order_number}}, {{order_total}}, {{order_items_table}}, {{order_view_url}}, {{user_email}}, {{user_name}}, {{user_profile_url}}', __( 'Shortcodes', 'learnpress' ) );

			$this->recipients = get_option( 'admin_email' );
			$this->recipient  = LP()->settings->get( 'emails_' . $this->id . '.recipients', $this->recipients );

			$this->support_variables = array_merge(
				$this->general_variables,
				array(
					'{{order_id}}',
					'{{order_user_id}}',
					'{{order_user_name}}',
					'{{order_items_table}}',
					'{{order_edit_url}}',
					'{{order_number}}',
				)
			);


//			add_action( 'learn_press_order_status_pending_to_processing_notification', array( $this, 'trigger' ) );
//			add_action( 'learn_press_order_status_pending_to_completed_notification', array( $this, 'trigger' ) );
//			add_action( 'learn_press_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ) );
//
//			add_action( 'learn_press_order_status_failed_to_processing_notification', array( $this, 'trigger' ) );
//			add_action( 'learn_press_order_status_failed_to_completed_notification', array( $this, 'trigger' ) );
//			add_action( 'learn_press_order_status_failed_to_on-hold_notification', array( $this, 'trigger' ) );

			add_action( 'learn-press/order-status-pending-to-processing_status', array( $this, 'trigger' ) );
			add_action( 'learn-press/order-status-pending-completed-status', array( $this, 'trigger' ) );
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
			$this->order_id = $order_id;
			if ( ! $this->enable ) {
				return false;
			}

			$this->get_object();
			$this->get_variable();

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

return new LP_Email_New_Order_Admin();