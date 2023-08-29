<?php
namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use LearnPress\ExternalPlugin\Elementor\LPElementor;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

defined( 'ABSPATH' ) || exit;

class CourseCategoryDynamicElementor extends Tag {
	use CourseDynamicBaseElementor;
	public function __construct( array $data = [] ) {
		$this->lp_dynamic_title = 'Course Category';
		$this->lp_dynamic_name  = 'course-category';
		parent::__construct( $data );
	}

	public function render() {
		$id = get_the_ID();
		if ( ! $id ) {
			return;
		}

		$singleCourseTemplate = SingleCourseTemplate::instance();
		$course               = learn_press_get_course( $id );
		if ( ! $course ) {
			return;
		}
		echo $singleCourseTemplate->html_categories( $course );
	}
}
