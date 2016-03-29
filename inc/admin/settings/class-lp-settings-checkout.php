<?php

/**
 * Class LP_Settings_Checkout
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes/Settings
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Settings_Checkout extends LP_Settings_Base {

	/**
	 * Constructor
	 */
	function __construct() {
		$this->id   = 'checkout';
		$this->text = __( 'Checkout', 'learnpress' );

		parent::__construct();
	}

	/**
	 * Tab's sections
	 *
	 * @return mixed
	 */
	function get_sections() {
		$sections = array(
			'general' => array(
				'id'    => 'general',
				'title' => __( 'General', 'learnpress' )
			)
		);
		return $sections = apply_filters( 'learn_press_settings_sections_' . $this->id, $sections );
	}

	function output_section_general() {
		$view = learn_press_get_admin_view( 'settings/checkout.php' );
		include_once $view;
	}

	function get_settings() {
		return apply_filters(
			'learn_press_checkout_settings',
			array(
				array(
					'title'   => __( 'Enable cart', 'learnpress' ),
					'desc'    => __( 'Check this option to enable user can purchase multiple course at one time', 'learnpress' ),
					'id'      => $this->get_field_name( 'enable_cart' ),
					'default' => 'yes',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'Add to cart redirect', 'learnpress' ),
					'desc'    => __( 'Redirect to checkout immediately after add course to cart', 'learnpress' ),
					'id'      => $this->get_field_name( 'redirect_after_add' ),
					'default' => 'yes',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'AJAX add to cart', 'learnpress' ),
					'desc'    => __( 'Using AJAX to add course to the cart', 'learnpress' ),
					'id'      => $this->get_field_name( 'ajax_add_to_cart' ),
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'No checkout free course', 'learnpress' ),
					'desc'    => __( 'Check this option to enable user can enroll course immediate without add to cart', 'learnpress' ),
					'id'      => $this->get_field_name( 'no_checkout_free_course' ),
					'default' => 'yes',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'Cart page', 'learnpress' ),
					'id'      => $this->get_field_name( 'cart_page_id' ),
					'default' => '',
					'type'    => 'pages-dropdown'
				),
				array(
					'title'   => __( 'Checkout page', 'learnpress' ),
					'id'      => $this->get_field_name( 'checkout_page_id' ),
					'default' => '',
					'type'    => 'pages-dropdown'
				),
				array(
					'title' => __( 'Checkout Endpoints', 'learnpress' ),
					'type'  => 'title'
				),
				array(
					'title'   => __( 'Order received', 'learnpress' ),
					'id'      => $this->get_field_name( 'checkout_endpoints[lp_order_received]' ),
					'default' => 'lp-order-received',
					'type'    => 'text'
				),
			)
		);
	}
}

//
return new LP_Settings_Checkout();