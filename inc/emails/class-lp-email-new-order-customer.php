<?php

/**
 * Class LP_Email_New_Order_Customer
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
defined( 'ABSPATH' ) || exit();

class LP_Email_New_Order_Customer extends LP_Email {

	/**
	 * LP_Email_New_Order_Customer constructor.
	 */
	public function __construct() {
		$this->id    = 'new_order_customer';
		$this->title = __( 'New order customer', 'learnpress' );

		$this->template_html  = 'emails/new-order-customer.php';
		$this->template_plain = 'emails/plain/new-order-customer.php';

		$this->default_subject = __( '[{site_title}] Order placed', 'learnpress' );
		$this->default_heading = __( 'Order placed', 'learnpress' );
                $this->email_text_message_description = sprintf( '%s [order_number], [order_total], [order_view_url], [user_email], [user_name], [user_profile_url]', __( 'Shortcodes', 'learnpress' ) );
//        $this->recipient = LP()->settings->get( 'emails_' . $this->id . '.recipients', get_option( 'admin_email' ) );

		add_action( 'learn_press_order_status_draft_to_pending_notification', array( $this, 'trigger' ) );
		add_action( 'learn_press_order_status_draft_to_processing_notification', array( $this, 'trigger' ) );
		add_action( 'learn_press_order_status_draft_to_on-hold_notification', array( $this, 'trigger' ) );

		add_action( "update_post_meta", array( $this, '_trigger' ), 10, 4 );
		parent::__construct();
	}

	public function _trigger( $meta_id, $object_id, $meta_key, $_meta_value ) {
		if ( get_post_type( $object_id ) != LP_ORDER_CPT ) {
			return;
		}
		if ( $meta_key != '_user_id' ) {
			return;
		}
		$this->trigger( $object_id );
	}

	public function admin_options( $settings_class ) {
		$view = learn_press_get_admin_view( 'settings/emails/new-order-customer.php' );
		include_once $view;
	}

	/**
	 * Trigger Email Notification
	 *
	 * @param type $order_id
	 *
	 * @return boolean
	 */
	public function trigger( $order_id ) {
		if ( !$this->enable ) {
			return;
		}

		$order           = learn_press_get_order( $order_id );
		$this->recipient = $order->get_user( 'user_email' );

                $items = $order->get_items();
                $order_total = $order->order_total;
                /**
                 * Return if course is free because this order will be enrolled
                 * 
                 * In this case we use email enrolled-course
                 */
		if ( !$this->recipient || ( count( $items ) === 0 && floatval( $order_total ) == 0 ) ) {
			return;
		}
		$this->find['site_title']    = '{site_title}';
		$this->replace['site_title'] = $this->get_blogname();

		$this->object = array(
			'order' => learn_press_get_order( $order_id )
		);
		$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), array(), $this->get_attachments() );

		return $return;
	}

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
                    "/\[order\_number\]/",
                    "/\[order\_view\_url\]/",
                    "/\[order\_total\]/",
                    "/\[user\_email\]/",
                    "/\[user\_name\]/",
                    "/\[user\_profile\_url\]/",
                );
                $this->text_replace = array(
                    $order->get_order_number(),
                    $order->get_view_order_url(),
                    $order->get_formatted_order_total(),
                    $order->get_user( 'user_email' ),
                    $order->get_customer_name(),
                    learn_press_user_profile_link( $order->user_id )
                );
            }
        }

	/**
	 * @param string $format
	 *
	 * @return array|void
	 */
	public function get_template_data( $format = 'plain' ) {
		return array(
			'email_heading' => $this->get_heading(),
			'footer_text'   => $this->get_footer_text(),
			'site_title'    => $this->get_blogname(),
			'plain_text'    => $format == 'plain',
			'order'         => $this->object['order']
		);
	}

}

return new LP_Email_New_Order_Customer();
