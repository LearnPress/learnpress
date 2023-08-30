<?php
namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic;
use Elementor\Core\DynamicTags\Tag;
use LearnPress\ExternalPlugin\Elementor\LPDynamicElementor;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

defined( 'ABSPATH' ) || exit;

class CourseCountLessonDynamicElementor extends Tag {
	use LPDynamicElementor;
	public function __construct( array $data = [] ) {
		$this->lp_dynamic_title = 'Course Count Lesson';
		$this->lp_dynamic_name  = 'course-count-lesson';
		parent::__construct( $data );
	}

	public function render() {
		$singleCourseTemplate = SingleCourseTemplate::instance();

		try {
			$course = $this->get_course();
			if ( ! $course ) {
				return;
			}
			echo $singleCourseTemplate->html_count_item( $course, LP_LESSON_CPT );
		} catch ( \Throwable $e ) {
			error_log( $e->getMessage() );
		}

	}
}
