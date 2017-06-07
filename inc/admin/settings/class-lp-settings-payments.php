<?php

/**
 * Class LP_Settings_Payment
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Settings_Payments
 */
class LP_Settings_Payments extends LP_Abstract_Settings_Page {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id   = 'payments';
		$this->text = __( 'Payments', 'learnpress' );

		parent::__construct();
	}

	/**
	 * @return mixed
	 */
	public function get_sections() {
		$gateways = LP_Gateways::instance()->get_gateways();
		$sections = array(
			'general' => __( 'General', 'learnpress' )
		);
		if ( $gateways ) {
			foreach ( $gateways as $id => $gateway ) {
				$sections[ $id ] = $gateway;
			}
		}

		//$sections['payment_order'] = __('Payment order', 'learnpress');
		return $sections;
	}

	public function get_settings( $section = '', $tab = '' ) {
		if ( is_callable( array( $this, 'get_settings_' . $section ) ) ) {
			return call_user_func( array( $this, 'get_settings_' . $section ) );
		}

		return false;
	}

	public function get_settings_payment_order( $section = '', $tab = '' ) {
		return array(
			array(
				'title'   => __( 'Checkout page', 'learnpress' ),
				'id'      => $this->get_field_name( 'checkout_page_id' ),
				'default' => '',
				'type'    => 'pages-dropdown'
			)
        );
	}

	public function get_settings_general() {
		return apply_filters(
		    'learn-press/payment-settings',
			array_merge(
			    // General
				apply_filters(
					'learn-press/payment-settings/general',
					array(
						array(
							'title'   => __( 'Checkout page', 'learnpress' ),
							'id'      => $this->get_field_name( 'checkout_page_id' ),
							'default' => '',
							'type'    => 'pages-dropdown'
						),
						array(
							'title'   => __( 'Auto enroll', 'learnpress' ),
							'id'      => $this->get_field_name( 'auto_enroll' ),
							'default' => 'yes',
							'type'    => 'yes-no',
							'desc'    => __( 'Auto enroll a user after they buying a course.', 'learnpress' )
						),
						array(
							'title'   => __( 'Enable guest checkout', 'learnpress' ),
							'id'      => $this->get_field_name( 'guest_checkout' ),
							'default' => 'yes',
							'type'    => 'yes-no',
							'desc'    => __( 'Auto enroll a user after they buying a course.', 'learnpress' )
						)
					)
				),
				// Endpoint
				apply_filters(
					'learn-press/payment-settings/checkout-endpoints',
					array(
						array(
							'title'   => __( 'Endpoints', 'learnpress' ),
							'type' => 'heading',
						),
						array(
							'title'   => __( 'Order received', 'learnpress' ),
							'id'      => $this->get_field_name( 'checkout_endpoints[order_received]' ),
							'default' => '',
							'type'    => 'text'
						)
					)
				),
				array(
					array(
						'title'   => __( 'Payments', 'learnpress' ),
						'type' => 'heading',
                        'desc'=>__('All available payments are listed here. Drag and drop the payments to re-order.', 'learnpress')
					),
					array(
						'title'   => __( 'Payment order', 'learnpress' ),
						'id'      => $this->get_field_name( 'payment_order' ),
						'default' => '',
						'type'    => 'payment-order'
					)
				)
			)
		);
	}

	public function admin_page( $section = null, $tab = null ) {
		$sections = array();
		$items    = LP_Admin_Menu::instance()->get_menu_items();
		if ( ! empty( $items['settings'] ) ) {
			$tab      = $items['settings']->get_active_tab();
			$section  = $items['settings']->get_active_section();
			$sections = $items['settings']->get_sections();
		}
		$section_data = ! empty( $sections[ $section ] ) ? $sections[ $section ] : false;
		if ( $section_data instanceof LP_Abstract_Settings ) {
			$section_data->admin_options();
		} else if ( is_array( $section_data ) ) {
			print_r( $section_data );
		} else {
			if ( is_callable( array( $this, 'admin_options_' . $section ) ) ) {
				call_user_func_array( array( $this, 'admin_options_' . $section ), array(
					$section,
					$tab
				) );
			} else {
				do_action( 'learn-press/admin/setting-payments/admin-options-' . $section, $tab );
			}
		}
	}

	public function admin_options_general( $section, $tab ) {
		parent::admin_page( $section, $tab );
	}

	public function output() {
		$section = $this->section;
		?>
        <h3 class="learn-press-settings-title"><?php echo $this->section['title']; ?></h3>
		<?php if ( ! empty( $this->section['description'] ) ) : ?>
            <p class="description">
				<?php echo $this->section['description']; ?>
            </p>
		<?php endif; ?>
        <table class="form-table">
            <tbody>
			<?php
			if ( 'paypal' == $section['id'] ) {
				$this->output_section_paypal();
			} else {
				do_action( 'learn_press_section_' . $this->id . '_' . $section['id'] );
			}
			?>
            </tbody>
        </table>
        <script type="text/javascript">
            jQuery(function ($) {
                var $sandbox_mode = $('#learn_press_paypal_sandbox_mode'),
                    $paypal_type = $('#learn_press_paypal_type');
                $paypal_type.change(function () {
                    $('.learn_press_paypal_type_security').toggleClass('hide-if-js', 'security' != this.value);
                });
                $sandbox_mode.change(function () {
                    this.checked ? $('.sandbox input').removeAttr('readonly') : $('.sandbox input').attr('readonly', true);
                });
            })
        </script>
		<?php
	}

	/**
	 * Print admin options for paypal section
	 */
	public function output_section_paypal() {
		$view = learn_press_get_admin_view( 'settings/payments.php' );
		include_once $view;
	}

	public function saves() {

		$settings = LP_Admin_Settings::instance( 'payment' );
		$section  = $this->section['id'];
		if ( 'paypal' == $section ) {
			$post_data = $_POST['lpr_settings'][ $this->id ];

			$settings->set( 'paypal', $post_data );
		} else {
			do_action( 'learn_press_save_' . $this->id . '_' . $section );
		}
		$settings->update();

	}
}

/**
 * Backward compatibility
 *
 * Class LP_Settings_Base
 */
class LP_Settings_Base extends LP_Abstract_Settings_Page {

}

return new LP_Settings_Payments();