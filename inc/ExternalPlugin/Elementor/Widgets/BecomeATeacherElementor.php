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

class BecomeATeacherElementor extends LPElementorWidgetBase {
	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Become a teacher', 'learnpress' );
		$this->name     = 'become_a_teacher';
		$this->keywords = [ 'teacher', 'become' ];
		$this->icon     = 'eicon-site-logo';
		parent::__construct( $data, $args );
	}

	protected function register_controls() {
		$this->controls = Config::instance()->get( 'become-a-teacher', 'elementor/instructor' );
		parent::register_controls();
	}

	public function render() {
		$settings = $this->get_settings_for_display();

		echo do_shortcode( '[learn_press_become_teacher_form title="' . $settings['title'] . '" description="' . $settings['description'] . '" submit_button_text="' . esc_html( $settings['submit_button_text'] ) . '" submit_button_process_text="' . $settings['submit_button_process_text'] . '"]' );
	}

	public function get_style_depends() {
		wp_register_style( 'learnpress', LP_PLUGIN_URL . 'assets/css/learnpress.css', array(), uniqid() );

		return array( 'learnpress' );
	}
}
