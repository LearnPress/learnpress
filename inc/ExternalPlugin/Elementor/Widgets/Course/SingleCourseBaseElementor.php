<?php
/**
 * Class SingleInstructorBaseElementor
 *
 * Has general methods for sections single instructor widgets use
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course;

use LearnPress\ExternalPlugin\Elementor\LPElementor;
use LP_Course;

trait SingleCourseBaseElementor {
	/**
	 * @var string $title
	 */
	public $title = '';
	/**
	 * @var string $prefix_name;
	 */
	private $prefix_name = 'learnpress_';
	/**
	 * @var string $name;
	 */
	public $name = '';
	/**
	 * @var string $icon
	 */
	public $icon;
	/**
	 * @var string[] key search widget
	 */
	public $keywords = array();
	/**
	 * @var array Controls
	 */
	public $controls = array();
	/**
	 * @var array Controls
	 */
	public $categories = array( LPElementor::CATE_COURSE );

	public function get_title() {
		return $this->title;
	}

	public function get_name() {
		return $this->prefix_name . $this->name;
	}

	public function get_icon() {
		return $this->icon ?? 'eicon-site-logo';
	}

	public function get_keywords() {
		return array_push( $this->keywords, 'learnpress' );
	}

	public function get_categories() {
		return $this->categories;
	}

	public function get_help_url() {
		return '';
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
