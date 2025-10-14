<?php
/**
 * Template hooks Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder;

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
		$callbacks[] = get_class( $this ) . ':render_html_comments';

		return $callbacks;
	}

	public function layout() {
		$layout = [
			'sidebar' => $this->sidebar(),
			'content' => '',
		];

		echo Template::combine_components( $layout );
	}

	public function sidebar() {
		$title   = __( 'LearnPress Course Builder', 'learnpress' );
		$nav     = '';
		$sidebar = [
			'wrapper'     => '<aside id="course-builder-sidebar">',
			'title'       => $title,
			'nav'         => $nav,
			'wrapper_end' => '</aside>',
		];

		return Template::combine_components( $sidebar );
	}
}
