<?php
/**
 * Template hooks Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder;

use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseOfflineTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Course_Filter;
use LP_Global;
use LP_Page_Controller;
use LP_Settings;
use Throwable;

class CourseBuilderTemplate {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
		add_action( 'learn-press/course-builder/layout', [ $this, 'layout' ] );
	}

	/**
	 * Allow callback for AJAX.
	 * @use self::render_html_comments
	 * @param array $callbacks
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = get_class( $this ) . ':sidebar';

		return $callbacks;
	}

	public function layout() {
		wp_enqueue_style( 'lp-course-builder' );
		wp_enqueue_style( 'learnpress' );

		$profile = LP_Global::profile();

		if ( ! is_user_logged_in() ) {
			echo Template::print_message(
				sprintf( '<a href="%s">%s</a>', $profile->get_login_url(), __( 'Authentication required', 'learnpress' ) ),
				'warning',
				false
			);
			return;
		} else {
			$user = UserModel::find( get_current_user_id(), true );
			if ( ! $user->is_instructor() ) {
				echo Template::print_message(
					sprintf( __( "Sorry, you don't have permission to perform this action", 'learnpress' ) ),
					'warning',
					false
				);
				return;
			}
		}

		$layout = [
			'sidebar' => $this->sidebar(),
			'content' => $this->content(),
		];

		echo Template::combine_components( $layout );
	}

	public function sidebar() {
		$title           = '';
		$tab_current     = CourseBuilder::get_current_tab();
		$section_current = CourseBuilder::get_current_section();
		$tabs            = CourseBuilder::get_tabs_arr();
		$nav_content     = '';
		if ( ! empty( $section_current ) ) {
			$section_data = $tabs[ $tab_current ]['sections'] ?? [];
			foreach ( $section_data as $section ) {
				$slug         = $section['slug'];
				$id           = CourseBuilder::get_post_id();
				$nav_item     = $this->html_nav_item( $tab_current, $id, $slug );
				$nav_content .= $nav_item;
			}
		} else {
			$title = __( 'LearnPress Course Builder', 'learnpress' );
			foreach ( $tabs as $tab ) {
				$slug         = $tab['slug'];
				$nav_item     = $this->html_nav_item( $slug );
				$nav_content .= $nav_item;
			}
		}

		$nav = [
			'wrapper'     => '<ul>',
			'content'     => $nav_content,
			'wrapper_end' => '</ul>',
		];

		$sidebar = [
			'wrapper'     => '<aside id="lp-course-builder-sidebar">',
			'title'       => sprintf( '<h1>%s</h1>', $title ),
			'nav'         => Template::combine_components( $nav ),
			'wrapper_end' => '</aside>',
		];

		return Template::combine_components( $sidebar );
	}

	public function content() {
		$tab_current     = CourseBuilder::get_current_tab();
		$section_current = CourseBuilder::get_current_section();

		ob_start();
		if ( ! empty( $section_current ) ) {
			do_action( "learn-press/course-builder/{$tab_current}/{$section_current}/layout" );
		} else {
			echo $this->html_tab( $tab_current );
		}

		$content = ob_get_clean();

		$output = [
			'wrapper'     => '<div id="lp-course-builder-content">',
			'content'     => $content,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $output );
	}

	public function html_nav_item( $tab = '', $post_id = '', $section = '' ) {
		if ( ! $tab ) {
			return '';
		}

		$tab_data = CourseBuilder::get_data( $tab );
		if ( empty( $tab_data ) ) {
			return '';
		}

		$tab_current     = CourseBuilder::get_current_tab();
		$section_current = CourseBuilder::get_current_section();
		$classes         = [ 'lp-course-builder_nav-item' ];

		$content = '';
		if ( $section ) {
			$classes[]    = $section === $section_current ? $section . ' active' : $section;
			$section_data = $tab_data['sections'][ $section ];
			$title        = $section_data['title'];
			$slug         = $section_data['slug'];
			$link         = CourseBuilder::get_tab_link( $tab, $post_id, $section );
		} else {
			$classes[] = $tab === $tab_current ? $tab . ' active' : $tab;
			$title     = $tab_data['title'];
			$slug      = $tab_data['slug'];
			$link      = CourseBuilder::get_tab_link( $slug );
		}

		$content = sprintf(
			'<a href="%s"><span>%s</span></a>',
			esc_url_raw( $link ),
			$title,
		);

		$item = apply_filters(
			'learn-press/course-builder/nav-item',
			[
				'wrapper'     => sprintf( '<li class="%s">', implode( ' ', $classes ) ),
				'content'     => $content,
				'wrapper_end' => '</li>',
			],
			$tab,
			$post_id,
			$section
		);

		return Template::combine_components( $item );
	}

	public function html_tab( $tab ) {
		$tab_data = CourseBuilder::get_data( $tab );
		$title    = $tab_data['title'];

		ob_start();
		$method = 'html_tab_' . $tab;
		if ( method_exists( $this, $method ) ) {
			echo $this->$method();
		} else {
			do_action( "learn-press/course-builder/{$tab}/layout" );
		}
		$content = ob_get_clean();

		$tab = [
			'wrapper'     => '<div class="lp-course-builder-content__tab">',
			'title'       => sprintf( '<h3 class="lp-cb-tab__title">%s</h3>', $title ),
			'content'     => $content,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $tab );
	}

	public function html_tab_courses() {
		$list_course = $this->tab_list_courses();
		$btn         = $this->html_btn_add_new();

		$tab = [
			'wrapper'     => '',
			'btn'         => $btn,
			'courses'     => $list_course,
			'wrapper_end' => '',
		];

		return Template::combine_components( $tab );
	}

	public function html_tab_lessons() {
		$list_lesson = '';
		$btn         = $this->html_btn_add_new();
		$tab         = [
			'wrapper'     => '',
			'btn'         => $btn,
			'lessons'     => $list_lesson,
			'wrapper_end' => '',
		];

		return Template::combine_components( $tab );
	}

	public function html_tab_quizzes() {
		$list_quiz = '';
		$btn       = $this->html_btn_add_new();
		$tab       = [
			'wrapper'     => '',
			'btn'         => $btn,
			'quizzes'     => $list_quiz,
			'wrapper_end' => '',
		];

		return Template::combine_components( $tab );
	}

	public function html_tab_questions() {
		$list_question = '';
		$btn           = $this->html_btn_add_new();
		$tab           = [
			'wrapper'     => '',
			'btn'         => $btn,
			'questions'   => $list_question,
			'wrapper_end' => '',
		];

		return Template::combine_components( $tab );
	}

	public function html_btn_add_new() {
		$tab_current = CourseBuilder::get_current_tab();
		$tab_data    = CourseBuilder::get_data( $tab_current );
		$title       = $tab_data['title'];

		$link_tab     = CourseBuilder::get_tab_link( $tab_current );
		$link_add_new = trailingslashit( $link_tab . 'post-new' );

		$btn = [
			'wrapper'     => sprintf( '<a href="%s" class="lp-button cb-btn-add-new">', esc_url_raw( $link_add_new ) ),
			'content'     => sprintf( '%s %s', __( 'Add New', 'learnpress' ), $title ),
			'wrapper_end' => '</a>',
		];

		return Template::combine_components( $btn );
	}


	public function tab_list_courses(): string {
		$content = '';

		try {
			$user = UserModel::find( get_current_user_id(), true );
			// Query courses of user
			$filter = new LP_Course_Filter();
			Courses::handle_params_for_query_courses( $filter, [] );
			$filter->post_author = $user->get_id();
			$filter->limit       = 10;
			$filter->page        = $GLOBALS['wp_query']->get( 'paged', 1 ) ? $GLOBALS['wp_query']->get( 'paged', 1 ) : 1;
			$filter              = apply_filters( 'lp/course-builder/courses/query/filter', $filter, [] );

			$total_courses = 0;
			$courses       = Courses::get_courses( $filter, $total_courses );
			if ( ! empty( $courses ) ) {
				$html_courses = $this->list_courses( $courses );
			} else {
				$html_courses = Template::print_message(
					sprintf(
						__( '%s does not have any courses', 'learnpress' ),
						$user->get_display_name()
					),
					'info',
					false
				);
			}

			$sections = apply_filters(
				'learn-press/course-builder/courses/sections',
				[
					'wrapper'     => '<div class="courses-builder__course-tab learn-press-courses">',
					'courses'     => $html_courses,
					'pagination'  => $this->courses_pagination( $filter->page, $filter->limit, $total_courses ),
					'wrapper_end' => '</div>',
				],
				$courses,
				$user
			);

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Display list courses.
	 *
	 * @param $instructor
	 * @param $courses
	 *
	 * @return string
	 */
	public function list_courses( $courses ): string {
		$content = '';

		try {
			$html_list_course = '';
			foreach ( $courses as $course_obj ) {
				$course            = CourseModel::find( $course_obj->ID, true );
				$html_list_course .= self::render_course( $course );
			}

			$sections = [
				'wrapper'     => '<ul class="cb-list-course">',
				'list_course' => $html_list_course,
				'wrapper_end' => '</ul>',
			];

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Render course in course builder
	 *
	 * @param CourseModel
	 * @param array $settings
	 *
	 * @return string
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function render_course( CourseModel $course, array $settings = [] ): string {
		$singleCourseTemplate = SingleCourseTemplate::instance();

		try {
			$html_img = apply_filters(
				'learn-press/layout/list-courses/item/section-top',
				[
					'wrapper'     => '<div class="course-thumbnail">',
					'img'         => $singleCourseTemplate->html_image( $course ),
					'wrapper_end' => '</div>',
				],
				$course,
				$settings
			);

			$meta_data = apply_filters(
				'learn-press/layout/list-courses/item/meta-data',
				[
					'duration' => $singleCourseTemplate->html_duration( $course ),
					'level'    => $singleCourseTemplate->html_level( $course ),
					'lesson'   => $singleCourseTemplate->html_count_item( $course, LP_LESSON_CPT ),
					'quiz'     => $singleCourseTemplate->html_count_item( $course, LP_QUIZ_CPT ),
					'student'  => $singleCourseTemplate->html_count_student( $course ),
				],
				$course,
				$settings
			);

			if ( $course->is_offline() ) {
				$singleCourseOfflineTemplate = SingleCourseOfflineTemplate::instance();
				unset( $meta_data['quiz'] );
				unset( $meta_data['student'] );
				if ( ! empty( $meta_data['lesson'] ) ) {
					$meta_data['lesson'] = $singleCourseOfflineTemplate->html_lesson_info( $course, true );
				}

				// Add address for offline course.
				$html_address = $singleCourseOfflineTemplate->html_address( $course );
				if ( ! empty( $html_address ) ) {
					$meta_data['address'] = $singleCourseOfflineTemplate->html_address( $course );
				}
			}

			$html_meta_data = '';
			if ( ! empty( $meta_data ) ) {
				foreach ( $meta_data as $k => $v ) {
					$html_meta_data .= sprintf( '<div class="meta-item meta-item-%s">%s</div>', $k, $v );
				}

				$html_meta_data = sprintf( '<div class="course-wrap-meta">%s</div>', $html_meta_data );
			}

			$html_status = sprintf( '<div class="course-status">%s</div>', $course->get_status() );

			$section_bottom_end = apply_filters(
				'learn-press/layout/list-courses/item/section/bottom/end',
				[
					'short_des'   => $singleCourseTemplate->html_short_description( $course ),
					'wrapper'     => '<div class="course-info">',
					'price'       => $singleCourseTemplate->html_price( $course ),
					'wrapper_end' => '</div>',
				],
				$course,
				$settings
			);

			$html_info = apply_filters(
				'learn-press/layout/list-courses/item/section/bottom',
				[
					'wrapper'                     => '<div class="course-content">',
					'title'                       => sprintf(
						'<h3 class="wap-course-title">%s</h3>',
						$singleCourseTemplate->html_title( $course )
					),
					'featured'                    => $singleCourseTemplate->html_featured( $course ),
					'wrapper_instructor_cate'     => '<div class="course-instructor-category">',
					'instructor'                  => sprintf(
						'<div>%s %s</div>',
						sprintf( '<label>%s</label>', __( 'by', 'learnpress' ) ),
						$singleCourseTemplate->html_instructor( $course, false, [ 'is_link' => false ] )
					),
					'wrapper_instructor_cate_end' => '</div>',
					'meta'                        => $html_meta_data,
					'info'                        => Template::combine_components( $section_bottom_end ),
					'wrapper_end'                 => '</div>',
				],
				$course,
				$settings
			);

			$html_action = apply_filters(
				'learn-press/course-builder/list-courses/item/action',
				[
					'wrapper'              => '<div class="course-action">',
					'edit'                 => sprintf(
						'<h3 class="wap-course-title"><a class="course-permalink" href="%s">%s</a></h3>',
						$course->get_permalink(),
						__( 'Edit', 'learnpress' )
					),
					'action_expanded'      => '...',
					'action_expanded_item' => '...',
					'wrapper_end'          => '</div>',
				],
				$course,
				$settings
			);

			$section = apply_filters(
				'learn-press/layout/list-courses/item-li',
				[
					'wrapper_li'      => '<li class="course">',
					'wrapper_div'     => sprintf( '<div class="course-item" data-id="%s">', esc_attr( $course->get_id() ) ),
					'media'           => Template::combine_components( $html_img ),
					'course_info'     => Template::combine_components( $html_info ),
					'course_action'   => Template::combine_components( $html_action ),
					'wrapper_div_end' => '</div>',
					'wrapper_li_end'  => '</li>',
				],
				$course,
				$settings
			);

			$html_item = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			$html_item = $e->getMessage();
		}

		return $html_item;
	}

	/**
	 * Pagination courses.
	 *
	 * @param int $page
	 * @param int $limit
	 * @param int $total_courses
	 *
	 * @return string
	 */
	public function courses_pagination( int $page, int $limit, int $total_courses ): string {
		$content = '';

		try {
			$total_pages = \LP_Database::get_total_pages( $limit, $total_courses );
			$link_tab    = CourseBuilder::get_tab_link( 'courses' );
			$base_url    = $link_tab . '/page/%#%';
			$base_url    = preg_replace( '#/+#', '/', $base_url );

			$data_pagination = array(
				'total'    => $total_pages,
				'current'  => max( 1, $page ),
				'base'     => esc_url_raw( $base_url ),
				'format'   => '',
				'per_page' => $limit,
			);

			ob_start();
			Template::instance()->get_frontend_template( 'shared/pagination.php', $data_pagination );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}
}
