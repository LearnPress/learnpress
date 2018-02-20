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
	public function __construct() {
		$this->id   = 'checkout';
		$this->text = __( 'Checkout', 'learnpress' );

		parent::__construct();
	}

	/**
	 * Tab's sections
	 *
	 * @return mixed
	 */
	public function get_sections() {
		$sections = array(
			'general' => array(
				'id'    => 'general',
				'title' => __( 'General', 'learnpress' )
			)
		);
		return $sections = apply_filters( 'learn_press_settings_sections_' . $this->id, $sections );
	}

	public function output_section_general() {
		$view = learn_press_get_admin_view( 'settings/checkout.php' );
		include_once $view;
	}

	public function get_settings() {
		return apply_filters(
			'learn_press_checkout_settings',
			array(
				array(
					'title'   => __( 'Auto enroll', 'learnpress' ),
					'desc'    => __( 'Auto enroll a user after they buy a course.', 'learnpress' ),
					'id'      => $this->get_field_name( 'auto_enroll' ),
					'default' => 'yes',
					'type'    => 'checkbox'
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
			),
			$this
		);
	}
}

//
return new LP_Settings_Checkout();