<?php

namespace LearnPress\Services;

use Exception;
use LearnPress\Databases\CourseSectionDB;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\CourseSectionModel;
use LP_Helper;
use LP_Section_DB;
use LP_Settings;
use stdClass;

/**
 * Class CourseService
 *
 * Create course with data.
 *
 * @package LearnPress\Services
 * @since 4.3.0
 * @version 1.0.0
 */
class CourseService {
	use Singleton;

	public function init() {
	}

	/**
	 * Create course info main
	 *
	 * @param array $data [ 'post_title' => '', 'post_content' => '', 'post_status' => '', 'post_author' => , ... ]
	 *
	 * @throws Exception
	 */
	public function create_info_main( array $data ): CoursePostModel {
		$coursePostModelNew = new CoursePostModel( $data );
		$coursePostModelNew->save();

		return $coursePostModelNew;
	}

	/**
	 * Create metadata for course
	 *
	 * @param CoursePostModel $coursePostModel
	 * @param array $data
	 */
	public function create_meta_data( CoursePostModel $coursePostModel, array $data ) {
		foreach ( $data as $key => $value ) {
			$coursePostModel->save_meta_value_by_key( $key, $value );
		}
	}

	/**
	 * Add section to course
	 *
	 * @throws Exception
	 */
	public function add_section( CoursePostModel $coursePostModel, array $data ): CourseSectionModel {
		$course_id    = $coursePostModel->get_id();
		$section_name = trim( $data['section_name'] ?? '' );
		if ( empty( $section_name ) ) {
			throw new Exception( __( 'Section title is required', 'learnpress' ) );
		}

		$section_description = LP_Helper::sanitize_params_submitted( $data['section_description'] ?? '', 'html' );

		// Get max section order
		$max_order = CourseSectionDB::getInstance()->get_last_number_order( $course_id );

		$sectionNew                      = new CourseSectionModel();
		$sectionNew->section_name        = $section_name;
		$sectionNew->section_description = $section_description;
		$sectionNew->section_course_id   = $course_id;
		$sectionNew->section_order       = $max_order + 1;
		$sectionNew->save();

		return $sectionNew;
	}

	/**
	 * Update section
	 *
	 * @throws Exception
	 * @since  4.3.0
	 * @version 1.0.0
	 */
	public static function update_section( CourseSectionModel $courseSectionModel, array $data ) {
		foreach ( $data as $key => $value ) {
			if ( $key !== 'section_id' && property_exists( $courseSectionModel, $key ) ) {
				$courseSectionModel->{$key} = $value;
			}
		}

		$courseSectionModel->save();
	}

	/**
	 * Update sections position
	 * new_position => list of section id by order
	 *
	 * JS file edit-section.js: function sortAbleSection call this method.
	 *
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.2.8.6
	 */
	public static function update_section_position( array $data ) {
		$new_position = $data['new_position'] ?? [];
		if ( ! is_array( $new_position ) ) {
			throw new Exception( __( 'Invalid section position', 'learnpress' ) );
		}

		$course_id   = $data['course_id'] ?? 0;
		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		CourseSectionDB::getInstance()->update_sections_position( $new_position, $course_id );

		$courseModel->sections_items = null;
		$courseModel->save();
	}
}
