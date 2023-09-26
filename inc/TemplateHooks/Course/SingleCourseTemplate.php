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
use LP_Datetime;
use Throwable;

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
	 * Get description course.
	 *
	 * @param LP_Course $course
	 *
	 * @return string
	 */
	public function html_description( LP_Course $course ): string {
		$html_wrapper = [
			'<p class="course-description">' => '</p>',
		];
		return Template::instance()->nest_elements( $html_wrapper, $course->get_data( 'description' ) );
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
	 * @param string $string_type not has prefix 'lp_'
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_count_item( LP_Course $course, string $string_type, array $data = [] ): string {
		$post_type_item = 'lp_' . $string_type;
		$count_item     = $course->count_items( $post_type_item );

		switch ( $post_type_item ) {
			case LP_LESSON_CPT:
				$content = sprintf( '%d %s', $count_item, _n( 'Lesson', 'Lessons', $count_item ) );
				break;
			case LP_QUIZ_CPT:
				$content = sprintf( '%d %s', $count_item, _n( 'Quiz', 'Quizzes', $count_item ) );
				break;
			case 'lp_assignment':
				$content = sprintf( '%d %s', $count_item, _n( 'Assignment', 'Assignments', $count_item ) );
				break;
			case 'lp_h5p':
				$content = sprintf( '%d %s', $count_item, _n( 'H5P', 'H5Ps', $count_item ) );
				break;
			default:
				$content = '';
				break;
		}

		$html_wrapper = [
			'<div class="course-count-' . $string_type . '">' => '</div>',
		];

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Get html level course.
	 *
	 * @param LP_Course $course
	 *
	 * @version 1.0.0
	 * @since 4.2.3.5
	 * @return string
	 */
	public function html_level( LP_Course $course ): string {
		$content = '';

		try {
			$level  = $course->get_level();
			$levels = lp_course_level();
			$level  = $levels[ $level ] ?? $levels[''];

			$html_wrapper = [
				'<span class="course-level">' => '</span>',
			];
			$content      = Template::instance()->nest_elements( $html_wrapper, $level );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html duration course.
	 *
	 * @param LP_Course $course
	 *
	 * @version 1.0.0
	 * @since 4.2.3.5
	 * @return string
	 */
	public function html_duration( LP_Course $course ): string {
		$content = '';

		try {
			$duration        = $course->get_duration();
			$duration_arr    = explode( ' ', $duration );
			$duration_number = $duration_arr[0] ?? 0;
			$duration_type   = $duration_arr[1] ?? '';
			$duration_str    = LP_Datetime::get_string_plural_duration( $duration_number, $duration_type );

			$html_wrapper = [
				'<span class="course-duration">' => '</span>',
			];
			$content      = Template::instance()->nest_elements( $html_wrapper, $duration_str );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
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

		// render count items
		$pattern_count_items = '/{{course_count_.*?}}/';
		preg_match_all( $pattern_count_items, $data_content, $matches_count_items );
		if ( ! empty( $matches_count_items ) ) {
			$items = $matches_count_items[0];
			foreach ( $items as $item ) {
				$method         = str_replace( [ '{{', '}}' ], '', $item );
				$post_type_item = str_replace( 'course_count_', '', $method );
				$data_content   = str_replace( $item, $this->html_count_item( $course, $post_type_item ), $data_content );
			}
		}

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
				$singleInstructorTemplate->html_display_name( $author_of_course ),
				$author_of_course->get_url_instructor(),
				$singleInstructorTemplate->html_avatar( $author_of_course ),
			],
			$data_content
		);
	}
}
