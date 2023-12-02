<?php
/**
 * Class SkinCoursesBase
 *
 * For render course in list course
 *
 * @since 4.2.5.7
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Skins;

use Elementor\Plugin;
use Elementor\Widget_Base;
use LearnPress\ExternalPlugin\Elementor\LPSkinBase;
use LearnPress\Helpers\Template;
use LearnPress\Models\Courses;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Course;
use LP_Course_Filter;
use LP_Database;
use LP_Helper;
use stdClass;
use Throwable;

class SkinCoursesBase extends LPSkinBase {
	protected function _register_controls_actions() {
		add_action(
			'elementor/element/learnpress_list_courses_by_page/section_skin/before_section_end',
			[ $this, 'controls_on_section_skin' ],
			10,
			2
		);
	}

	public function controls_on_section_skin( Widget_Base $widget, $args ) {
		// Only add controls here
	}

	/**
	 * Render content list courses. (Grid/List)
	 * @override elementor
	 *
	 * @return void
	 */
	public function render() {
		try {
			$settings                  = $this->parent->get_settings_for_display();
			$is_load_restapi           = $settings['courses_rest'] ?? 0;
			$courses_rest_no_load_page = $settings['courses_rest_no_load_page'] ?? 0;
			$settings['url_current']   = LP_Helper::getUrlCurrent();

			// Merge params filter form url
			$settings = array_merge(
				$settings,
				lp_archive_skeleton_get_args()
			);

			$html_wrapper_widget      = [
				'<div class="list-courses-elm-wrapper">' => '</div>',
			];
			$name_target              = 'learn-press-courses-wrapper';
			$html_wrapper_courses     = [ '<div class="' . $name_target . '">' => '</div>' ];
			$el_target_render_courses = '<div class="' . $name_target . '"></div>';

			// No load AJAX
			if ( 'yes' !== $is_load_restapi || Plugin::$instance->editor->is_edit_mode() || 'yes' === $courses_rest_no_load_page ) {
				$templateObj = self::render_courses( $settings );
				$content     = $templateObj->content;
				$content     = Template::instance()->nest_elements( $html_wrapper_courses, $content );
			} else { // Load AJAX
				$args = array_merge(
					[
						'el_target' => '.' . $name_target,
					],
					$settings
				);

				$callback = [
					'class'  => get_class( $this ),
					'method' => 'render_courses',
				];
				$content  = TemplateAJAX::load_content_via_ajax( $el_target_render_courses, $args, $callback );
			}

			echo Template::instance()->nest_elements( $html_wrapper_widget, $content );
		} catch ( Throwable $e ) {
			echo $e->getMessage();
		}
	}

	/**
	 * Render template list courses with settings param.
	 *
	 * @param array $settings
	 *
	 * @return stdClass { content: string_html }
	 */
	public static function render_courses( array $settings = [] ): stdClass {
		$filter = new LP_Course_Filter();
		Courses::handle_params_for_query_courses( $filter, $settings );
		$total_rows    = 0;
		$filter->limit = $settings['courses_per_page'] ?? 8;
		$courses       = Courses::get_courses( $filter, $total_rows );
		$skin          = $settings['skin'] ?? 'grid';

		ob_start();
		echo '<ul class="learn-press-courses ' . $skin . '">';
		foreach ( $courses as $courseObj ) {
			$course = learn_press_get_course( $courseObj->ID );
			echo static::render_course( $course, $settings );
		}
		echo '</ul>';

		$listCoursesTemplate = ListCoursesTemplate::instance();
		$data_pagination     = [
			'total_pages' => LP_Database::get_total_pages( $filter->limit, $total_rows ),
			'type'        => 'number',
			'base'        => add_query_arg( 'paged', '%#%', $settings['url_current'] ?? '' ),
			'paged'       => $settings['paged'] ?? 1,
		];
		echo $listCoursesTemplate->html_pagination( $data_pagination );

		$content          = new stdClass();
		$content->content = ob_get_clean();

		return $content;
	}

	/**
	 * Render single item course
	 *
	 * @param LP_Course $course
	 * @param array $settings
	 *
	 * @return string
	 */
	public static function render_course( LP_Course $course, array $settings = [] ): string {
		$html_item            = '';
		$singleCourseTemplate = SingleCourseTemplate::instance();

		try {
			$html_wrapper = [
				'<li class="course-item">' => '</li>',
			];

			$title = sprintf( '<a href="%s">%s</a>', $course->get_permalink(), $course->get_title() );

			$section = [
				'image'  => [ 'text_html' => $singleCourseTemplate->html_image( $course ) ],
				'author' => [ 'text_html' => sprintf( '<div>%s</div>', $course->get_instructor_html() ) ],
				'title'  => [ 'text_html' => $title ],
				'price'  => [ 'text_html' => sprintf( '<div>%s</div>', $singleCourseTemplate->html_price( $course ) ) ],
				'button' => [ 'text_html' => sprintf( '<div><a href="%s">%s</a></div>', $course->get_permalink(), __( 'View More' ) ) ],
			];

			ob_start();
			Template::instance()->print_sections( $section );
			$html_item = ob_get_clean();
			$html_item = Template::instance()->nest_elements( $html_wrapper, $html_item );
		} catch ( Throwable $e ) {
			$html_item = $e->getMessage();
		}

		return $html_item;
	}
}
