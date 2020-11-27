<?php
/**
 * Class LP_Email_User_Order_Completed
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */
_deprecated_file( __FILE__, '3.0.0' );
die();
/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_User_Order_Completed' ) ) {

	/**
	 * Class LP_Email_User_Order_Completed
	 */
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

			$this->support_variables = array_merge( $this->general_variables, array(
				'{{order_id}}',
				'{{order_user_id}}',
				'{{order_user_name}}',
				'{{order_items_table}}',
				'{{order_detail_url}}',
				'{{order_number}}',
			) );


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

			$order = learn_press_get_order( $order_id );

			$this->object = $this->get_common_template_data(
				$this->email_format,
				array(
					'order_id'          => $order_id,
					'order_user_id'     => $order->user_id,
					'order_user_name'   => $order->get_user_name(),
					'order_items_table' => learn_press_get_template_content( 'emails/' . ( $this->email_format == 'plain' ? 'plain/' : '' ) . 'order-items-table.php', array( 'order' => $order ) ),
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
						'desc'       => sprintf( __( 'Email subject, default: <code>%s</code>.', 'learnpress' ), $this->default_subject ),
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
						'desc'       => sprintf( __( 'Email heading, default: <code>%s</code>.', 'learnpress' ), $this->default_heading ),
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
					),
				)
			);
		}
	}
}

return new LP_Email_User_Order_Completed();
