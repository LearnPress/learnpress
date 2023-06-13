<?php
/**
 * Class SingleInstructorElementor
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Instructor;

use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\Helpers\Config;

class SingleInstructorElementor extends LPElementorWidgetBase {
	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Single Instructor', 'learnpress' );
		$this->name     = 'single_instructor';
		$this->keywords = [ 'instructor', 'single', 'teacher' ];
		parent::__construct( $data, $args );
	}

	protected function register_controls() {
		$this->controls = Config::instance()->get( 'single-instructor', 'elementor/instructor' );
		parent::register_controls();
	}

	protected function render() {
		echo 13123;
	}

}
