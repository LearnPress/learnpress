<?php

/**
 * Class LP_Settings_Payment
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Settings_Payments extends LP_Settings_Base {
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
		$sections = array();
		if ( $gateways ) foreach ( $gateways as $id => $gateway ) {
			$sections[$id] = array(
				'id'          => $id,
				'title'       => !empty( $gateway->method_title ) ? $gateway->method_title : ucfirst( $gateway->id ),
				'description' => !empty( $gateway->method_description ) ? $gateway->method_description : ''
			);
		}
		return $sections;
	}

	public function output() {
		$section = $this->section;
		?>
		<h3 class="learn-press-settings-title"><?php echo $this->section['title']; ?></h3>
		<?php if ( !empty( $this->section['description'] ) ) : ?>
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
			$post_data = $_POST['lpr_settings'][$this->id];

			$settings->set( 'paypal', $post_data );
		} else {
			do_action( 'learn_press_save_' . $this->id . '_' . $section );
		}
		$settings->update();

	}
}

new LP_Settings_Payments();