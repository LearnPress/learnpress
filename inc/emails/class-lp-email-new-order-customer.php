<?php

/**
 * Class LP_Email_New_Order_Customer
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_New_Order_Customer' ) ) {

	class LP_Email_New_Order_Customer extends LP_Email {

		/**
		 * LP_Email_New_Order_Customer constructor.
		 */
		public function __construct() {
			$this->id          = 'new_order_customer';
			$this->title       = __( 'New order customer', 'learnpress' );
			$this->description = __( 'Send email to the user who has bought course', 'learnpress' );

			$this->template_html  = 'emails/new-order-customer.php';
			$this->template_plain = 'emails/plain/new-order-customer.php';

			$this->default_subject = __( '[{{site_title}}] Order placed', 'learnpress' );
			$this->default_heading = __( 'Order placed', 'learnpress' );
			//$this->email_text_message_description = sprintf( '%s {{order_number}}, {{order_total}}, {{order_items_table}}, {{order_view_url}}, {{user_email}}, {{user_name}}, {{user_profile_url}}', __( 'Shortcodes', 'learnpress' ) );
//        $this->recipient = LP()->settings->get( 'emails_' . $this->id . '.recipients', get_option( 'admin_email' ) );

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


			add_action( 'learn_press_order_status_draft_to_pending_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_draft_to_processing_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_draft_to_on-hold_notification', array( $this, 'trigger' ) );

			add_action( "update_post_meta", array( $this, '_trigger' ), 10, 4 );
			parent::__construct();
		}

		/**
		 * Trigger email.
		 *
		 * @param $meta_id
		 * @param $object_id
		 * @param $meta_key
		 * @param $_meta_value
		 */
		public function _trigger( $meta_id, $object_id, $meta_key, $_meta_value ) {
			if ( get_post_type( $object_id ) != LP_ORDER_CPT ) {
				return;
			}
			if ( $meta_key != '_user_id' ) {
				return;
			}
			$this->trigger( $object_id );
		}

		/**
		 * Trigger Email Notification
		 *
		 * @param $order_id
		 *
		 * @return boolean
		 */
		public function trigger( $order_id ) {
			if ( ! $this->enable ) {
				return false;
			}

			$order           = learn_press_get_order( $order_id );
			$this->recipient = $order->get_user( 'user_email' );

			$items       = $order->get_items();
			$order_total = $order->order_total;
			/**
			 * Return if course is free because this order will be enrolled
			 *
			 * In this case we use email enrolled-course
			 */
			if ( ! $this->recipient || ( count( $items ) === 0 && floatval( $order_total ) == 0 ) ) {
				return false;
			}
			/**$this->find['site_title']    = '{site_title}';
			 * $this->replace['site_title'] = $this->get_blogname();*/

			$order  = learn_press_get_order( $order_id );

			$this->object = $this->get_common_template_data(
				$this->email_format,
				array(
					'order_id'          => $order_id,
					'order_user_id'     => $order->user_id,
					'order_user_name'   => $order->get_user_name(),
					'order_items_table' => learn_press_get_template_content( 'emails/' . ( $this->email_format == 'plain' ? 'plain/' : '' ) . 'order-items-table.php', array( 'order' => $order ) ),
					'order_detail_url'  => $order->get_view_order_url(),
					'order_number'      => $order->get_order_number(),
					'order_subtotal'    => $order->get_formatted_order_subtotal(),
					'order_total'       => $order->get_formatted_order_total(),
					'order_date'        => date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) )
				)
			);

			$this->variables = $this->data_to_variables( $this->object );

			$this->object['order'] = $order;

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), array(), $this->get_attachments() );

			return $return;
		}

		/**
		 * Get email template.
		 *
		 * @param string $format
		 *
		 * @return array|object
		 */
		public function get_template_data( $format = 'plain' ) {
			return $this->object;
		}

		/**
		 * Admin settings fields.
		 *
		 * @return mixed
		 */
		public function get_settings() {
			return apply_filters(
				'learn-press/email-settings/new-order-customer/settings',
				array(
					array(
						'type'  => 'heading',
						'title' => $this->title,
						'desc'  => $this->description
					),
					array(
						'title'   => __( 'Enable', 'learnpress' ),
						'type'    => 'yes-no',
						'default' => 'no',
						'id'      => 'emails_new_order_customer[enable]'
					),
					array(
						'title'      => __( 'Subject', 'learnpress' ),
						'type'       => 'text',
						'default'    => $this->default_subject,
						'id'         => 'emails_new_order_customer[subject]',
						'desc'       => sprintf( __( 'Email subject, default: <code>%s</code>', 'learnpress' ), $this->default_subject ),
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => 'emails_new_order_customer[enable]',
									'compare' => '=',
									'value'   => 'yes'
								)
							)
						)
					),
					array(
						'title'      => __( 'Heading', 'learnpress' ),
						'type'       => 'text',
						'default'    => $this->default_heading,
						'id'         => 'emails_new_order_customer[heading]',
						'desc'       => sprintf( __( 'Email heading, default: <code>%s</code>', 'learnpress' ), $this->default_heading ),
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => 'emails_new_order_customer[enable]',
									'compare' => '=',
									'value'   => 'yes'
								)
							)
						)
					),
					array(
						'title'                => __( 'Email content', 'learnpress' ),
						'type'                 => 'email-content',
						'default'              => '',
						'id'                   => 'emails_new_order_customer[email_content]',
						'template_base'        => $this->template_base,
						'template_path'        => $this->template_path,//default learnpress
						'template_html'        => $this->template_html,
						'template_plain'       => $this->template_plain,
						'template_html_local'  => $this->get_theme_template_file( 'html', $this->template_path ),
						'template_plain_local' => $this->get_theme_template_file( 'plain', $this->template_path ),
						'support_variables'    => $this->get_variables_support(),
						'visibility'           => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => 'emails_new_order_customer[enable]',
									'compare' => '=',
									'value'   => 'yes'
								)
							)
						)
					)
				)
			);
		}
	}
}

return new LP_Email_New_Order_Customer();