<?php
namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic;


use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module as TagsModule;

use LearnPress\ExternalPlugin\Elementor\LPElementor;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

defined( 'ABSPATH' ) || exit;

class CoursePriceDynamicElementor extends Tag {
	use CourseDynamicBaseElementor;
	public function __construct( array $data = [] ) {
		$this->lp_dynamic_title = 'Course Price';
		$this->lp_dynamic_name  = 'course-price';
		parent::__construct( $data );
	}

	public function render() {
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
