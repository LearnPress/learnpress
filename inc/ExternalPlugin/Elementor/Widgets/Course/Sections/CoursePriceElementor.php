<?php
/**
 * Class InstructorTitleElementor
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Sections;

use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\Helpers\Config;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\SingleCourseBaseElementor;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

class CoursePriceElementor extends LPElementorWidgetBase {
	use SingleCourseBaseElementor;

	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Course Price', 'learnpress' );
		$this->name     = 'course_price';
		$this->keywords = [ 'course price', 'price' ];
		parent::__construct( $data, $args );
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->controls = Config::instance()->get(
			'course-price',
			'elementor/course'
		);
		parent::register_controls();
	}

	/**
	 * Show content of widget
	 *
	 * @return void
	 */
	protected function render() {
		$singleCourseTemplate = SingleCourseTemplate::instance();

		try {
			$course = $this->get_course();
			if ( ! $course ) {
				return;
			}
			echo $singleCourseTemplate->html_price( $course );
		} catch ( \Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
