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
		$tab_current     = CourseBuilder::get_current_tab();
		$section_current = CourseBuilder::get_current_section();

		$layout = [
			'sidebar' => $this->sidebar(),
			'content' => '',
		];

		echo Template::combine_components( $layout );
	}

	public function sidebar() {
		$title           = '';
		$tab_current     = CourseBuilder::get_current_tab();
		$section_current = CourseBuilder::get_current_section();
		// error_log( print_r( $section_current, true ) );
		$tabs        = CourseBuilder::get_tabs_arr();
		$nav_content = '';
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
			'wrapper'     => '<aside id="course-builder-sidebar">',
			'title'       => sprintf( '<h1>%s</h1>', $title ),
			'nav'         => Template::combine_components( $nav ),
			'wrapper_end' => '</aside>',
		];

		return Template::combine_components( $sidebar );
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
			error_log( $tab );

			$title = $tab_data['title'];
			$slug  = $tab_data['slug'];
			$link  = CourseBuilder::get_tab_link( $slug );
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

	public function html_tab_courses() {
		return 'Course';
	}

	public function html_tab_lessons() {
		return 'Lessons';
	}


	public function html_btn_edit() {
		$tab_current = CourseBuilder::get_current_tab();

		$btn = [
			'wrapper'     => '<button class="lp-button">',
			'content'     => __( 'Edit', 'learnpress' ),
			'wrapper_end' => '</button>',
		];

		return Template::combine_components( $btn );
	}
}
