<?php
/**
 * Class CourseLevelDynamicElementor
 *
 * Dynamic course level elementor.
 *
 * @since 4.2.3.5
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic;
use Elementor\Core\DynamicTags\Tag;
use LearnPress\ExternalPlugin\Elementor\LPDynamicElementor;
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use Throwable;

defined( 'ABSPATH' ) || exit;

class CourseDeliverTypeDynamicElementor extends Tag {
	use LPDynamicElementor;

	public function __construct( array $data = [] ) {
		$this->lp_dynamic_title = 'Course Deliver Type';
		$this->lp_dynamic_name  = 'course-deliver-type';
		parent::__construct( $data );
	}

	public function render() {
		$singleCourseTemplate = SingleCourseTemplate::instance();

		try {
			$course = $this->get_course();
			if ( ! $course ) {
				return;
			}

			$course = CourseModel::find( $course->get_id() );
			if ( ! $course ) {
				return;
			}

			echo $singleCourseTemplate->html_deliver_type( $course );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
