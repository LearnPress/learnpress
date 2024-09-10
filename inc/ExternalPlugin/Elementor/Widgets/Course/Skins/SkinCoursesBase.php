<?php
/**
 * Class SkinCoursesBase
 *
 * For render course in list course
 *
 * @since 4.2.5.7
 * @version 1.0.1
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

			$html_wrapper_widget = [
				'<div class="list-courses-elm-wrapper">' => '</div>',
			];

			// No load AJAX
			if ( 'yes' !== $is_load_restapi || Plugin::$instance->editor->is_edit_mode() ) {
				$templateObj = self::render_courses( $settings );
				$content     = $templateObj->content;
			} elseif ( 'yes' === $courses_rest_no_load_page ) {
				$callback                            = [
					'class'  => get_class( $this ),
					'method' => 'render_courses',
				];
				$content_obj                         = static::render_courses( $settings );
				$settings['html_no_load_ajax_first'] = $content_obj->content;
				$content                             = TemplateAJAX::load_content_via_ajax( $settings, $callback );
			} else { // Load AJAX
				$callback = [
					'class'  => get_class( $this ),
					'method' => 'render_courses',
				];
				$content  = TemplateAJAX::load_content_via_ajax( $settings, $callback );
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
	 * @since 4.2.5.7
	 * @version 1.0.1
	 */
	public static function render_courses( array $settings = [] ): stdClass {
		$listCoursesTemplate  = ListCoursesTemplate::instance();
		$skin                 = $settings['skin'] ?? 'grid';
		$courses_limit        = $settings['courses_limit'] ?? 0;
		$courses_per_page     = $settings['courses_per_page'] ?? 8;
		$courses_order_by     = $settings['order_by'] ?? $settings['courses_order_by_default'] ?? 'post_date';
		$total_pages          = 0;
		$paged                = $settings['paged'] ?? 1;
		$show_el_sorting      = ( $settings['el_sorting'] ?? 'yes' ) === 'yes';
		$show_el_result_count = ( $settings['el_result_count'] ?? 'yes' ) === 'yes';
		$pagination_type      = $settings['pagination_type'] ?? 'number';
		$courses_category_ids = $settings['courses_category_ids'] ?? [];

		if ( $courses_limit > 0 ) {
			if ( $courses_per_page > $courses_limit ) {
				$courses_per_page = $courses_limit;
			} elseif ( $courses_per_page === 0 ) {
				$courses_per_page = $courses_limit;
			}

			$total_pages = LP_Database::get_total_pages( $courses_per_page, $courses_limit );
		}

		if ( $courses_per_page === 0 ) {
			$courses_per_page = - 1;
		}

		$filter               = new LP_Course_Filter();
		$settings['order_by'] = $courses_order_by;
		Courses::handle_params_for_query_courses( $filter, $settings );
		// Term
		$filter->term_ids = array_merge( $filter->term_ids, $courses_category_ids );
		// End term
		$total_rows         = 0;
		$filter->limit      = $courses_per_page;
		$courses            = Courses::get_courses( $filter, $total_rows );
		$total_pages_result = LP_Database::get_total_pages( $courses_per_page, $total_rows );
		if ( $total_pages > 0 ) {
			if ( $total_pages_result > $total_pages ) {
				if ( $paged === $total_pages && $total_pages > 1 ) {
					$number_courses_residual = $courses_limit - $courses_per_page * ( $paged - 1 );
					$courses                 = array_slice( $courses, 0, $number_courses_residual );
				}

				$total_rows = $courses_limit;
			} else {
				$total_pages = $total_pages_result;
			}
		} else {
			$total_pages = $total_pages_result;
		}

		// Handle layout
		ob_start();
		$data_rs     = [
			'total_rows'       => $total_rows,
			'paged'            => $paged,
			'courses_per_page' => $courses_per_page,
		];

		$section_top = [
			'wrapper'         => '<div class="learn-press-elms-courses-top">',
			'el_result_count' => $show_el_result_count ? $listCoursesTemplate->html_courses_page_result( $data_rs ) : '',
			'el_sorting'      => $show_el_sorting ? $listCoursesTemplate->html_order_by( $courses_order_by ) : '',
			'close_wrapper'   => '</div>',
		];
		echo Template::combine_components( $section_top );

		$html_lis = '';
		foreach ( $courses as $courseObj ) {
			$course = learn_press_get_course( $courseObj->ID );
			$html_lis .= static::render_course( $course, $settings );
		}
		$section_list = [
			'wrapper' => sprintf( '<ul class="learn-press-courses lp-list-courses-no-css %s">', esc_attr( $skin ) ),
			'list'    => $html_lis,
			'wrapper_end' => '</ul>',
		];
		echo Template::combine_components( $section_list );

		$data_pagination = [
			'total_pages' => $total_pages,
			'type'        => $pagination_type,
			'base'        => add_query_arg( 'paged', '%#%', $settings['url_current'] ?? '' ),
			'paged'       => (int) ( $settings['paged'] ?? 1 ),
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
				'button' => [
					'text_html' => sprintf(
						'<div><a href="%s">%s</a></div>',
						$course->get_permalink(),
						__( 'View More', 'learnpress' )
					),
				],
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
