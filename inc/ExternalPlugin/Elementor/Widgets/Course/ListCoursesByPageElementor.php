<?php
/**
 * Class ListCoursesByPageElementor
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course;

use Elementor\Plugin;
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

		wp_register_script(
			'lp-courses-by-page',
			LP_PLUGIN_URL . 'assets/js/dist/elementor/courses.js',
			array(),
			uniqid(),
			true
		);
		$this->add_style_depends( 'lp-courses-by-page' );
		$this->add_script_depends( 'lp-courses-by-page' );
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
			$settings                  = $this->get_settings_for_display();
			$is_load_restapi           = $settings['courses_rest'] ?? 0;
			$courses_rest_no_load_page = $settings['courses_rest_no_load_page'] ?? 0;
			$courses_per_page          = $settings['courses_per_page'] ?? 20;
			$courses_layout            = $settings['courses_layout'] ?? '';
			$courses_item_layout       = $settings['courses_item_layout'] ?? '';
			$order_by_default          = $settings['courses_order_by_default'] ?? '';
			$listCoursesTemplate       = ListCoursesTemplate::instance();
			$settings['lp_rest_url']   = esc_url_raw( get_rest_url() );
			if ( get_current_user_id() ) {
				$settings['nonce'] = wp_create_nonce( 'wp_rest' );
			}
			wp_localize_script( 'lp-courses-by-page', 'lpWidget_' . $this->get_id(), $settings );

			echo '<div class="list-courses-elm-wrapper" data-widget-id="' . $this->get_id() . '">';
			if ( 'yes' !== $is_load_restapi || Plugin::$instance->editor->is_edit_mode() || $courses_rest_no_load_page ) {
				$filter        = new LP_Course_Filter();
				$_GET['paged'] = $GLOBALS['wp_query']->get( 'paged', 1 ) ? $GLOBALS['wp_query']->get( 'paged', 1 ) : 1;
				if ( ! isset( $_GET['order_by'] ) ) {
					$_GET['order_by'] = $order_by_default;
				}
				LP_course::handle_params_for_query_courses( $filter, $_GET );

				$total_rows             = 0;
				$filter->limit          = $courses_per_page;
				$courses_list           = LP_Course::get_courses( $filter, $total_rows );
				$total_pages            = LP_Database::get_total_pages( $filter->limit, $total_rows );
				$base                   = add_query_arg( 'paged', '%#%', LP_Helper::getUrlCurrent() );
				$paged                  = $filter->page;
				$pagination             = compact( 'total_pages', 'base', 'paged' );
				$courses_layout_default = $settings['courses_layout_default'] ?? 'grid';
				$courses_ul_classes     = [ 'list-courses-elm' ];
				$courses_list_icon      = $settings['courses_list_icon'] ?? 'list';
				$courses_grid_icon      = $settings['courses_grid_icon'] ?? 'grid';
				$data_courses           = compact(
					'courses_list',
					'pagination',
					'courses_item_layout',
					'courses_ul_classes',
					'courses_layout_default',
					'courses_list_icon',
					'courses_grid_icon'
				);
				echo $listCoursesTemplate->render_data( $data_courses, $courses_layout );
			} else {
				lp_skeleton_animation_html( 10 );
			}
			echo '</div>';
		} catch ( \Throwable $e ) {
			echo $e->getMessage();
		}
	}
}
