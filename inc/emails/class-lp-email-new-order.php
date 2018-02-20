<?php

/**
 * Class LP_Email_New_Order
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();
if ( !class_exists( 'LP_Email_New_Order' ) ) {
	class LP_Email_New_Order extends LP_Email {
		/**
		 * LP_Email_New_Order constructor.
		 */
		public function __construct() {
			$this->id    = 'new_order';
			$this->title = __( 'New order', 'learnpress' );

			$this->template_html  = 'emails/new-order.php';
			$this->template_plain = 'emails/plain/new-order.php';

			$this->default_subject                = __( '[{{site_title}}] New order placed', 'learnpress' );
			$this->default_heading                = __( 'New order', 'learnpress' );
			$this->email_text_message_description = sprintf( '%s {{order_number}}, {{order_total}}, {{order_items_table}}, {{order_view_url}}, {{user_email}}, {{user_name}}, {{user_profile_url}}', __( 'Shortcodes', 'learnpress' ) );

			$this->recipient = LP()->settings->get( 'emails_' . $this->id . '.recipients', get_option( 'admin_email' ) );

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
				'{{order_edit_url}}',
				'{{order_number}}',
			);


			add_action( 'learn_press_order_status_pending_to_processing_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_pending_to_completed_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ) );

			add_action( 'learn_press_order_status_failed_to_processing_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_failed_to_completed_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_failed_to_on-hold_notification', array( $this, 'trigger' ) );

			parent::__construct();
		}

		public function admin_options( $settings_class ) {
			$view = learn_press_get_admin_view( 'settings/emails/new-order.php' );
			include_once $view;
		}

		public function trigger( $order_id ) {

			if ( !$this->enable ) {
				return;
			}

			/*$this->find['site_title']    = '{site_title}';
			$this->replace['site_title'] = $this->get_blogname();

			$this->object = array(
				'order' => learn_press_get_order( $order_id )
			);*/

			$format = $this->email_format == 'plain_text' ? 'plain' : 'html';
			$order  = learn_press_get_order( $order_id );

			$this->object = $this->get_common_template_data(
				$format,
				array(
					'order_id'          => $order_id,
					'order_user_id'     => $order->user_id,
					'order_user_name'   => $order->get_user_name(),
					'order_items_table' => learn_press_get_template_content( 'emails/' . ( $format == 'plain' ? 'plain/' : '' ) . 'order-items-table.php', array( 'order' => $order ) ),
					'order_edit_url'    => admin_url( 'post.php?post=' . $order->id . '&action=edit' ),
					'order_number'      => $order->get_order_number(),
					'order_subtotal'    => $order->get_formatted_order_subtotal(),
					'order_total'       => $order->get_formatted_order_total(),
					'order_date'        => date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) )
				)
			);

			$this->variables = $this->data_to_variables( $this->object );

			$this->object['order'] = $order;

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			return $return;
		}
		/*
			public function get_content_html() {
				ob_start();
				learn_press_get_template( $this->template_html, $this->get_template_data( 'html' ) );
				return ob_get_clean();
			}

			public function get_content_plain() {
				ob_start();
				learn_press_get_template( $this->template_plain, $this->get_template_data( 'plain' ) );
				return ob_get_clean();
			}

				public function _prepare_content_text_message() {
					$order = isset( $this->object['order'] ) ? $this->object['order'] : null;
					if ( $order ) {
						$this->text_search = array(
							"/\{\{order\_number\}\}/",
							"/\{\{order\_view\_url\}\}/",
							"/\{\{order\_total\}\}/",
							"/\{\{order\_items\_table\}\}/",
							"/\{\{user\_email\}\}/",
							"/\{\{user\_name\}\}/",
							"/\{\{user\_profile\_url\}\}/",
						);
						$this->text_replace = array(
							$order->get_order_number(),
							$order->get_view_order_url(),
							$order->get_formatted_order_total(),
							learn_press_get_template_content( 'emails/order-items-table.php', array( 'order' => $order ) ),
							$order->get_user( 'user_email' ),
							$order->get_customer_name(),
							learn_press_user_profile_link( $order->user_id )
						);
					}
				}
		*/
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

return new LP_Email_New_Order();