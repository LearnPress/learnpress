<?php
/**
 * Template hooks Archive Package.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Template;

class SingleCourseTemplate {
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function __construct() {

	}

	public function sections( $data = [] ) {

	}

	/**
	 * Get display title course.
	 *
	 * @param \LP_Course $course
	 *
	 * @return string
	 */
	public function html_title( \LP_Course $course ): string {
		$html_wrapper = apply_filters(
			'learn-press/single-course/title/wrapper',
			[
				'<span class="course-title">' => '</span>',
			]
		);
		return Template::instance()->nest_elements( $html_wrapper, $course->get_title() );
	}

	/**
	 * Get display title course.
	 *
	 * @param \LP_Course $course
	 *
	 * @return string
	 */
	public function html_categories( \LP_Course $course ): string {
		$html_wrapper = apply_filters(
			'learn-press/single-course/categories/wrapper',
			[
				'<div class="course-categories">' => '</div>',
			]
		);

		$cats      = $course->get_categories();
		$cat_names = [];
		array_map(
			function( $cat ) use ( &$cat_names ) {
				$term        = sprintf( '<a href="%s">%s</a>', get_term_link( $cat->term_id ), $cat->name );
				$cat_names[] = $term;
			},
			$cats
		);

		$content = implode( ', ', $cat_names );

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Get display title course.
	 *
	 * @param \LP_Course $course
	 *
	 * @return string
	 */
	public function html_image( \LP_Course $course ): string {
		$content = '';

		try {
			$html_wrapper = apply_filters(
				'learn-press/single-course/img/wrapper',
				[
					'<div class="course-img">' => '</div>',
				],
				$course
			);

			$content = $course->get_image();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch ( \Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}
}
