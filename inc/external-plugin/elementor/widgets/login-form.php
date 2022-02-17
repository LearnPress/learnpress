<?php
namespace Elementor;

class LP_Elementor_Widget_Login_Form extends LP_Elementor_Widget_Base {

	public function get_name() {
		return 'learnpress_login_form';
	}

	public function get_title() {
		return esc_html__( 'Login Form', 'learnpress' );
	}

	public function get_keywords() {
		return array( 'learnpress', 'login' );
	}

	public function get_icon() {
		return 'eicon-lock-user';
	}

	protected function register_controls() {
		$this->register_control_style_form_title( '.learn-press-form-login h3' );
		$this->register_control_style_form_field( '.learn-press-form-login', '.form-field' );
		$this->register_control_style_form_button( '.learn-press-form-login button[type="submit"]' );
		$this->register_control_style_message();
	}

	public function render() {
		if ( 'yes' !== LP()->settings()->get( 'enable_login_profile' ) ) {
			learn_press_display_message( __( 'Login form is disabled', 'learnpress' ), 'error' );
		}

		if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			learn_press_get_template( 'global/form-login.php' );
		} else {
			echo LP()->template( 'profile' )->login_form();
		}
	}
}
