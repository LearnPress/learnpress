<?php

/**
 * Class LP_Settings_Payment
 *
 * @author ThimPress
 * @package LearnPress/Admin/Classes
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Settings_Payments extends LP_Settings_Base {
	/**
	 * Constructor
	 */
	function __construct() {
		$this->id   = 'payments';
		$this->text = __( 'Payments', 'learn_press' );
		parent::__construct();
	}

	/**
	 * @return mixed
	 */
	function get_sections() {
		$sections = array(
			'paypal' => __( 'Paypal', 'learn_press' )
		);

		return apply_filters( 'learn_press_payment_method', $sections );
	}

	function output() {
		$section = $this->section;
		?>
		<h3 class=""><?php echo $this->section['text']; ?></h3>
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
	function output_section_paypal() {
		$view = learn_press_get_admin_view( 'settings/payments.php' );
		include_once $view;
	}

	function saves() {

		$settings = LP_Admin_Settings::instance( 'payment' );
		$section  = $this->section['id'];
		if ( 'paypal' == $section ) {
			$post_data = $_POST['lpr_settings'][$this->id];

			$settings->set( 'paypal', $post_data );
		} else {
			do_action( 'learn_press_save_' . $this->id . '_' . $section );
		}
		$settings->update();
		return;
		$payment_options                = get_option( '_lpr_payment_settings', array() );
		$section                        = isset( $_GET['section'] ) ? $_GET['section'] : 'paypal';
		$params                         = isset( $_POST['lpr_settings']['payment'][$section] ) ? $_POST['lpr_settings']['payment'][$section] : $payment_options[$section];
		$payment_options[$section]      = $params;
		$payment_options['method']      = isset( $_POST['lpr_settings']['payment']['method'] ) ? $_POST['lpr_settings']['payment']['method'] : '';
		$payment_options['third_party'] = isset( $_POST['lpr_settings']['payment']['third_party'] ) ? $_POST['lpr_settings']['payment']['third_party'] : '';
		update_option( '_lpr_payment_settings', $payment_options );
		return;
		$payment_options                = get_option( '_lpr_payment_settings', array() );
		$payment_tab                    = isset( $_GET['section'] ) ? $_GET['section'] : 'paypal';
		$params                         = isset( $_POST['lpr_settings']['payment'][$payment_tab] ) ? $_POST['lpr_settings']['payment'][$payment_tab] : $payment_options[$payment_tab];
		$payment_options[$payment_tab]  = $params;
		$payment_options['woocommerce'] = isset( $_POST['lpr_settings']['payment']['woocommerce'] ) ? $_POST['lpr_settings']['payment']['woocommerce'] : array();
		update_option( '_lpr_payment_settings', $payment_options );
	}
}

new LP_Settings_Payments();