<?php
/**
 * Class CourseLevelDynamicElementor
 *
 * Dynamic course level elementor.
 *
 * @since 4.2.3.5
 * @version 1.0.1
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic;
use Elementor\Core\DynamicTags\Tag;
use LearnPress\ExternalPlugin\Elementor\LPDynamicElementor;
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseOfflineTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use Throwable;

defined( 'ABSPATH' ) || exit;

class CourseOfflineDeliverTypeDynamicElementor extends Tag {
	use LPDynamicElementor;

	public function __construct( array $data = [] ) {
		$this->lp_dynamic_title = 'Course Deliver Type';
		$this->lp_dynamic_name  = 'course-deliver-type';
		parent::__construct( $data );
	}

	public function render() {
		$singleCourseOfflineTemplate = SingleCourseOfflineTemplate::instance();

		try {
			$course = $this->get_course_model();
			if ( ! $course ) {
				return;
			}

			echo $singleCourseOfflineTemplate->html_deliver_type( $course );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
