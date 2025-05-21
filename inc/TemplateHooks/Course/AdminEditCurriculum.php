<?php

namespace LearnPress\TemplateHooks\Course;

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseSectionModel;
use LearnPress\Models\CourseModel;

use LP_Section_DB;
use stdClass;

/**
 * Template hooks Admin Edit Course Curriculum.
 *
 * @since 4.2.8.6
 * @version 1.0.0
 */
class AdminEditCurriculum {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	/**
	 * Allow callback for AJAX.
	 * @use self::render_html_comments
	 *
	 * @param array $callbacks
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = get_class( $this ) . ':render_html';

		return $callbacks;
	}

	/**
	 * Render string to data content
	 *
	 * @param array $data
	 *
	 * @return stdClass
	 * @throws Exception
	 */
	public static function render_html( array $data ): stdClass {
		$content          = new stdClass();
		$content->content = '';

		$can_handle = false;

		if ( current_user_can( 'manage_options' ) ) {
			$can_handle = true;
		}

		if ( ! $can_handle ) {
			throw new Exception( __( 'You do not have permission to access this page.', 'learnpress' ) );
		}

		$action = $data['action'] ?? '';
		if ( is_callable( self::class, $action ) ) {
			$content = call_user_func( [ self::class, $action ], $data );
		} else {
			throw new Exception( __( 'Action not found', 'learnpress' ) );
		}

		if ( ! isset( $content->content ) ) {
			$content->content = '';
		}

		return $content;
	}

	/**
	 * Add section
	 *
	 * @throws Exception
	 */
	public static function add_section( $data ): stdClass {
		$response = new stdClass();

		$course_id   = $data['course_id'] ?? 0;
		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		$section_title = $data['title'] ?? '';
		if ( empty( $section_title ) ) {
			throw new Exception( __( 'Section title is required', 'learnpress' ) );
		}

		// Get max section order
		$max_order = LP_Section_DB::getInstance()->get_last_number_order( $course_id );

		$sectionNew                    = new CourseSectionModel();
		$sectionNew->section_name      = $section_title;
		$sectionNew->section_course_id = $course_id;
		$sectionNew->section_order     = $max_order + 1;
		$sectionNew->save();

		$response->section = $sectionNew;
		$response->message = __( 'Section added successfully', 'learnpress' );

		return $response;
	}

	/**
	 * Update section
	 *
	 * @throws Exception
	 */
	public static function update_section( $data ): stdClass {
		$response    = new stdClass();
		$course_id   = $data['course_id'] ?? 0;
		$section_id  = $data['section_id'] ?? 0;
		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		$courseSectionModel = CourseSectionModel::find( $section_id );
		if ( ! $courseSectionModel ) {
			throw new Exception( __( 'Section not found', 'learnpress' ) );
		}

		foreach ( $data as $key => $value ) {
			if ( property_exists( $courseSectionModel, $key ) ) {
				$courseSectionModel->{$key} = $value;
			}
		}

		$courseSectionModel->save();

		$response->message = __( 'Section updated successfully', 'learnpress' );

		return $response;
	}

	/**
	 * Update section
	 *
	 * @throws Exception
	 */
	public static function delete_section( $data ): stdClass {
		$response    = new stdClass();
		$course_id   = $data['course_id'] ?? 0;
		$section_id  = $data['section_id'] ?? 0;
		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		$courseSectionModel = CourseSectionModel::find( $section_id );
		if ( ! $courseSectionModel ) {
			throw new Exception( __( 'Section not found', 'learnpress' ) );
		}

		$courseSectionModel->delete();

		$response->message = __( 'Section updated successfully', 'learnpress' );

		return $response;
	}
}
