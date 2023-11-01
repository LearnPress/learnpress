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
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;

class CoursesCountStudentElementor extends LPElementorWidgetBase {
	use SingleCourseBaseElementor;

	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Courses Student Count', 'learnpress' );
		$this->name     = 'courses_student_count';
		$this->keywords = [ 'courses student', 'price' ];
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
		$listTemplate = ListCoursesTemplate::instance();

		try {

			echo $listTemplate->html_count_students();
			echo $listTemplate->html_count_course_free();
		} catch ( \Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
