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
use WP_User_Query;

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
			$courses_layout      = $this->render_course_items( $courses_layout, $settings );
			echo $listCoursesTemplate->render_data( $courses_layout );

			// End show list courses
		} catch ( \Throwable $e ) {
			echo $e->getMessage();
		}
	}

	private function render_course_items( $data_content, $settings ) {
		return str_replace(
			[
				'{{course_items}}',
			],
			[
				$this->html_course_items( $settings ),
			],
			$data_content
		);
	}

	private function html_course_items( $settings ) {
		$is_load_restapi     = $settings['load_restapi'] ?? 0;
		$courses_per_page    = $settings['courses_per_page'] ?? 20;
		$courses_item_layout = $settings['courses_item_layout'] ?? '';
		$layout_default      = $settings['layout_default'] ?? 'grid';

		// Start show list courses
		// For load course via REST API
		if ( $is_load_restapi ) {
			// Get courses via REST API
		} else {
			$filter = new LP_Course_Filter();
			LP_course::handle_params_for_query_courses( $filter, $_GET );

			$total_rows    = 0;
			$filter->limit = $courses_per_page;
			$filter        = apply_filters( 'lp/api/courses/filter', $filter, $_GET );
			$courses       = LP_Course::get_courses( $filter, $total_rows );
			ob_start();
			$singleCourseTemplate = SingleCourseTemplate::instance();
			echo '<ul class="list-courses-elm ' . $layout_default . '">';
			foreach ( $courses as $courseObj ) {
				$course_id = $courseObj->ID;
				$course    = learn_press_get_course( $course_id );
				if ( ! $course ) {
					continue;
				}
				?>
				<li class="item-course">
					<?php echo $singleCourseTemplate->render_data( $course, html_entity_decode( $courses_item_layout ) ); ?>
				</li>
				<?php
			}
			echo '</ul>';
			$content = ob_get_clean();

			return $content;

			//Template::instance()->print_sections( [ 'content' => [ 'text_html' => $content ] ] );

			/*$html_wrap = [
				'<div class="' . ( 'elementor-repeater-item-' ) . '">' => '</div>',
			];
			echo Template::instance()->nest_elements( $html_wrap, $content );*/
		}
	}
}
