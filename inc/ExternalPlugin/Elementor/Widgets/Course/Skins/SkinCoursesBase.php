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
use LP_Course_Filter;
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
	 * Render widget content.
	 * @override elementor
	 *
	 * @return void
	 */
	public function render() {
		try {
			$settings                  = $this->parent->get_settings_for_display();
			$is_load_restapi           = $settings['courses_rest'] ?? 0;
			$courses_rest_no_load_page = $settings['courses_rest_no_load_page'] ?? 0;
			$settings['url_current'] = LP_Helper::getUrlCurrent();

			// Merge params filter form url
			$settings = array_merge(
				$settings,
				lp_archive_skeleton_get_args()
			);

			$html_wrapper = [
				'<div class="list-courses-elm-wrapper" data-widget-id="' . $this->get_id() . '">' => '</div>',
			];

			if ( 'yes' !== $is_load_restapi || Plugin::$instance->editor->is_edit_mode() || 'yes' === $courses_rest_no_load_page ) {
				$templateObj = self::render_courses( $settings );
				$content     = $templateObj->content;
				$content     = Template::instance()->nest_elements(
					[ '<div class="learn-press-courses">' => '</div>' ],
					$content
				);
			} else {
				$html_el_target = '<div class="learn-press-courses"></div>';
				$args           = array_merge(
					[
						'el_target' => '.learn-press-courses',
					],
					$settings
				);

				$callback = [
					'class'  => get_class( $this ),
					'method' => 'render_courses',
				];
				$content  = TemplateAJAX::load_content_via_ajax( $html_el_target, $args, $callback );
			}

			echo Template::instance()->nest_elements( $html_wrapper, $content );
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

		ob_start();
		foreach ( $courses as $courseObj ) {
			$course = learn_press_get_course( $courseObj->ID );
			echo static::render_course( $course, $settings );
		}

		$listCoursesTemplate = ListCoursesTemplate::instance();
		$data_pagination     = [
			'total_pages' => ceil( $total_rows / $filter->limit ),
			'type'        => 'number',
			'base' => add_query_arg( 'paged', '%#%', $settings[ 'url_current' ] ?? '' ),
			'paged' => $settings['paged'] ?? 1,
		];
		echo $listCoursesTemplate->html_pagination( $data_pagination );

		$content          = new stdClass();
		$content->content = ob_get_clean();

		return $content;
	}

	/**
	 * Render single item course
	 *
	 * @param $course
	 * @param array $settings
	 *
	 * @return string
	 */
	public static function render_course( $course, array $settings = [] ): string {
		$singleCourseTemplate = SingleCourseTemplate::instance();
		$content              = '';
		$content              .= $singleCourseTemplate->html_title( $course );
		$content              .= $singleCourseTemplate->html_image( $course );

		return $content;
	}
}
