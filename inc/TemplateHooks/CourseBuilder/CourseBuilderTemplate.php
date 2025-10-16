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
		$back_btn        = '';
		if ( ! empty( $section_current ) ) {
			$section_data = $tabs[ $tab_current ]['sections'] ?? [];
			$link_tab     = CourseBuilder::get_tab_link( $tab_current );
			$tab_data     = CourseBuilder::get_data( $tab_current );
			$title_tab    = $tab_data['title'];

			$back_btn = sprintf( '<div class="cb-btn-back"><a href="%s">%s</a></div>', $link_tab, __( 'Back to', 'learnpress' ) . ' ' . $title_tab );
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
			'back_btn'    => $back_btn,
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
			echo $this->html_section( $tab_current, $section_current );
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
		do_action( "learn-press/course-builder/{$tab}/layout" );
		$content = ob_get_clean();

		$tab = [
			'wrapper'     => '<div class="lp-course-builder-content__tab">',
			'title'       => sprintf( '<h3 class="lp-cb-tab__title">%s</h3>', $title ),
			'content'     => $content,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $tab );
	}

	public function html_section( $tab, $section ) {
		ob_start();
		do_action( "learn-press/course-builder/{$tab}/{$section}/layout" );
		$content = ob_get_clean();

		$tab = [
			'wrapper'     => '<div class="lp-course-builder-content__section">',
			'content'     => $content,
			'wrapper_end' => '</div>',
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
}
