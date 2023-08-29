<?php
namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic;

use Elementor\Modules\DynamicTags\Module as TagsModule;
use LearnPress\ExternalPlugin\Elementor\LPElementor;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Course;

defined( 'ABSPATH' ) || exit;

trait CourseDynamicBaseElementor {
	public $lp_dynamic_name       = '';
	public $lp_dynamic_categories = [ TagsModule::TEXT_CATEGORY ];
	public $lp_dynamic_title      = '';
	public $lp_dynamic_group      = '';

	public function get_name() {
		return $this->lp_dynamic_name;
	}

	public function get_categories() {
		return $this->lp_dynamic_categories;
	}

	public function get_group() {
		return ! empty( $this->lp_dynamic_group ) ? $this->lp_dynamic_group : LPElementor::$group_dynamic;
	}

	public function get_title() {
		return $this->lp_dynamic_title;
	}

	/**
	 * Get course
	 *
	 * @return bool|LP_Course|mixed
	 */
	public function get_course() {
		$id = get_the_ID();
		if ( ! $id ) {
			return false;
		}

		return learn_press_get_course( $id );
	}
}
