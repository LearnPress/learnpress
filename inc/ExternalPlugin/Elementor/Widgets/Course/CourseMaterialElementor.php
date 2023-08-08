<?php
namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course;

use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\Helpers\Config;
use Throwable;
class CourseMaterialElementor extends LPElementorWidgetBase {
	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Course/Lesson Material', 'learnpress' );
		$this->name     = 'lp_course_material';
		$this->keywords = [ 'courses', 'lesson', 'material' ];
		$this->icon     = 'eicon-document-file';
		parent::__construct( $data, $args );
	}

	protected function register_controls() {
		$this->controls = Config::instance()->get( 'materials', 'elementor/course' );
		parent::register_controls();
	}
	public function render() {
		try {
			$settings = $this->get_settings_for_display();
			echo '<pre>';
			print_r( $settings );
			echo '</pre>';
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}
}

