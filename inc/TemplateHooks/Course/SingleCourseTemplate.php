<?php
/**
 * Template hooks Single Course.
 *
 * @since 4.2.3
 * @version 1.0.1
 */
namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LP_Course;

class SingleCourseTemplate {
	use Singleton;

	public function init() {
		// TODO: Implement init() method.
	}

	public function sections( $data = [] ) {

	}

	/**
	 * Get display title course.
	 *
	 * @param LP_Course $course
	 *
	 * @return string
	 */
	public function html_title( LP_Course $course ): string {
		$html_wrapper = [
			'<span class="course-title">' => '</span>',
		];
		return Template::instance()->nest_elements( $html_wrapper, $course->get_title() );
	}

	/**
	 * Get short description course.
	 *
	 * @param LP_Course $course
	 *
	 * @return string
	 */
	public function html_short_description( LP_Course $course ): string {
		$html_wrapper = [
			'<p class="course-short-description">' => '</p>',
		];
		return Template::instance()->nest_elements( $html_wrapper, $course->get_data( 'excerpt' ) );
	}

	/**
	 * Get display title course.
	 *
	 * @param LP_Course $course
	 *
	 * @return string
	 */
	public function html_categories( LP_Course $course ): string {
		$html_wrapper = [
			'<div class="course-categories">' => '</div>',
		];

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
	 * @param LP_Course $course
	 *
	 * @return string
	 */
	public function html_image( LP_Course $course ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<div class="course-img">' => '</div>',
			];

			$content = $course->get_image();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch ( \Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get display price course.
	 *
	 * @param LP_Course $course
	 *
	 * @return string
	 */
	public function html_price( LP_Course $course ): string {
		return $course->get_course_price_html();
	}

	/**
	 * Render string to data content
	 *
	 * @param LP_Course $course
	 * @param string $data_content
	 *
	 * @return string
	 */
	public function render_data( LP_Course $course, string $data_content = '' ): string {
		$author_of_course         = $course->get_author();
		$singleInstructorTemplate = SingleInstructorTemplate::instance();

		return str_replace(
			[
				'{{course_id}}',
				'{{course_title}}',
				'{{course_image}}',
				'{{course_url}}',
				'{{course_short_description}}',
				'{{course_price}}',
				'{{course_author_string}}',
				'{{course_author_link}}',
				'{{course_author_avatar}}',
				'{{course_categories}}',
			],
			[
				$course->get_id(),
				$this->html_title( $course ),
				$this->html_image( $course ),
				$course->get_permalink(),
				$this->html_short_description( $course ),
				$this->html_price( $course ),
				$singleInstructorTemplate->html_display_name( $author_of_course ),
				$author_of_course->get_url_instructor(),
				$singleInstructorTemplate->html_avatar( $author_of_course ),
			],
			$data_content
		);
	}
}
