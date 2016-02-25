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
	function __construct() {
		$this->id = 'new_order';
		$this->title = __( 'New order', 'learn_press' );

		$this->template_html  = 'emails/new-order.php';
		$this->template_plain = 'emails/plain/new-order.php';

		$this->subject = __( '[{site_title}] New course for review ({course_name}) - {course_date}', 'learn_press' );
		$this->heading = __( 'New course', 'learn_press' );

		add_action( 'learn_press_order_status_pending_to_processing_notification', array( $this, 'trigger' ) );
		add_action( 'learn_press_order_status_pending_to_completed_notification', array( $this, 'trigger' ) );
		add_action( 'learn_press_order_status_processing_to_completed_notification', array( $this, 'trigger' ) );

		parent::__construct();
	}

	function admin_options( $settings_class ){
		$view = learn_press_get_admin_view( 'settings/emails/new-order.php' );
		include_once $view;
	}

	function trigger( $order_id ) {

		if ( !$this->enable ) {
			return;
		}

		$this->find['site_title']  = '{site_title}';
		$this->find['course_name'] = '{course_name}';
		$this->find['course_date'] = '{course_date}';

		//$this->replace['site_title']  = $this->get_blogname();
		//$this->replace['course_name'] = get_the_title( $course_id );
		//$this->replace['course_date'] = get_the_date( null, $course_id );

		$this->object = array(
			'order' => $order_id
		);

		$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		LP_Debug::instance()->add( array( 'action' =>  learn_press_get_order( $order_id ) ) );
		return $return;
	}

	function get_content_html() {
		ob_start();
		learn_press_get_template( $this->template_html, array(
			'email_heading'    => $this->get_heading(),
			'site_title'       => $this->get_blogname(),
			'course_name'      => get_the_title( $this->object['course'] ),
			'course_date'      => get_the_date( $this->object['course'] ),
			'course_link'      => get_the_permalink( $this->object['course'] ),
			'course_edit_link' => get_edit_post_link( $this->object['course'] ),
			'plain_text'       => false
		) );
		return ob_get_clean();
	}

	function get_content_plain() {
		ob_start();
		learn_press_get_template( $this->template_plain, array(
			'email_heading'    => $this->get_heading(),
			'site_title'       => $this->get_blogname(),
			'course_name'      => get_the_title( $this->object['course'] ),
			'course_date'      => get_the_date( $this->object['course'] ),
			'course_link'      => get_the_permalink( $this->object['course'] ),
			'course_edit_link' => get_edit_post_link( $this->object['course'] ),
			'plain_text'       => true
		) );
		return ob_get_clean();
	}
}

return new LP_Email_New_Order();