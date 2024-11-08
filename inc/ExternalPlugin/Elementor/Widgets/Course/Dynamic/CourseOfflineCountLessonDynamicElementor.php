<?php
/**
 * Class CourseCountLessonDynamicElementor
 *
 * Dynamic course count lesson elementor.
 *
 * @since 4.2.7.2
 * @version 1.0.0
 */
namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic;
use Elementor\Core\DynamicTags\Tag;
use LearnPress\ExternalPlugin\Elementor\LPDynamicElementor;
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseOfflineTemplate;

defined( 'ABSPATH' ) || exit;

class CourseOfflineCountLessonDynamicElementor extends Tag {
	use LPDynamicElementor;

	/**
	 * Declare base properties for dynamic course elementor
	 *
	 * @param array $data
	 */
	public function __construct( array $data = [] ) {
		$this->lp_dynamic_title = 'Course Offline Count Lesson';
		$this->lp_dynamic_name  = 'course-offline-count-lesson';
		parent::__construct( $data );
	}

	/**
	 * Render dynamic course count lesson elementor.
	 *
	 * @return void
	 */
	public function render() {
		$singleCourseTemplate = SingleCourseOfflineTemplate::instance();

		try {
			$course = $this->get_course_model();
			if ( ! $course ) {
				return;
			}
			echo $singleCourseTemplate->html_lesson_info( $course, true );
		} catch ( \Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
