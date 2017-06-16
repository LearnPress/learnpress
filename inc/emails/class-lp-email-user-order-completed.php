<?php

/**
 * Class LP_Email_User_Order_Completed
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_User_Order_Completed' ) ) {

	class LP_Email_User_Order_Completed extends LP_Email {
		/**
		 * LP_Email_User_Order_Completed constructor.
		 */
		public function __construct() {
			$this->id          = 'user_order_completed';
			$this->title       = __( 'User order completed', 'learnpress' );
			$this->description = __( 'Send email to user when the order is completed', 'learnpress' );

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

		/**
		 * Trigger email to send to users
		 *
		 * @param $order_id
		 *
		 * @return bool
		 */
		public function trigger( $order_id ) {

			if ( ! $this->enable ) {
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
					'order_detail_url'  => learn_press_user_profile_link( $order->user_id, 'orders' ),
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
						$r                                = $this->send( $data->email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
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

		/**
		 * Admin settings.
		 */
		public function get_settings() {
			return apply_filters(
				'learn-press/email-settings/user-order-completed/settings',
				array(
					array(
						'type'  => 'heading',
						'title' => $this->title,
						'desc'  => $this->description
					),
					array(
						'title'   => __( 'Enabled', 'learnpress' ),
						'type'    => 'yes-no',
						'default' => 'no',
						'id'      => 'emails_user_order_completed[enable]'
					),
					array(
						'title'      => __( 'Subject', 'learnpress' ),
						'type'       => 'text',
						'default'    => $this->default_subject,
						'id'         => 'emails_user_order_completed[subject]',
						'desc'       => sprintf( __( 'Email subject, default: <code>%s</code>', 'learnpress' ), $this->default_subject ),
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => 'emails_user_order_completed[enable]',
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
						'id'         => 'emails_user_order_completed[heading]',
						'desc'       => sprintf( __( 'Email heading, default: <code>%s</code>', 'learnpress' ), $this->default_heading ),
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => 'emails_user_order_completed[enable]',
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
						'id'                   => 'emails_user_order_completed[email_content]',
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
									'field'   => 'emails_user_order_completed[enable]',
									'compare' => '=',
									'value'   => 'yes'
								)
							)
						)
					),
				)
			);
		}
	}
}

return new LP_Email_User_Order_Completed();
