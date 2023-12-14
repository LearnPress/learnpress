<?php
/**
 * Class ListCoursesByPageElementor
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course;

use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Skins\CoursesList;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Skins\CoursesGrid;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Skins\CoursesLoopItem;
use LearnPress\Helpers\Config;

class ListCoursesByPageElementor extends LPElementorWidgetBase {
	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'List Courses by Page', 'learnpress' );
		$this->name     = 'list_courses_by_page';
		$this->keywords = [ 'list courses', 'by page' ];
		$this->icon     = 'eicon-post-list';

		wp_register_style(
			'lp-courses-by-page',
			LP_PLUGIN_URL . 'assets/css/elementor/course/list-courses-by-page.css',
			array(),
			uniqid()
		);

		wp_register_script(
			'lp-courses-by-page',
			LP_PLUGIN_URL . 'assets/js/dist/elementor/courses.js',
			array(),
			uniqid(),
			true
		);
		$this->add_style_depends( 'lp-courses-by-page' );
		$this->add_script_depends( 'lp-courses-v2' );
		parent::__construct( $data, $args );
	}

	protected function register_skins() {
		$skins = [
			'courses_grid' => CoursesGrid::class,
			'courses_list' => CoursesList::class,
		];

		if ( class_exists( \Thim_EL_Kit::class ) ) {
			$skins['courses_loop_item'] = CoursesLoopItem::class;
		}

		foreach ( $skins as $skin ) {
			$this->add_skin( new $skin( $this ) );
		}
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->controls = Config::instance()->get(
			'list-courses-by-page',
			'elementor/course'
		);
		parent::register_controls();
	}
}
