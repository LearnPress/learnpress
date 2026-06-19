<?php

namespace LearnPress\MCP\Domain;

use LearnPress\MCP\Mappers\ResponseMapper;
use LearnPress\MCP\Support\Curriculum;
use LearnPress\MCP\Support\Errors;
use LearnPress\MCP\Support\Pagination;
use LearnPress\MCP\Support\Permissions;
use LearnPress\MCP\Support\Sanitizer;
use LearnPress\MCP\Support\Validator;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\CourseSectionModel;
use LearnPress\Models\LessonPostModel;
use LP_Material_Files_DB;
use Throwable;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Lesson create/update/delete executors.
 *
 * Creates lessons through the LearnPress section curriculum API and persists
 * lesson metadata. Delete is reversible (trash + relationship removal with
 * recovery metadata).
 */
class LessonTools {

	const VIDEO_INTRO_META = '_lp_lesson_video_intro';

	/**
	 * Execute `learnpress/create-lesson`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function create_lesson( $input ) {
		$args      = is_array( $input ) ? $input : array();
		$course_id = Validator::require_id( $args, 'course_id' );
		if ( is_wp_error( $course_id ) ) {
			return $course_id;
		}
		$section_id = Validator::require_id( $args, 'section_id' );
		if ( is_wp_error( $section_id ) ) {
			return $section_id;
		}

		$title = Sanitizer::text( $args['title'] ?? '' );
		if ( '' === $title ) {
			return Errors::invalid( __( 'title is required.', 'learnpress' ) );
		}

		$course = Validator::find_course( $course_id );
		if ( is_wp_error( $course ) ) {
			return $course;
		}
		$section = Validator::find_section( $section_id, $course_id );
		if ( is_wp_error( $section ) ) {
			return $section;
		}

		if ( ! Permissions::can_edit_post( $course_id ) ) {
			return Errors::forbidden();
		}

		$status = Validator::status( $args['status'] ?? '' );
		if ( is_wp_error( $status ) ) {
			return $status;
		}

		try {
			$section_item = $section->create_item_and_add(
				array(
					'item_type'    => LP_LESSON_CPT,
					'item_title'   => $title,
					'item_content' => Sanitizer::html( $args['content'] ?? '' ),
				)
			);

			$lesson_id = absint( $section_item->item_id );
			$lesson    = LessonPostModel::find( $lesson_id, true );
			if ( ! $lesson instanceof LessonPostModel ) {
				return Errors::internal();
			}

			// Default to draft on create (create_item_and_add publishes by default).
			self::apply_fields( $lesson, $args, '' !== $status ? $status : 'draft' );

			if ( array_key_exists( 'order', $args ) ) {
				$course_post = CoursePostModel::find( $course_id, true );
				if ( $course_post instanceof CoursePostModel ) {
					Curriculum::place_item( $course_post, $course_id, $lesson_id, $section_id, $section_id, absint( $args['order'] ) );
				}
			}

			$lesson = LessonPostModel::find( $lesson_id, true );

			return array(
				'lesson' => ResponseMapper::lesson_write_result(
					$lesson,
					array(
						'course_id'  => $course_id,
						'section_id' => $section_id,
					)
				),
			);
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Execute `learnpress/update-lesson`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function update_lesson( $input ) {
		$args      = is_array( $input ) ? $input : array();
		$lesson_id = Validator::require_id( $args, 'lesson_id' );
		if ( is_wp_error( $lesson_id ) ) {
			return $lesson_id;
		}

		$lesson = Validator::find_lesson( $lesson_id );
		if ( is_wp_error( $lesson ) ) {
			return $lesson;
		}

		if ( ! Permissions::can_edit_post( $lesson_id ) ) {
			return Errors::forbidden();
		}

		$status = Validator::status( $args['status'] ?? '' );
		if ( is_wp_error( $status ) ) {
			return $status;
		}

		$location = Curriculum::resolve_location( $lesson_id, LP_LESSON_CPT );

		try {
			if ( array_key_exists( 'title', $args ) ) {
				$lesson->post_title = Sanitizer::text( $args['title'] );
			}
			if ( array_key_exists( 'content', $args ) ) {
				$lesson->post_content = Sanitizer::html( $args['content'] );
			}
			self::apply_fields( $lesson, $args, $status );

			$move = self::move_within_curriculum( $lesson_id, $location, $args );
			if ( is_wp_error( $move ) ) {
				return $move;
			}
			$location = $move;

			$lesson = LessonPostModel::find( $lesson_id, true );

			return array( 'lesson' => ResponseMapper::lesson( $lesson, $location ) );
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Execute `learnpress/delete-lesson` (reversible trash).
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function delete_lesson( $input ) {
		$args      = is_array( $input ) ? $input : array();
		$lesson_id = Validator::require_id( $args, 'lesson_id' );
		if ( is_wp_error( $lesson_id ) ) {
			return $lesson_id;
		}

		$lesson = Validator::find_lesson( $lesson_id );
		if ( is_wp_error( $lesson ) ) {
			return $lesson;
		}

		if ( ! Permissions::can_delete_post( $lesson_id ) ) {
			return Errors::forbidden();
		}

		$hint_course = absint( $args['course_id'] ?? 0 );
		$location    = Curriculum::resolve_location( $lesson_id, LP_LESSON_CPT, $hint_course );

		if ( $hint_course > 0 && $location['course_id'] !== $hint_course ) {
			return Errors::not_found( __( 'Lesson does not belong to the provided course.', 'learnpress' ) );
		}
		if ( ! empty( $args['section_id'] ) && absint( $args['section_id'] ) !== $location['section_id'] ) {
			return Errors::not_found( __( 'Lesson does not belong to the provided section.', 'learnpress' ) );
		}

		try {
			$removed_from_curriculum = false;
			if ( $location['section_id'] > 0 ) {
				$removed_from_curriculum = Curriculum::remove_item_from_section( $location['section_id'], $lesson_id, $location['course_id'] );
			}

			$trashed = wp_trash_post( $lesson_id );
			if ( ! $trashed ) {
				return Errors::internal();
			}

			return array(
				'trashed'                 => true,
				'lesson_id'               => $lesson_id,
				'removed_from_curriculum' => $removed_from_curriculum,
				'recovery'                => array(
					'course_id'      => $location['course_id'],
					'section_id'     => $location['section_id'],
					'previous_order' => $location['item_order'],
					'item_type'      => LP_LESSON_CPT,
					'note'           => __( 'Lesson moved to trash. Restore from WP admin and re-add to the section to recover.', 'learnpress' ),
				),
			);
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Execute `learnpress/list-lessons`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function list_lessons( $input ) {
		$args = Sanitizer::input_array( $input, 'learnpress/list-lessons' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$course_id = Validator::require_id( $args, 'course_id' );
		if ( is_wp_error( $course_id ) ) {
			return $course_id;
		}

		$course = CourseModel::find( $course_id, true );
		if ( ! $course instanceof CourseModel ) {
			return Errors::not_found( __( 'Course not found.', 'learnpress' ) );
		}

		$section_id = absint( $args['section_id'] ?? 0 );
		$page       = Pagination::page( $args['page'] ?? 1 );
		$per_page   = Pagination::per_page( $args['per_page'] ?? 10 );
		$statuses   = Sanitizer::status_list( $args['status'] ?? null );
		$refs       = Curriculum::collect_items( $course, LP_LESSON_CPT, $section_id );

		$all = array();
		foreach ( $refs as $ref ) {
			$lesson = LessonPostModel::find( (int) $ref['item_id'], true );
			if ( ! $lesson instanceof LessonPostModel ) {
				continue;
			}
			if ( ! empty( $statuses ) && ! in_array( $lesson->post_status, $statuses, true ) ) {
				continue;
			}
			$all[] = ResponseMapper::lesson_summary( $lesson, $ref );
		}

		$total = count( $all );
		$items = array_slice( $all, ( $page - 1 ) * $per_page, $per_page );

		return array(
			'items'      => array_values( $items ),
			'pagination' => array(
				'page'        => $page,
				'per_page'    => $per_page,
				'total_items' => $total,
				'total_pages' => Pagination::total_pages( $total, $per_page ),
			),
		);
	}

	/**
	 * Execute `learnpress/get-lesson-details`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function get_lesson_details( $input ) {
		$args = Sanitizer::input_array( $input, 'learnpress/get-lesson-details' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$lesson_id = Validator::require_id( $args, 'lesson_id' );
		if ( is_wp_error( $lesson_id ) ) {
			return $lesson_id;
		}

		$lesson = LessonPostModel::find( $lesson_id, true );
		if ( ! $lesson instanceof LessonPostModel ) {
			return Errors::not_found( __( 'Lesson not found.', 'learnpress' ) );
		}

		$materials = array();
		if ( class_exists( 'LP_Material_Files_DB' ) ) {
			$materials_rs = LP_Material_Files_DB::getInstance()->get_material_by_item_id( $lesson_id, 0, 0, false );
			$materials_rs = is_array( $materials_rs ) ? $materials_rs : array();
			foreach ( $materials_rs as $material ) {
				$materials[] = array(
					'file_id'   => absint( $material->file_id ?? 0 ),
					'name'      => (string) ( $material->file_name ?? '' ),
					'type'      => (string) ( $material->file_type ?? '' ),
					'method'    => (string) ( $material->method ?? '' ),
					'file_path' => (string) ( $material->file_path ?? '' ),
					'url'       => (string) ( $material->file_url ?? '' ),
				);
			}
		}

		return array(
			'lesson' => array(
				'lesson_id'    => $lesson->get_id(),
				'title'        => (string) $lesson->get_the_title(),
				'content'      => (string) $lesson->get_the_content(),
				'excerpt'      => (string) $lesson->get_the_excerpt(),
				'duration'     => (string) $lesson->get_duration(),
				'video_intro'  => (string) $lesson->get_meta_value_by_key( self::VIDEO_INTRO_META, '' ),
				'preview'      => (bool) $lesson->has_preview(),
				'status'       => (string) $lesson->post_status,
				'permalink'    => (string) $lesson->get_permalink(),
				'materials'    => $materials,
				'materials_no' => count( $materials ),
			),
		);
	}

	/**
	 * Apply common lesson post-status and metadata fields.
	 *
	 * @param LessonPostModel $lesson Lesson model.
	 * @param array           $args   Input args.
	 * @param string          $status Validated status ('' when not provided).
	 *
	 * @return void
	 */
	protected static function apply_fields( LessonPostModel $lesson, array $args, string $status ): void {
		if ( array_key_exists( 'excerpt', $args ) ) {
			$lesson->post_excerpt = Sanitizer::html( $args['excerpt'] );
		}
		if ( '' !== $status ) {
			$lesson->post_status = $status;
		}

		$lesson->save();

		if ( array_key_exists( 'duration', $args ) ) {
			$lesson->save_meta_value_by_key( LessonPostModel::META_KEY_DURATION, Sanitizer::text( $args['duration'] ) );
		}
		if ( array_key_exists( 'preview', $args ) ) {
			$lesson->save_meta_value_by_key( LessonPostModel::META_KEY_PREVIEW, Sanitizer::boolean( $args['preview'] ) ? 'yes' : 'no' );
		}
		if ( array_key_exists( 'video_intro', $args ) ) {
			$lesson->save_meta_value_by_key( self::VIDEO_INTRO_META, Sanitizer::url( $args['video_intro'] ) );
		}
	}

