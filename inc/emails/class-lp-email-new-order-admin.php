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

	class LP_Email_New_Order_Admin extends LP_Email {
		/**
		 * LP_Email_New_Order_Admin constructor.
		 */
		public function __construct() {
			$this->id          = 'new_order';
			$this->title       = __( 'New order admin', 'learnpress' );
			$this->description = __( 'Send email to admin when new order is placed', 'learnpress' );

			$this->template_html  = 'emails/new-order-admin.php';
			$this->template_plain = 'emails/plain/new-order-admin.php';

			$this->default_subject                = __( '[{{site_title}}] New order placed', 'learnpress' );
			$this->default_heading                = __( 'New order', 'learnpress' );
			$this->email_text_message_description = sprintf( '%s {{order_number}}, {{order_total}}, {{order_items_table}}, {{order_view_url}}, {{user_email}}, {{user_name}}, {{user_profile_url}}', __( 'Shortcodes', 'learnpress' ) );

			$this->recipient = LP()->settings->get( 'emails_' . $this->id . '.recipients', get_option( 'admin_email' ) );

			$this->support_variables = array_merge( $this->general_variables, array(
				'{{order_id}}',
				'{{order_user_id}}',
				'{{order_user_name}}',
				'{{order_items_table}}',
				'{{order_edit_url}}',
				'{{order_number}}',
			) );


			add_action( 'learn_press_order_status_pending_to_processing_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_pending_to_completed_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ) );

			add_action( 'learn_press_order_status_failed_to_processing_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_failed_to_completed_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_failed_to_on-hold_notification', array( $this, 'trigger' ) );

			parent::__construct();
		}

		/**
		 * Trigger email
		 *
		 * @param $order_id
		 *
		 * @return bool
		 */
		public function trigger( $order_id ) {

			if ( ! $this->enable ) {
				return false;
			}

			$order = learn_press_get_order( $order_id );

			$this->object = $this->get_common_template_data(
				$this->email_format,
				array(
					'order_id'          => $order_id,
					'order_user_id'     => $order->user_id,
					'order_user_name'   => $order->get_user_name(),
					'order_items_table' => learn_press_get_template_content( 'emails/' . ( $this->email_format == 'plain' ? 'plain/' : '' ) . 'order-items-table.php', array( 'order' => $order ) ),
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

		/**
		 * Admin settings.
		 */
		public function get_settings() {
			return apply_filters(
				'learn-press/email-settings/new-order/settings',
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
						'id'      => 'emails_new_order[enable]'
					),
					array(
						'title'      => __( 'Recipient(s)', 'learnpress' ),
						'type'       => 'text',
						'default'    => get_option( 'admin_email' ),
						'id'         => 'emails_new_order[recipients]',
						'desc'       => sprintf( __( 'Email recipient(s) (separated by comma), default: <code>%s</code>', 'learnpress' ), get_option( 'admin_email' ) ),
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => 'emails_new_order[enable]',
									'compare' => '=',
									'value'   => 'yes'
								)
							)
						)
					),
					array(
						'title'      => __( 'Subject', 'learnpress' ),
						'type'       => 'text',
						'default'    => $this->default_subject,
						'id'         => 'emails_new_order[subject]',
						'desc'       => sprintf( __( 'Email subject, default: <code>%s</code>', 'learnpress' ), $this->default_subject ),
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => 'emails_new_order[enable]',
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
						'id'         => 'emails_new_order[heading]',
						'desc'       => sprintf( __( 'Email heading, default: <code>%s</code>', 'learnpress' ), $this->default_heading ),
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => 'emails_new_order[enable]',
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
						'id'                   => 'emails_new_order[email_content]',
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
									'field'   => 'emails_new_order[enable]',
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

return new LP_Email_New_Order_Admin();