<?php
/**
 * Class SetupDemoCourseService
 *
 * Import the bundled Setup Wizard demo courses.
 *
 * @since 4.4.6
 * @version 1.0.0
 */

namespace LearnPress\Services;

use Exception;
use LearnPress\Helpers\Config;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\PostModel;
use LP_Helper;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class SetupDemoCourseService
 *
 * Load, validate, normalize, and import demo courses for the Setup Wizard.
 *
 * @since 4.4.7
 * @version 1.0.0
 */
class SetupDemoCourseService {
	/**
	 * @var string
	 */
	protected $json_data;

	/**
	 * Initialize the demo course data from the Setup Wizard configuration.
	 */
	public function __construct() {
		$this->json_data = Config::instance()->get( 'demo-data-courses', 'setup-wizard' );
	}

	/**
	 * Read and validate all demo courses before importing.
	 *
	 * @return array Normalized demo courses.
	 * @throws Exception If the demo course data is invalid or empty.
	 */
	public function load_courses(): array {
		$data = LP_Helper::json_decode( $this->json_data, true );
		if ( empty( $data['courses'] ) ) {
			throw new Exception( __( 'Demo course data has not been added yet.', 'learnpress' ) );
		}

		$courses = array();
		foreach ( $data['courses'] as $course ) {
			$normalized = $this->normalize_course( $course );
			$courses[]  = $normalized;
		}

		return $courses;
	}

	/**
	 * Import one course and return progress for the client.
	 *
	 * @param int $index Zero-based index of the course to import.
	 *
	 * @return array Import progress and created course data.
	 * @throws Exception If the requested course does not exist or cannot be loaded.
	 * @throws Throwable
	 */
	public function import_course( int $index ): array {
		$courses = $this->load_courses();
		$total   = count( $courses );
		if ( $index < 0 || $index >= $total ) {
			throw new Exception( __( 'The requested demo course does not exist.', 'learnpress' ) );
		}

		$course    = $courses[ $index ];
		$course_id = $this->persist_course( $course );
		$processed = $index + 1;

		return array(
			'index'     => $processed,
			'total'     => $total,
			'title'     => $course['post_title'],
			'percent'   => (int) round( $processed / $total * 100 ),
			'result'    => 'created',
			'course_id' => $course_id,
			'complete'  => $processed === $total,
			'view_url'  => admin_url( 'edit.php?post_type=lp_course' ),
		);
	}

	/**
	 * Normalize one course from the JSON contract.
	 *
	 * @param mixed $course Raw course data.
	 *
	 * @return array Normalized course data.
	 * @throws Exception If the course or its lessons are invalid.
	 */
	protected function normalize_course( $course ): array {
		if ( ! is_array( $course ) ) {
			throw new Exception( __( 'Each demo course must be an object.', 'learnpress' ) );
		}

		$title = sanitize_text_field( $course['title'] ?? '' );
		if ( '' === $title ) {
			throw new Exception( __( 'Every demo course requires a title.', 'learnpress' ) );
		}

		$section = $course['section'] ?? array();
		if ( ! is_array( $section ) || empty( $section['title'] ) || ! isset( $section['lessons'] ) || ! is_array( $section['lessons'] ) ) {
			throw new Exception( __( 'Every demo course requires one valid section.', 'learnpress' ) );
		}

		$lessons = array();
		foreach ( $section['lessons'] as $lesson ) {
			$lesson_title = is_array( $lesson ) ? sanitize_text_field( $lesson['title'] ?? '' ) : '';
			if ( '' === $lesson_title ) {
				throw new Exception( __( 'Every demo lesson requires a title.', 'learnpress' ) );
			}

			$lessons[] = array(
				'post_title'   => $lesson_title,
				'post_content' => wp_kses_post( $lesson['content'] ?? '' ),
				'meta_input'   => array(
					LessonPostModel::META_KEY_DURATION => sanitize_text_field( $lesson['duration'] ?? '' ),
				),
			);
		}

		$status = sanitize_key( $course['status'] ?? 'publish' );
		if ( ! in_array( $status, array( 'draft', 'publish' ), true ) ) {
			$status = 'publish';
		}

		return array(
			'post_title'   => $title,
			'post_content' => wp_kses_post( $course['content'] ?? '' ),
			'post_excerpt' => sanitize_textarea_field( $course['excerpt'] ?? '' ),
			'post_status'  => $status,
			'section'      => array(
				'title'       => sanitize_text_field( $section['title'] ),
				'description' => wp_kses_post( $section['description'] ?? '' ),
				'lessons'     => $lessons,
			),
		);
	}

	/**
	 * Persist one normalized course.
	 *
	 * @param array $course Normalized course data.
	 *
	 * @return int Created course ID.
	 * @throws Throwable If the course, section, or lessons cannot be persisted.
	 */
	protected function persist_course( array $course ): int {
		$lesson_models = array();
		$section_data  = $course['section'];

		unset( $course['section'] );

		$course['post_type']   = LP_COURSE_CPT;
		$course['post_author'] = get_current_user_id();
		$course['meta_input']  = array(
			CoursePostModel::META_KEY_SAMPLE_DATA => 'yes',
		);

		$courseModel = CourseService::instance()->create_info_main( $course );

		$section = $courseModel->add_section(
			array(
				'section_name'        => $section_data['title'],
				'section_description' => $section_data['description'],
			)
		);

		foreach ( $section_data['lessons'] as $lesson ) {
			$lesson['post_status'] = PostModel::STATUS_PUBLISH;
			$lesson['post_author'] = get_current_user_id();
			$lesson['post_type']   = LP_LESSON_CPT;
			$lesson['meta_input'][ CoursePostModel::META_KEY_SAMPLE_DATA ] = 'yes';

			$lesson_model            = new LessonPostModel( $lesson );
			$lesson_model->meta_data = (object) $lesson['meta_input'];
			$lesson_model->save();
			$section->add_items(
				array(
					'items' => array(
						array(
							'id'   => $lesson_model->get_id(),
							'type' => LP_LESSON_CPT,
						),
					),
				)
			);
		}

		return $courseModel->get_id();
	}
}
