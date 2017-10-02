<?php

/**
 * Class LP_Email_Processing_Order_Guest
 *
 * Send email to customer email in case they checkout as a guest.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.x.x
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Processing_Order_Guest' ) ) {

	class LP_Email_Processing_Order_Guest extends LP_Email {

		/**
		 * LP_Email_Processing_Order_Guest constructor.
		 */
		public function __construct() {
			$this->id          = 'processing-order-guest';
			$this->title       = __( 'Processing order Guest', 'learnpress' );
			$this->description = __( 'Send email to user who has purchased course as a Guest when the order is processing.', 'learnpress' );

			$this->template_html  = 'emails/new-order-guest.php';
			$this->template_plain = 'emails/plain/new-order-guest.php';

			$this->default_subject = __( '[{{site_title}}] Order placed', 'learnpress' );
			$this->default_heading = __( 'Order placed', 'learnpress' );

			$this->support_variables = array_merge( $this->general_variables, array(
				'{{order_id}}',
				'{{order_user_id}}',
				'{{order_user_name}}',
				'{{order_items_table}}',
				'{{order_detail_url}}',
				'{{order_number}}',
			) );

			add_action( 'learn_press_order_status_draft_to_pending_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_draft_to_processing_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_draft_to_on-hold_notification', array( $this, 'trigger' ) );

			add_action( 'learn-press/order/status-pending-to-processing/notification', array( $this, 'trigger' ) );

			parent::__construct();
		}

		/**
		 * Trigger Email Notification
		 *
		 * @param int $order_id
		 *
		 * @return boolean
		 */
		public function trigger( $order_id ) {
			if ( ! $this->enable ) {
				return false;
			}

			$order = learn_press_get_order( $order_id );

			if ( ! $order->is_guest() ) {
				return false;
			}

			$this->recipient = $order->get_user_email();

			$this->object['order'] = $order;

			/**
			 * Return if course is free because this order will be enrolled
			 *
			 * In this case we use email enrolled-course
			 */
			if ( ! $this->recipient || ( $order->get_total() === 0 ) ) {
				return false;
			}

			LP_Emails::instance()->set_current( $this->id );

			$this->object = $this->get_common_template_data(
				$this->email_format,
				array(
					'order_id'          => $order_id,
					'order_user_id'     => $order->get_user_id(),
					'order_user_name'   => $order->get_user_name(),
					'order_items_table' => learn_press_get_template_content( 'emails/' . ( $this->email_format == 'plain' ? 'plain/' : '' ) . 'order-items-table.php', array( 'order' => $order ) ),
					'order_detail_url'  => $order->get_view_order_url(),
					'order_number'      => $order->get_order_number(),
					'order_subtotal'    => $order->get_formatted_order_subtotal(),
					'order_total'       => $order->get_formatted_order_total(),
					'order_date'        => date_i18n( get_option( 'date_format' ), strtotime( $order->get_order_date() ) )
				)
			);

			$this->variables = $this->data_to_variables( $this->object );

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
				'learn-press/email-settings/new-order-guest/settings',
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
						'id'      => $this->get_field_name( 'enable' )
					),
					array(
						'title'      => __( 'Subject', 'learnpress' ),
						'type'       => 'text',
						'default'    => $this->default_subject,
						'id'         => $this->get_field_name( 'subject' ),
						'desc'       => sprintf( __( 'Email subject, default: <code>%s</code>', 'learnpress' ), $this->default_subject ),
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => $this->get_field_name( 'enable' ),
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
						'id'         => $this->get_field_name( 'heading' ),
						'desc'       => sprintf( __( 'Email heading, default: <code>%s</code>', 'learnpress' ), $this->default_heading ),
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => $this->get_field_name( 'enable' ),
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
						'id'                   => $this->get_field_name( 'email_content' ),
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
									'field'   => $this->get_field_name( 'enable' ),
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

return new LP_Email_Processing_Order_Guest();