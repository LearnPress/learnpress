<?php
/**
 * Template hooks Single Item's Course.
 *
 * @since 4.2.6.9.
 * @version 1.0.1
 */

namespace LearnPress\TemplateHooks\ItemOfCourse;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LP_Course;
use LP_Datetime;
use LP_Page_Controller;
use Throwable;
use WP_Post;

class SingleItemCourseTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/single-item-of-course/layout-no-header-footer', [
			$this,
			'layout_no_header_footer'
		] );
	}

	public function layout_no_header_footer() {
		$item_course_data = LP_Page_Controller::is_page_single_item();
		if ( ! $item_course_data ) {
			return;
		}
		/**
		 * @var CourseModel $course
		 */
		$course = $item_course_data['course'] ?? false;
		if ( empty( $course ) ) {
			return;
		}

		$data_find                       = [
			'user_id'   => get_current_user_id(),
			'item_id'   => $course->get_id(),
			'item_type' => LP_COURSE_CPT,
		];
		$userCourseModel                 = UserCourseModel::find( $data_find );
		$item_course_data['user_course'] = $userCourseModel;

		$html_wrapper = [
			'<div id="popup-course" class="course-summary">' => '</div>',
		];
		$section      = apply_filters(
			'learn-press/single-item-of-course/layout-no-header-footer/section',
			[
				'header'  => [ 'text_html' => $this->header_section( $item_course_data ) ],
				'sidebar' => [ 'text_html' => $this->sidebar_section( $item_course_data ) ],
				'content' => [ 'text_html' => $this->content_section( $item_course_data ) ],
				'footer'  => [ 'text_html' => $this->footer_section( $item_course_data ) ],
			],
			$item_course_data
		);

		ob_start();
		Template::instance()->print_sections( $section );
		$content = ob_get_clean();

		echo Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Get header html single item's course
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function header_section( array $data = [] ): string {
		/**
		 * @var CourseModel $course
		 */
		$course = $data['course'] ?? false;
		/**
		 * @var WP_Post $item
		 */
		$item = $data['item'] ?? false;
		if ( empty( $course ) && empty( $item ) ) {
			return '';
		}
		/**
		 * @var false|UserCourseModel $user_course
		 */
		$user_course = $data['user_course'] ?? false;

		$btn_toggle = sprintf(
			'<input type="checkbox" id="sidebar-toggle" title="%s" />',
			esc_html__( 'Show/Hide curriculum', 'learnpress' )
		);
		$btn_back   = sprintf(
			'<a href="%s" class="back-course" title="%s"><i class="lp-icon-times"></i></a>',
			esc_url( $course->get_permalink() ),
			esc_html__( 'Back to Course', 'learnpress' )
		);

		$html_item_progress = '';
		if ( $user_course && $user_course->has_enrolled_or_finished() ) {
			$course_results        = $user_course->calculate_course_results();
			$html_process          = sprintf(
				'<div class="learn-press-progress">
					<div class="learn-press-progress__active" data-value="%s" title="%s"></div>
				</div>',
				esc_attr( $course_results['result'] ?? 0 ),
				esc_attr( sprintf( 'Progress passing grade %s%%', $course_results['result'] ?? 0 ) )
			);
			$item_progress_section = [
				'wrapper_start' => sprintf( '<div class="items-progress" data-total-items="%s">', $course_results['count_items'] ),
				'count_info'    => sprintf(
					'<span class="number"><span class="items-completed">%1$s</span> of %2$d items</span>',
					esc_html( $course_results['completed_items'] ?? 0 ),
					esc_html( $course_results['count_items'] ?? 0 )
				),
				'progress'      => $html_process,
				'wrapper_ned'   => '</div>',
			];

			$html_item_progress = Template::combine_components( $item_progress_section );
		}

		$header_inner = apply_filters(
			'learn-press/single-item-of-course/layout-no-header-footer/header_section/inner',
			[
				'wrapper_start'  => '<div class="popup-header__inner">',
				'course_title'   => sprintf(
					'<h2 class="course-title"><a href="%s">%s</a></h2>',
					esc_url( $course->get_permalink() ),
					$course->get_title()
				),
				'items_progress' => $html_item_progress,
				'wrapper_end'    => '</div>',
			],
			$data
		);

		$header_inner = Template::combine_components( $header_inner );

		$section = apply_filters(
			'learn-press/single-item-of-course/layout-no-header-footer/header_section',
			[
				'btn-toggle'   => $btn_toggle,
				'header-inner' => $header_inner,
				'btn-back'     => $btn_back,
			]
		);

		$content = Template::combine_components( $section );

		$html_wrapper = [
			'<div id="popup-header">' => '</div>',
		];

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	public function sidebar_section( $data ) {
		return '';
	}

	public function content_section( $data ) {
		return '';
	}

	public function footer_section( $data ) {
		return '';
	}
}
