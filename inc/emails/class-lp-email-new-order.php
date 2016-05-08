<?php

/**
 * Class LP_Email_New_Order
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Email_New_Order extends LP_Email {
	/**
	 * LP_Email_New_Order constructor.
	 */
	function __construct() {
		$this->id    = 'new_order';
		$this->title = __( 'New order', 'learnpress' );

		$this->template_html  = 'emails/new-order.php';
		$this->template_plain = 'emails/plain/new-order.php';

		$this->default_subject = __( '[{site_title}] New order placed', 'learnpress' );
		$this->default_heading = __( 'New order', 'learnpress' );

		$this->recipient = LP()->settings->get( 'emails_' . $this->id . '.recipients', get_option( 'admin_email' ) );

		add_action( 'learn_press_order_status_pending_to_processing_notification', array( $this, 'trigger' ) );
		add_action( 'learn_press_order_status_pending_to_completed_notification', array( $this, 'trigger' ) );
		add_action( 'learn_press_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ) );

		add_action( 'learn_press_order_status_failed_to_processing_notification', array( $this, 'trigger' ) );
		add_action( 'learn_press_order_status_failed_to_completed_notification', array( $this, 'trigger' ) );
		add_action( 'learn_press_order_status_failed_to_on-hold_notification', array( $this, 'trigger' ) );

		parent::__construct();
	}

	function admin_options( $settings_class ) {
		$view = learn_press_get_admin_view( 'settings/emails/new-order.php' );
		include_once $view;
	}

	function trigger( $order_id ) {

		if ( !$this->enable ) {
			return;
		}

		$this->find['site_title']    = '{site_title}';
		$this->replace['site_title'] = $this->get_blogname();

		$this->object = array(
			'order' => learn_press_get_order( $order_id )
		);

		$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		return $return;
	}

	function get_content_html() {
		ob_start();
		learn_press_get_template( $this->template_html, $this->get_template_data( 'html' ) );
		return ob_get_clean();
	}

	function get_content_plain() {
		ob_start();
		learn_press_get_template( $this->template_plain, $this->get_template_data( 'plain' ) );
		return ob_get_clean();
	}

	/**
	 * @param string $format
	 *
	 * @return array|void
	 */
	function get_template_data( $format = 'plain' ) {
		return array(
			'email_heading' => $this->get_heading(),
			'footer_text'   => $this->get_footer_text(),
			'site_title'    => $this->get_blogname(),
			'plain_text'    => $format == 'plain',
			'order'         => $this->object['order']
		);
	}
}

return new LP_Email_New_Order();