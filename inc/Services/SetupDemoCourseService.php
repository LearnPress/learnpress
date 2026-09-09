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
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\LessonPostModel;
use LP_WP_Filesystem;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class SetupDemoCourseService
 */
class SetupDemoCourseService {
	public const META_KEY_SOURCE_ID = '_lp_setup_demo_course_slug';

	/**
	 * Legacy constant retained for backward compatibility.
	 *
	 * @deprecated 4.4.6 Use META_KEY_SOURCE_ID instead.
	 */
	public const META_KEY_SOURCE_SLUG = self::META_KEY_SOURCE_ID;

	/**
	 * @var string
	 */
	protected $json_file;
	/**
	 * Optional file reader used by isolated tests.
	 *
	 * @var callable|null
	 */
	protected $file_reader;

	/**
	 * @param string        $json_file   JSON source path.
	 * @param callable|null $file_reader Optional callback for reading the JSON source.
	 */
	public function __construct( string $json_file = '', ?callable $file_reader = null ) {
		$this->json_file   = '' !== $json_file ? $json_file : LP_PLUGIN_PATH . '/inc/admin/setup/demo-data-courses.json';
		$this->file_reader = $file_reader;
	}

	/**
	 * Read and validate all demo courses before importing.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function load_courses(): array {
		if ( is_callable( $this->file_reader ) ) {
			$content = call_user_func( $this->file_reader, $this->json_file );
		} else {
			$file_system = LP_WP_Filesystem::instance();
			$content     = $file_system->is_readable( $this->json_file )
				? $file_system->file_get_contents( $this->json_file )
				: false;
		}

		if ( false === $content ) {
			throw new Exception( __( 'The demo course data file is missing.', 'learnpress' ) );
		}

		$data = json_decode( (string) $content, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) || ! isset( $data['courses'] ) || ! is_array( $data['courses'] ) ) {
			throw new Exception( __( 'The demo course data file is invalid.', 'learnpress' ) );
		}

		if ( empty( $data['courses'] ) ) {
			throw new Exception( __( 'Demo course data has not been added yet.', 'learnpress' ) );
		}

		$courses = array();
		$titles  = array();
		foreach ( $data['courses'] as $course ) {
			$normalized = $this->normalize_course( $course );
			$title_key  = sanitize_title( $normalized['title'] );
			if ( isset( $titles[ $title_key ] ) ) {
				throw new Exception( __( 'Every demo course must have a unique title.', 'learnpress' ) );
			}

			$titles[ $title_key ] = true;
			$courses[]            = $normalized;
		}

		return $courses;
	}

	/**
	 * Import one course and return progress for the client.
	 *
	 * @throws Exception
	 */
	public function import_course( int $index ): array {
		$courses = $this->load_courses();
		$total   = count( $courses );
		if ( $index < 0 || $index >= $total ) {
			throw new Exception( __( 'The requested demo course does not exist.', 'learnpress' ) );
		}

		$course      = $courses[ $index ];
		$existing_id = $this->find_existing_course( sanitize_title( $course['title'] ) );
		$course_id   = $existing_id ? $existing_id : $this->persist_course( $course );
		$processed   = $index + 1;

		return array(
			'index'     => $processed,
			'total'     => $total,
			'title'     => $course['title'],
			'percent'   => (int) round( $processed / $total * 100 ),
			'result'    => $existing_id ? 'existing' : 'created',
			'course_id' => $course_id,
			'complete'  => $processed === $total,
			'view_url'  => admin_url( 'edit.php?post_type=lp_course' ),
		);
	}

	/**
	 * Normalize one course from the JSON contract.
	 *
	 * @throws Exception
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

		if ( 5 !== count( $section['lessons'] ) ) {
			throw new Exception( __( 'Every demo course section must contain exactly five lessons.', 'learnpress' ) );
		}

		$lessons = array();
		foreach ( $section['lessons'] as $lesson ) {
			$lesson_title = is_array( $lesson ) ? sanitize_text_field( $lesson['title'] ?? '' ) : '';
			if ( '' === $lesson_title ) {
				throw new Exception( __( 'Every demo lesson requires a title.', 'learnpress' ) );
			}

			$lessons[] = array(
				'title'    => $lesson_title,
				'content'  => wp_kses_post( $lesson['content'] ?? '' ),
				'duration' => sanitize_text_field( $lesson['duration'] ?? '' ),
			);
		}

		$status = sanitize_key( $course['status'] ?? 'publish' );
		if ( ! in_array( $status, array( 'draft', 'publish' ), true ) ) {
			$status = 'publish';
		}

		return array(
			'title'   => $title,
			'content' => wp_kses_post( $course['content'] ?? '' ),
			'excerpt' => sanitize_textarea_field( $course['excerpt'] ?? '' ),
			'status'  => $status,
			'section' => array(
				'title'       => sanitize_text_field( $section['title'] ),
				'description' => wp_kses_post( $section['description'] ?? '' ),
				'lessons'     => $lessons,
			),
		);
	}

	/**
	 * Find a course previously created from the same demo source.
	 *
	 * @param string $source_key Demo course identifier derived from its title.
	 *
	 * @return int
	 */
	protected function find_existing_course( string $source_key ): int {
		$ids = get_posts(
			array(
				'post_type'      => LP_COURSE_CPT,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'meta_key'       => self::META_KEY_SOURCE_ID,
				'meta_value'     => $source_key,
			)
		);

		return empty( $ids ) ? 0 : (int) $ids[0];
	}

	/**
	 * Persist one normalized course.
	 *
	 * @param array $course Normalized course data.
	 *
	 * @return int
	 * @throws Throwable
	 */
	protected function persist_course( array $course ): int {
		$lesson_models = array();
		$course_model  = CourseService::instance()->create_info_main(
			array(
				'post_title'   => $course['title'],
				'post_content' => $course['content'],
				'post_excerpt' => $course['excerpt'],
				'post_status'  => $course['status'],
				'post_type'    => LP_COURSE_CPT,
				'post_author'  => get_current_user_id(),
				'meta_input'   => array(
					CoursePostModel::META_KEY_SAMPLE_DATA => 'yes',
				),
			)
		);

		try {
			$section = $course_model->add_section(
				array(
					'section_name'        => $course['section']['title'],
					'section_description' => $course['section']['description'],
				)
			);

			foreach ( $course['section']['lessons'] as $lesson ) {
				$lesson_model               = new LessonPostModel();
				$lesson_model->post_title   = $lesson['title'];
				$lesson_model->post_content = $lesson['content'];
				$lesson_model->post_status  = 'publish';
				$lesson_model->post_author  = get_current_user_id();
				$lesson_model->post_type    = LP_LESSON_CPT;
				$lesson_model->meta_data    = (object) array(
					CoursePostModel::META_KEY_SAMPLE_DATA => 'yes',
					LessonPostModel::META_KEY_DURATION     => $lesson['duration'],
				);
				$lesson_model->save();
				$lesson_models[] = $lesson_model;
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

			update_post_meta( $course_model->get_id(), self::META_KEY_SOURCE_ID, sanitize_title( $course['title'] ) );
		} catch ( Throwable $error ) {
			foreach ( $lesson_models as $lesson_model ) {
				$lesson_model->delete();
			}
			$course_model->delete();
			throw $error;
		}

		return $course_model->get_id();
	}
}
