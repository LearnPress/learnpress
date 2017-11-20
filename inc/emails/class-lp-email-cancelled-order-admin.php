<?php

/**
 * Class LP_Email_Cancelled_Order_Admin
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Cancelled_Order_Admin' ) ) {

	/**
	 * Class LP_Email_Cancelled_Order_Admin
	 */
	class LP_Email_Cancelled_Order_Admin extends LP_Email_Type_Order {
		/**
		 * LP_Email_Cancelled_Order_Admin constructor.
		 */
		public function __construct() {
			$this->id          = 'cancelled-order-admin';
			$this->title       = __( 'Admin', 'learnpress' );
			$this->description = __( 'Send email to admin when order has been cancelled', 'learnpress' );

			$this->default_subject = __( 'Order placed on {{order_date}} has been cancelled', 'learnpress' );
			$this->default_heading = __( 'User order has been cancelled', 'learnpress' );

			$this->recipient = LP()->settings->get( 'emails_' . $this->id . '.recipients', get_option( 'admin_email' ) );

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

			parent::trigger( $order_id );

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


		/**
		 * Admin settings.
		 */
		public function get_settings() {
			return apply_filters(
				'learn-press/email-settings/cancelled-order/settings',
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
						'title'      => __( 'Recipient(s)', 'learnpress' ),
						'type'       => 'text',
						'default'    => get_option( 'admin_email' ),
						'id'         => $this->get_field_name( 'recipients' ),
						'desc'       => sprintf( __( 'Email recipient(s) (separated by comma), default: <code>%s</code>', 'learnpress' ), get_option( 'admin_email' ) ),
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

return new LP_Email_Cancelled_Order_Admin();