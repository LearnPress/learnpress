<?php
/**
 * Class ListCoursesByPageElementor
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course;

use Elementor\Icons_Manager;
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
use Throwable;

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
			$settings['lp_rest_url']   = esc_url_raw( get_rest_url() );

			if ( get_current_user_id() ) {
				$settings['nonce'] = wp_create_nonce( 'wp_rest' );
			}
			$courses_detect_page = $settings['courses_detect_page'] ?? 'yes';
			if ( 'yes' === $courses_detect_page ) {
				$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();
				if ( $instructor ) {
					$settings['c_author'] = $instructor->get_id();
				}

				// Detect category, tag of course by page.
				if ( learn_press_is_course_category() || learn_press_is_course_tag() ) {
					$cat = get_queried_object();

					$settings['term_id']  = $cat->term_id;
					$settings['taxonomy'] = $cat->taxonomy;
				}
			}

			// Merge params filter form url
			$settings = array_merge(
				$settings,
				lp_archive_skeleton_get_args()
			);

			wp_localize_script( 'lp-courses-by-page', 'lpWidget_' . $this->get_id(), $settings );

			echo '<div class="list-courses-elm-wrapper" data-widget-id="' . $this->get_id() . '">';
			if ( 'yes' !== $is_load_restapi || Plugin::$instance->editor->is_edit_mode() || 'yes' === $courses_rest_no_load_page ) {
				$settings                       = array_merge( $settings, $_GET );
				$settings['paged']              = $GLOBALS['wp_query']->get( 'paged', 1 ) ? $GLOBALS['wp_query']->get( 'paged', 1 ) : 1;
				$settings['courses_ul_classes'] = [ 'list-courses-elm' ];
				echo self::render_data_from_setting( $settings );
			} else {
				lp_skeleton_animation_html( 10 );
			}
			echo '</div>';
		} catch ( Throwable $e ) {
			echo $e->getMessage();
		}
	}

	/**
	 * Render template by setting argument.
	 *
	 * @param array $settings
	 *
	 * @return string
	 */
	public static function render_data_from_setting( array $settings = [] ): string {
		$listCoursesTemplate = ListCoursesTemplate::instance();
		$filter              = new LP_Course_Filter();
		LP_course::handle_params_for_query_courses( $filter, $settings );

		$total_rows               = 0;
		$filter->limit            = $settings['courses_per_page'] ?? 8;
		$settings['courses_list'] = LP_Course::get_courses( $filter, $total_rows );
		$settings['total_rows']   = $total_rows;
		$settings['pagination']   = [
			'total_pages' => LP_Database::get_total_pages( $filter->limit, $total_rows ),
			'base'        => add_query_arg( 'paged', '%#%', $settings['courses_pagination_url'] ?? '' ),
			'paged'       => $filter->page,
			'type'        => $settings['courses_rest_pagination_type'] ?? 'number',
		];

		return $listCoursesTemplate->render_data( $settings, $settings['courses_layout'] );
	}
}
