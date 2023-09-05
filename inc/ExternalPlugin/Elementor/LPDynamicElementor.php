<?php
/**
 * Class LPDynamicElementor
 * Declare base properties for dynamic course elementor
 *
 * @since 4.2.3.5
 * @version 1.0.0
 */
namespace LearnPress\ExternalPlugin\Elementor;

use Elementor\Modules\DynamicTags\Module;
use LP_Course;

defined( 'ABSPATH' ) || exit;

trait LPDynamicElementor {
	public $lp_dynamic_name       = '';
	public $lp_dynamic_categories = [ Module::TEXT_CATEGORY ];
	public $lp_dynamic_title      = '';
	public $lp_dynamic_group      = LPElementor::GROUP_DYNAMIC;

	public function get_name() {
		return $this->lp_dynamic_name;
	}

	public function get_categories() {
		return $this->lp_dynamic_categories;
	}

	public function get_group() {
		return $this->lp_dynamic_group;
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
