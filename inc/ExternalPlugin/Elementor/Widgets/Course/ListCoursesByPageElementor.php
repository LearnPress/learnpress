<?php
/**
 * Class ListCoursesByPageElementor
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course;

use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\Helpers\Config;
use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LP_Course;
use LP_Course_Filter;
use LP_Database;
use LP_Helper;

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
		$this->add_style_depends( 'lp-courses-by-page' );
		parent::__construct( $data, $args );
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

	/**
	 * Render Template
	 *
	 * @return void
	 */
	protected function render() {
		try {
			$settings            = $this->get_settings_for_display();
			$is_load_restapi     = $settings['load_restapi'] ?? 0;
			$courses_per_page    = $settings['courses_per_page'] ?? 20;
			$courses_layout      = $settings['courses_layout'] ?? '';
			$courses_item_layout = $settings['courses_item_layout'] ?? '';
			$listCoursesTemplate = ListCoursesTemplate::instance();

			if ( ! $is_load_restapi ) {
				$filter        = new LP_Course_Filter();
				$_GET['paged'] = $GLOBALS['wp_query']->get( 'paged', 1 ) ? $GLOBALS['wp_query']->get( 'paged', 1 ) : 1;
				LP_course::handle_params_for_query_courses( $filter, $_GET );

				$total_rows         = 0;
				$filter->limit      = $courses_per_page;
				$courses_list       = LP_Course::get_courses( $filter, $total_rows );
				$total_pages        = LP_Database::get_total_pages( $filter->limit, $total_rows );
				$base               = add_query_arg( 'paged', '%#%', LP_Helper::getUrlCurrent() );
				$paged              = $filter->page;
				$pagination         = compact( 'total_pages', 'base', 'paged' );
				$courses_ul_classes = [ 'list-courses-elm' ];
				$data_courses       = compact(
					'courses_list',
					'pagination',
					'courses_item_layout',
					'courses_ul_classes'
				);

				echo $listCoursesTemplate->render_data( $data_courses, $courses_layout );
			}
		} catch ( \Throwable $e ) {
			echo $e->getMessage();
		}
	}
}
