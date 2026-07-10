<?php

namespace LearnPress\MCP\Domain;

use LearnPress\MCP\Mappers\ResponseMapper;
use LearnPress\MCP\Support\Errors;
use LearnPress\MCP\Support\Permissions;
use LearnPress\MCP\Support\Sanitizer;
use LearnPress\MCP\Support\Validator;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\CourseSectionModel;
use Throwable;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Section create/update/delete executors.
 *
 * Uses CoursePostModel + CourseSectionModel curriculum APIs. Delete is a
 * relationship-safe removal that preserves contained lesson/quiz posts and
 * returns recovery metadata.
 */
class SectionTools {

	/**
	 * Execute `learnpress/create-section`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function create_section( $input ) {
		$args      = is_array( $input ) ? $input : array();
		$course_id = Validator::require_id( $args, 'course_id' );
		if ( is_wp_error( $course_id ) ) {
			return $course_id;
		}

		$name = Sanitizer::text( $args['name'] ?? '' );
		if ( '' === $name ) {
			return Errors::invalid( __( 'name is required.', 'learnpress' ) );
		}

		$course_post = CoursePostModel::find( $course_id, true );
		if ( ! $course_post instanceof CoursePostModel ) {
			return Errors::not_found( __( 'Course not found.', 'learnpress' ) );
		}

		if ( ! Permissions::can_edit_post( $course_id ) ) {
			return Errors::forbidden();
		}

		try {
			$section = $course_post->add_section(
				array(
					'section_name'        => $name,
					'section_description' => $args['description'] ?? '',
				)
			);

			if ( array_key_exists( 'order', $args ) ) {
				self::reorder_section( $course_post, $course_id, $section->get_section_id(), absint( $args['order'] ) );
			}

			$fresh = CourseSectionModel::find( $section->get_section_id(), $course_id, false );
			$fresh = $fresh instanceof CourseSectionModel ? $fresh : $section;

			return array( 'section' => ResponseMapper::section( $fresh ) );
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Execute `learnpress/update-section`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function update_section( $input ) {
		$args      = is_array( $input ) ? $input : array();
		$course_id = Validator::require_id( $args, 'course_id' );
		if ( is_wp_error( $course_id ) ) {
			return $course_id;
		}
		$section_id = Validator::require_id( $args, 'section_id' );
		if ( is_wp_error( $section_id ) ) {
			return $section_id;
		}

		$section = Validator::find_section( $section_id, $course_id );
		if ( is_wp_error( $section ) ) {
			return $section;
		}

		if ( ! Permissions::can_edit_post( $course_id ) ) {
			return Errors::forbidden();
		}

		$course_post = CoursePostModel::find( $course_id, true );
		if ( ! $course_post instanceof CoursePostModel ) {
			return Errors::not_found( __( 'Course not found.', 'learnpress' ) );
		}

		try {
			$data = array();
			if ( array_key_exists( 'name', $args ) ) {
				$data['section_name'] = Sanitizer::text( $args['name'] );
			}
			if ( array_key_exists( 'description', $args ) ) {
				$data['section_description'] = Sanitizer::html( $args['description'] );
			}

			if ( ! empty( $data ) ) {
				$course_post->update_section( $section, $data );
			}

			if ( array_key_exists( 'order', $args ) ) {
				self::reorder_section( $course_post, $course_id, $section_id, absint( $args['order'] ) );
			}

			$fresh = CourseSectionModel::find( $section_id, $course_id, false );
			$fresh = $fresh instanceof CourseSectionModel ? $fresh : $section;

			return array( 'section' => ResponseMapper::section( $fresh ) );
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Execute `learnpress/delete-section` (relationship-safe removal).
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function delete_section( $input ) {
		$args      = is_array( $input ) ? $input : array();
		$course_id = Validator::require_id( $args, 'course_id' );
		if ( is_wp_error( $course_id ) ) {
			return $course_id;
		}
		$section_id = Validator::require_id( $args, 'section_id' );
		if ( is_wp_error( $section_id ) ) {
			return $section_id;
		}

		$section = Validator::find_section( $section_id, $course_id );
		if ( is_wp_error( $section ) ) {
			return $section;
		}

		if ( ! Permissions::can_edit_post( $course_id ) ) {
			return Errors::forbidden();
		}

		try {
			$affected_items = self::collect_section_items( $course_id, $section_id );

			$recovery = array(
				'course_id'      => $course_id,
				'section_id'     => $section_id,
				'name'           => (string) $section->section_name,
				'description'    => (string) $section->section_description,
				'previous_order' => (int) $section->section_order,
				'affected_items' => $affected_items,
				'note'           => __( 'Section relationship removed. Lesson/quiz posts are preserved and can be re-added using the affected_items metadata.', 'learnpress' ),
			);

			$section->delete();

			return array(
				'removed'        => true,
				'section_id'     => $section_id,
				'course_id'      => $course_id,
				'affected_items' => $affected_items,
				'recovery'       => $recovery,
			);
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Move a section to a 1-based position within its course.
	 *
	 * @param CoursePostModel $course_post Course post model.
	 * @param int             $course_id   Course ID.
	 * @param int             $section_id  Section being moved.
	 * @param int             $position    Desired 1-based position.
	 *
	 * @return void
	 */
	protected static function reorder_section( CoursePostModel $course_post, int $course_id, int $section_id, int $position ): void {
		$course = CourseModel::find( $course_id, false );
		if ( ! $course instanceof CourseModel ) {
			return;
		}
		$course->sections_items = null; // Force a fresh read from the relationship tables, not the JSON snapshot.

		$ids = array();
		foreach ( $course->get_section_items() as $section ) {
			$ids[] = absint( $section->section_id ?? 0 );
		}
		$ids = array_values( array_filter( $ids ) );

		$ids   = array_values( array_diff( $ids, array( $section_id ) ) );
		$index = max( 0, min( count( $ids ), $position - 1 ) );
		array_splice( $ids, $index, 0, array( $section_id ) );

		$course_post->update_sections_position( array( 'new_position' => $ids ) );
	}

	/**
	 * Collect items belonging to a section, with previous order.
	 *
	 * @param int $course_id  Course ID.
	 * @param int $section_id Section ID.
	 *
	 * @return array
	 */
	protected static function collect_section_items( int $course_id, int $section_id ): array {
		$course = CourseModel::find( $course_id, false );
		if ( ! $course instanceof CourseModel ) {
			return array();
		}
		$course->sections_items = null; // Force a fresh read from the relationship tables, not the JSON snapshot.

		$items = array();
		foreach ( $course->get_section_items() as $section ) {
			if ( absint( $section->section_id ?? 0 ) !== $section_id ) {
				continue;
			}
			$section_items = is_array( $section->items ?? null ) ? $section->items : array();
			foreach ( $section_items as $item ) {
				$items[] = array(
					'item_id'    => absint( $item->item_id ?? 0 ),
					'item_type'  => (string) ( $item->item_type ?? '' ),
					'item_order' => absint( $item->item_order ?? 0 ),
				);
			}
		}

		return $items;
	}
}
