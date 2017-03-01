<?php
/**
 * Class LP_Email_User_Order_Changed_Status
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( ! class_exists( 'LP_Email_User_Order_Changed_Status' ) ) {

	class LP_Email_User_Order_Changed_Status extends LP_Email {

		/**
		 * LP_Email_User_Order_Changed_Status constructor.
		 */
		public function __construct () {

			$this->id                = 'user_order_changed_status';
			$this->title             = __( 'User order changed status', 'learnpress' );
			$this->template_html     = 'emails/user-order-changed-status.php';
			$this->template_plain    = 'emails/plain/user-order-changed-status.php';
			$this->default_subject   = __( 'Your order {{order_date}} has just been changed status', 'learnpress' );
			$this->default_heading   = __( 'Your order {{order_number}} has just been changed status', 'learnpress' );
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
				'{{order_status}}',
				'{{order_items_table}}',
				'{{order_detail_url}}',
				'{{order_number}}',
			);

			add_action( 'learn_press_update_order_status', array( $this, 'update_order_status' ), 10, 2 );
			parent::__construct();
		}

		public function admin_options ( $settings_class ) {
			$view = learn_press_get_admin_view( 'settings/emails/user-order-changed-status.php' );
			include_once $view;
		}

		public function update_order_status ( $new_status, $order_id ) {

			if ( empty( $new_status ) || $new_status == 'completed' || empty( $order_id ) ) {
				return;
			}

			$this->trigger( $new_status, $order_id );
		}

		public function trigger ( $new_status, $order_id ) {

			if ( ! $this->enable ) {
				return;
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
					'order_date'        => date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ),
					'order_status' => $new_status
				)
			);

			$this->variables = $this->data_to_variables( $this->object );

			$this->object['order'] = $order;

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			return $return;
		}

		public function get_recipient () {
			$user            = learn_press_get_user( $this->object['order']->user_id );
			$this->recipient = $user->user_email;

			return parent::get_recipient();
		}

		public function get_template_data ( $format = 'plain' ) {
			return $this->object;
		}
	}
}

return new LP_Email_User_Order_Changed_Status();
