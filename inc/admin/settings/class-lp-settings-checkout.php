<?php

/**
 * Class LP_Settings_Checkout
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes/Settings
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Settings_Checkout extends LP_Settings_Base {

	/**
	 * Constructor
	 */
	function __construct() {
		$this->id   = 'checkout';
		$this->text = __( 'Checkout', 'learn_press' );

		parent::__construct();
	}

	/**
	 * Tab's sections
	 *
	 * @return mixed
	 */
	function get_sections() {
		$sections = array(
			'general' => __( 'General', 'learn_press' )
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
					'title'    => __( 'Enable cart', 'learn_press' ),
					'desc'     => __( 'Check this option to enable user can purchase multiple course at one time', 'learn_press' ),
					'id'       => $this->get_field_name( 'enable_cart' ),
					'default'  => 'no',
					'type'     => 'checkbox'
				),
				array(
					'title'    => __( 'Add to cart redirect', 'learn_press' ),
					'desc'     => __( 'Redirect to checkout immediately after add course to cart', 'learn_press' ),
					'id'       => $this->get_field_name( 'redirect_after_add' ),
					'default'  => 'yes',
					'type'     => 'checkbox'
				),
				array(
					'title'    => __( 'AJAX add to cart', 'learn_press' ),
					'desc'     => __( 'Using AJAX to add course to the cart', 'learn_press' ),
					'id'       => $this->get_field_name( 'ajax_add_to_cart' ),
					'default'  => 'no',
					'type'     => 'checkbox'
				),
				array(
					'title'    => __( 'Cart page', 'learn_press' ),
					'id'       => $this->get_field_name( 'cart_page_id' ),
					'default'  => '',
					'type'     => 'pages-dropdown'
				),
				array(
					'title'    => __( 'Checkout page', 'learn_press' ),
					'id'       => $this->get_field_name( 'checkout_page_id' ),
					'default'  => '',
					'type'     => 'pages-dropdown'
				),
				array(
					'title' => __( 'Checkout Endpoints', 'learn_press' ),
					'type'  => 'title'
				),
				array(
					'title'    => __( 'Order received', 'learn_press' ),
					'id'       => $this->get_field_name( 'checkout_endpoints[order_received]' ),
					'default'  => 'order-received',
					'type'     => 'text'
				),
			)
		);
	}
}
//
return new LP_Settings_Checkout();