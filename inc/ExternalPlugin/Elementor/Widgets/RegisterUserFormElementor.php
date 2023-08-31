<?php
/**
 * Class SingleInstructorElementor
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets;

use Elementor\Plugin;
use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\Helpers\Config;

class RegisterUserFormElementor extends LPElementorWidgetBase {
	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Register Form', 'learnpress' );
		$this->name     = 'register_form';
		$this->keywords = [ 'register' ];
		$this->icon     = 'eicon-lock-user';
		parent::__construct( $data, $args );
	}

	protected function register_controls() {
		$this->controls = Config::instance()->get( 'register-user-form', 'elementor' );
		parent::register_controls();
	}

	public function render() {
		try {
			$settings = $this->get_settings_for_display();

			if ( class_exists( '\Elementor\Plugin' ) && Plugin::$instance->editor->is_edit_mode() ) {
				learn_press_get_template( 'global/form-register.php' );
			} else {
				\LearnPress::instance()->template( 'profile' )->register_form();
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
