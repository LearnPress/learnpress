<?php
namespace Elementor;

class LP_Elementor_Widget_Register_Form extends LP_Elementor_Widget_Base {

	public function get_name() {
		return 'learnpress_register_form';
	}

	public function get_title() {
		return esc_html__( 'Register Form', 'learnpress' );
	}

	public function get_keywords() {
		return array( 'learnpress', 'Register' );
	}

	public function get_icon() {
		return 'eicon-lock-user';
	}

	protected function register_controls() {
		$this->register_control_style_form_title( '.learn-press-form-register h3' );
		$this->register_control_style_form_field( '.learn-press-form-register', '.form-field' );
		$this->register_control_style_form_button( '.learn-press-form-register button[type="submit"]' );
		$this->register_control_style_message();
	}

	public function render() {
		if ( 'yes' !== LP()->settings()->get( 'enable_register_profile' ) ) {
			learn_press_display_message( __( 'Register form is disabled', 'learnpress' ), 'error' );
		}

		if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			learn_press_get_template( 'global/form-register.php' );
		} else {
			echo LP()->template( 'profile' )->register_form();
		}
	}
}
