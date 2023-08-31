<?php
/**
 * Class SingleInstructorElementor
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets;

use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\Helpers\Config;

class LoginUserFormElementor extends LPElementorWidgetBase {
	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Login Form', 'learnpress' );
		$this->name     = 'login_form';
		$this->keywords = [ 'login' ];
		$this->icon     = 'eicon-lock-user';
		parent::__construct( $data, $args );
	}

	protected function register_controls() {
		$this->controls = Config::instance()->get( 'login-user-form', 'elementor' );
		parent::register_controls();
	}

	public function render() {
		try {
			$settings = $this->get_settings_for_display();

			if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				learn_press_get_template( 'global/form-login.php' );
			} else {
				\LearnPress::instance()->template( 'profile' )->login_form();
			}
		} catch ( \Throwable $e ) {
			error_log( __METHOD__ . ' ' . $e->getMessage() );
		}
	}

	public function get_style_depends() {
		wp_register_style( 'learnpress', LP_PLUGIN_URL . 'assets/css/learnpress.css', array(), uniqid() );

		return array( 'learnpress' );
	}
}