	/**
	 * Move/reorder the lesson within the curriculum when requested.
	 *
	 * @param int   $lesson_id Lesson ID.
	 * @param array $location  Current location.
	 * @param array $args      Input args.
	 *
	 * @return array|WP_Error Updated location.
	 */
	protected static function move_within_curriculum( int $lesson_id, array $location, array $args ) {
		$has_section = array_key_exists( 'section_id', $args );
		$has_order   = array_key_exists( 'order', $args );
		if ( ! $has_section && ! $has_order ) {
			return $location;
		}

		$course_id = $location['course_id'];
		if ( $course_id <= 0 ) {
			return Errors::invalid( __( 'Lesson is not assigned to a course; cannot move or reorder.', 'learnpress' ) );
		}

		$old_section_id = $location['section_id'];
		$new_section_id = $has_section ? absint( $args['section_id'] ) : $old_section_id;

		if ( $has_section ) {
			$section = Validator::find_section( $new_section_id, $course_id );
			if ( is_wp_error( $section ) ) {
				return $section;
			}
		}

		$position    = $has_order ? absint( $args['order'] ) : 0;
		$course_post = CoursePostModel::find( $course_id, true );
		if ( $course_post instanceof CoursePostModel ) {
			Curriculum::place_item( $course_post, $course_id, $lesson_id, $new_section_id, $old_section_id, $position );
		}

		return Curriculum::resolve_location( $lesson_id, LP_LESSON_CPT, $course_id );
	}
}
