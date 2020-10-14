<?php
/**
 * Class LP_Settings_Payment
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LP_Settings_Payments
 *
 * Manage all payments are registered and settings to control order process.
 *
 */
class LP_Settings_Payments extends LP_Abstract_Settings_Page {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id   = 'payments';
		$this->text = esc_html__( 'Payments', 'learnpress' );

		parent::__construct();

		add_filter( 'learn-press/admin/submenu-section-title', array( $this, 'custom_section_title' ), 10, 2 );
	}

	public function custom_section_title( $title, $slug ) {
		$sections = $this->get_sections();

		if ( ! empty( $sections[ $slug ] ) && $sections[ $slug ] instanceof LP_Gateway_Abstract ) {
			$title = $title . sprintf( '<span class="learn-press-tooltip" data-tooltip="%s"></span>', esc_attr( $sections[ $slug ]->get_method_description() ) );
		}

		return $title;
	}

	/**
	 * Get sections.
	 * Add a general section and each registered payment is a section.
	 *
	 * @return mixed
	 */
	public function get_sections() {
		static $sections;

		if ( ! $sections ) {
			$gateways = LP_Gateways::instance()->get_gateways();
			$sections = array(
				'general' => esc_html__( 'General', 'learnpress' ),
			);

			if ( $gateways ) {
				foreach ( $gateways as $id => $gateway ) {
					$sections[ $id ] = $gateway;
				}
			}
		}

		return $sections;
	}

	/**
	 * Settings fields of general section.
	 *
	 * @return mixed
	 */
	public function get_settings_general() {
		$checkout_url = learn_press_get_checkout_url();

		$guest = apply_filters(
			'learn-press/payment-settings/general',
			array(
				array(
					'type' => 'title',
				),
				array(
					'title'   => esc_html__( 'Guest checkout', 'learnpress' ),
					'id'      => 'guest_checkout',
					'default' => 'no',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Enable guest checkout', 'learnpress' ),
				),
				array(
					'title'   => esc_html__( 'Account login', 'learnpress' ),
					'id'      => 'enable_login_checkout',
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Enable login form in checkout', 'learnpress' ),
				),
				array(
					'title'   => esc_html__( 'Account creation', 'learnpress' ),
					'id'      => 'enable_registration_checkout',
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Enable register form in checkout', 'learnpress' ),
				),
			)
		);

		$enpoints = apply_filters(
			'learn-press/payment-settings/checkout-endpoints',
			array(
				array(
					'title'       => esc_html__( 'Custom order slug', 'learnpress' ),
					'id'          => 'checkout_endpoints[lp_order_received]',
					'default'     => 'lp-order-received',
					'placeholder' => 'lp-order-received',
					'type'        => 'text',
					'desc'        => sprintf( 'e.g. %s', "{$checkout_url}<code>" . LP()->settings()->get( 'checkout_endpoints.lp_order_received', 'lp-order-received' ) . '</code>' ),
				),
			)
		);

		$payment = array(
			array(
				'title'   => esc_html__( 'Payments', 'learnpress' ),
				'id'      => 'payment_order',
				'default' => '',
				'type'    => 'payment-order',
			),
			array(
				'type' => 'sectionend',
			),
		);

		return apply_filters( 'learn-press/payment-settings', array_merge( $guest, $enpoints, $payment ) );
	}

	public function admin_page_settings( $section = null, $tab = null ) {
		$sections = array();
		$items    = LP_Admin_Menu::instance()->get_menu_items();

		if ( ! empty( $items['settings'] ) ) {
			$tab      = $items['settings']->get_active_tab();
			$section  = $items['settings']->get_active_section();
			$sections = $items['settings']->get_sections();
		}

		$section_data = ! empty( $sections[ $section ] ) ? $sections[ $section ] : false;

		if ( $section_data instanceof LP_Abstract_Settings ) {
			$section_data->admin_option_settings();
		} elseif ( is_callable( array( $this, 'admin_options_' . $section ) ) ) {
			call_user_func_array( array( $this, 'admin_options_' . $section ), array( $section, $tab ) );
		} else {
			do_action( 'learn-press/admin/setting-payments/admin-options-' . $section, $tab );
		}
	}

	/**
	 * Output admin option of general page.
	 *
	 * @param string $section
	 * @param string $tab
	 */
	public function admin_options_general( $section, $tab ) {
		parent::admin_page_settings( $section, $tab );
	}
}

return new LP_Settings_Payments();
