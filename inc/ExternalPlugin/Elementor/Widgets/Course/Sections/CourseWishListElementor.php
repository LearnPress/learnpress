<?php
/**
 * Class CourseWishlistElementor
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */
namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Sections;

use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\Helpers\Config;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\SingleCourseBaseElementor;
use LP_Addon_Wishlist_Preload;


class CourseWishlistElementor extends LPElementorWidgetBase {
    use SingleCourseBaseElementor;

	public function __construct( $data = [], $args = null ) {
		$this->title    	= esc_html__( 'Course Wishlist', 'learnpress-wishlist' );
		$this->name     	= 'course_wishlist';
		$this->keywords 	= [ 'course wishlist', 'wishlist' ];
		parent::__construct( $data, $args );
	}

    protected function register_controls() {
		$this->controls = Config::instance()->get(
			'course-wishlist',
			'elementor/course'
		);
		parent::register_controls();
	}
    /**
	 * Show content of widget
	 *
	 * @return void
	 */
	protected function render() {

		try {
			$course = $this->get_course();
			if ( ! $course ) {
				return;
			}
			wp_enqueue_script( 'lp-course-wishlist' );
			LP_Addon_Wishlist_Preload::$addon->wishlist_button( $course->ID );
		} catch ( \Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
