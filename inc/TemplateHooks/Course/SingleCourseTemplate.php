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
	 * Get display total student's course.
	 *
	 * @param LP_Course $course
	 *
	 * @return string
	 */
	public function html_count_student( LP_Course $course ): string {
		$count_student = $course->get_total_user_enrolled_or_purchased();
		$ico_student   = sprintf(
			'<span class="course-ico student">%s</span>',
			wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-students.svg' )
		);
		$ico_student   = '';
		$content       = sprintf( '%s %d %s', $ico_student, $count_student, _n( 'Student', 'Students', $count_student ) );
		$html_wrapper  = [
			'<div class="course-count-student">' => '</div>',
		];

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Get display total lesson's course.
	 *
	 * @param LP_Course $course
	 *
	 * @return string
	 */
	public function html_count_lesson( LP_Course $course ): string {
		$count_lesson = $course->count_items( LP_LESSON_CPT );
		$ico_lesson   = sprintf(
			'<span class="course-ico lesson">%s</span>',
			wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-file.svg' )
		);
		$ico_lesson   = '';
		$content      = sprintf( '%s %d %s', $ico_lesson, $count_lesson, _n( 'Lesson', 'Lessons', $count_lesson ) );
		$html_wrapper = [
			'<div class="course-count-lesson">' => '</div>',
		];

		return Template::instance()->nest_elements( $html_wrapper, $content );
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
				'{{course_categories}}',
				'{{course_count_student}}',
				'{{course_count_lesson}}',
				'{{course_author_display_name}}',
				'{{course_author_url}}',
				'{{course_author_avatar}}',
			],
			[
				$course->get_id(),
				$this->html_title( $course ),
				$this->html_image( $course ),
				$course->get_permalink(),
				$this->html_short_description( $course ),
				$this->html_price( $course ),
				$this->html_categories( $course ),
				$this->html_count_student( $course ),
				$this->html_count_lesson( $course ),
				$singleInstructorTemplate->html_display_name( $author_of_course ),
				$author_of_course->get_url_instructor(),
				$singleInstructorTemplate->html_avatar( $author_of_course ),
			],
			$data_content
		);
	}
}
