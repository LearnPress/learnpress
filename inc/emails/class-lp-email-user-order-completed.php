<?php

/**
 * Class LP_Email_User_Order_Completed
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

if ( !class_exists( 'LP_Email_User_Order_Completed' ) ) {

	class LP_Email_User_Order_Completed extends LP_Email {
		/**
		 * LP_Email_User_Order_Completed constructor.
		 */
		public function __construct() {
			$this->id    = 'user_order_completed';
			$this->title = __( 'User order completed', 'learnpress' );

			$this->template_html  = 'emails/user-order-completed.php';
			$this->template_plain = 'emails/plain/user-order-completed.php';

			$this->default_subject = __( 'Your order {{order_date}} is completed', 'learnpress' );
			$this->default_heading = __( 'Your order {{order_number}} is completed', 'learnpress' );

			$this->support_variables = array(
				'{{site_url}}',
				'{{site_title}}',
				'{{site_admin_email}}',
				'{{site_admin_name}}',
				'{{login_url}}',
				'{{header}}',
				'{{footer}}',
				'{{email_heading}}',
				'{{footer_text}}',
				'{{order_id}}',
				'{{order_user_id}}',
				'{{order_user_name}}',
				'{{order_items_table}}',
				'{{order_detail_url}}',
				'{{order_number}}',
			);

			// $this->email_text_message_description = sprintf( '%s {{order_number}}, {{order_total}}, {{order_view_url}}, {{user_email}}, {{user_name}}, {{user_profile_url}}', __( 'Shortcodes', 'learnpress' ) );

			add_action( 'learn_press_order_status_completed_notification', array( $this, 'trigger' ) );

			parent::__construct();
		}

		public function admin_options( $settings_class ) {
			$view = learn_press_get_admin_view( 'settings/emails/user-order-completed.php' );
			include_once $view;
		}

		/**
		 * Trigger email to send to users
		 *
		 * @param $order_id
		 *
		 * @return bool
		 */
		public function trigger( $order_id ) {

			if ( !$this->enable ) {
				return false;
			}

			$format = $this->email_format == 'plain_text' ? 'plain' : 'html';
			$order  = learn_press_get_order( $order_id );

			$this->object = $this->get_common_template_data(
				$format,
				array(
					'order_id'          => $order_id,
					'order_user_id'     => $order->user_id,
					'order_user_name'   => $order->get_user_name(),
					'order_items_table' => learn_press_get_template_content( 'emails/' . ( $format == 'plain' ? 'plain/' : '' ) . 'order-items-table.php', array( 'order' => $order ) ),
					'order_detail_url'  => learn_press_user_profile_link( $order->user_id, 'orders' ),///$order->get_view_order_url(),
					'order_number'      => $order->get_order_number(),
					'order_subtotal'    => $order->get_formatted_order_subtotal(),
					'order_total'       => $order->get_formatted_order_total(),
					'order_date'        => date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) )
				)
			);

			if ( $order->is_multi_users() ) {
				if ( $recipient = $order->get_user_data() ) {

					foreach ( $recipient as $uid => $data ) {
						$this->object['order_user_id']    = $uid;
						$this->object['order_user_name']  = $data->name;
						$this->object['order_detail_url'] = learn_press_user_profile_link( $uid, 'orders' );
						$this->variables                  = $this->data_to_variables( $this->object );
						$r = $this->send( $data->email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
					}
				}
			} else {
				$this->variables       = $this->data_to_variables( $this->object );
				$this->object['order'] = $order;
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}
			return false;
		}

		/**
		 * Get email recipients
		 *
		 * @return mixed|void
		 */
		public function get_recipient() {
			if ( $order = $this->object['order'] ) {
				$this->recipient = $order->get_user_email();
			}
			return parent::get_recipient();
		}

		/**
		 * @param string $format
		 *
		 * @return array|void
		 */
		public function get_template_data( $format = 'plain' ) {
			return $this->object;

		}
	}
}

return new LP_Email_User_Order_Completed();
