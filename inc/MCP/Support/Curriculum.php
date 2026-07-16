<?php

namespace LearnPress\MCP\Support;

use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\CourseSectionItemModel;

defined( 'ABSPATH' ) || exit;

/**
 * Curriculum-relationship helpers for MCP write tools.
 *
 * Resolves where a lesson/quiz lives in a course so delete tools can record
 * reversible recovery metadata before trashing a post.
 */
class Curriculum {

	/**
	 * Locate a curriculum item (lesson/quiz) inside a course.
	 *
	 * @param CourseModel $course  Course model.
	 * @param int         $item_id Lesson/quiz post ID.
	 *
	 * @return array|null { section_id, section_name, item_order, item_type } or null.
	 */
	public static function locate_item( CourseModel $course, int $item_id ) {
		foreach ( $course->get_section_items() as $section ) {
			$section_id   = absint( $section->section_id ?? 0 );
			$section_name = (string) ( $section->section_name ?? '' );
			$items        = is_array( $section->items ?? null ) ? $section->items : array();

			foreach ( $items as $item ) {
				if ( absint( $item->item_id ?? 0 ) === $item_id ) {
					return array(
						'section_id'   => $section_id,
						'section_name' => $section_name,
						'item_order'   => absint( $item->item_order ?? 0 ),
						'item_type'    => (string) ( $item->item_type ?? '' ),
					);
				}
			}
		}

		return null;
	}

	/**
	 * Resolve the first course ID that contains a given curriculum item.
	 *
	 * @param int    $item_id   Lesson/quiz post ID.
	 * @param string $item_type Item post type.
	 *
	 * @return int 0 when the item is not assigned to any course.
	 */
	public static function find_course_id_for_item( int $item_id, string $item_type ): int {
		$rows = CourseSectionItemModel::get_courses_from_item_id( $item_id, $item_type );
		foreach ( $rows as $row ) {
			$course_id = absint( $row->section_course_id ?? 0 );
			if ( $course_id > 0 ) {
				return $course_id;
			}
		}

		return 0;
	}

	/**
	 * Remove a lesson/quiz from a course section (relationship-only).
	 *
	 * @param int $section_id Section ID.
	 * @param int $item_id    Item ID.
	 * @param int $course_id  Course ID.
	 *
	 * @return bool True when a relationship row was found and removed.
	 */
	public static function remove_item_from_section( int $section_id, int $item_id, int $course_id = 0 ): bool {
		$relation = CourseSectionItemModel::find( $section_id, $item_id, true );
		if ( ! $relation instanceof CourseSectionItemModel ) {
			return false;
		}

		// CourseSectionItemModel::find() does not populate section_course_id, which
		// delete() needs for its capability check (otherwise it resolves course 0 and
		// CourseSectionItemModel::get_course_model() throws a TypeError). Supply it.
		if ( $course_id > 0 ) {
			$relation->section_course_id = $course_id;
		}

		$relation->delete();

		return true;
	}

	/**
	 * Resolve the curriculum location of a lesson/quiz.
	 *
	 * @param int    $item_id        Item post ID.
	 * @param string $item_type      Item post type.
	 * @param int    $hint_course_id Optional known course ID.
	 *
	 * @return array { course_id, section_id, section_name, item_order }
	 */
	public static function resolve_location( int $item_id, string $item_type, int $hint_course_id = 0 ): array {
		$location = array(
			'course_id'    => 0,
			'section_id'   => 0,
			'section_name' => '',
			'item_order'   => 0,
		);

		$course_id = $hint_course_id > 0 ? $hint_course_id : self::find_course_id_for_item( $item_id, $item_type );
		if ( $course_id <= 0 ) {
			return $location;
		}
		$location['course_id'] = $course_id;

		$course = CourseModel::find( $course_id, true );
		if ( ! $course instanceof CourseModel ) {
			return $location;
		}

		$found = self::locate_item( $course, $item_id );
		if ( is_array( $found ) ) {
			$location['section_id']   = (int) $found['section_id'];
			$location['section_name'] = (string) $found['section_name'];
			$location['item_order']   = (int) $found['item_order'];
		}

		return $location;
	}

	/**
	 * Place (move/reorder) a curriculum item to a 1-based position in a section.
	 *
	 * Works for same-section reordering (old == new) and cross-section moves.
	 *
	 * @param CoursePostModel $course_post    Course post model.
	 * @param int             $course_id      Course ID.
	 * @param int             $item_id        Item post ID.
	 * @param int             $new_section_id Target section ID.
	 * @param int             $old_section_id Current section ID.
	 * @param int             $position       Desired 1-based position; <= 0 appends.
	 *
	 * @return void
	 */
	public static function place_item(
		CoursePostModel $course_post,
		int $course_id,
		int $item_id,
		int $new_section_id,
		int $old_section_id,
		int $position
	): void {
		$course = CourseModel::find( $course_id, false );
		$ids    = array();
		if ( $course instanceof CourseModel ) {
			$course->sections_items = null; // Force a fresh read from the relationship tables, not the JSON snapshot.
			foreach ( $course->get_section_items() as $section ) {
				if ( absint( $section->section_id ?? 0 ) !== $new_section_id ) {
					continue;
				}
				$section_items = is_array( $section->items ?? null ) ? $section->items : array();
				foreach ( $section_items as $item ) {
					$ids[] = absint( $item->item_id ?? 0 );
				}
			}
		}

		$ids = array_values( array_filter( $ids ) );
		$ids = array_values( array_diff( $ids, array( $item_id ) ) );

		if ( $position > 0 ) {
			$index = max( 0, min( count( $ids ), $position - 1 ) );
			array_splice( $ids, $index, 0, array( $item_id ) );
		} else {
			$ids[] = $item_id;
		}

		$course_post->update_items_position(
			array(
				'items_position'         => $ids,
				'item_id_change'         => $item_id,
				'section_id_new_of_item' => $new_section_id,
				'section_id_old_of_item' => $old_section_id,
			)
		);
	}

	/**
	 * Collect a course's curriculum items by type, optionally filtered by section.
	 *
	 * @param CourseModel $course     Course model.
	 * @param string      $item_type  LearnPress item type.
	 * @param int         $section_id Optional section filter.
	 *
	 * @return array
	 */
	public static function collect_items( CourseModel $course, string $item_type, int $section_id = 0 ): array {
		$items = array();
		foreach ( $course->get_section_items() as $section ) {
			$current_section_id = absint( $section->section_id ?? $section->id ?? 0 );
			if ( $section_id > 0 && $section_id !== $current_section_id ) {
				continue;
			}

			foreach ( $section->items as $item ) {
				$current_type = (string) ( $item->item_type ?? $item->type ?? '' );
				if ( $item_type !== $current_type ) {
					continue;
				}
				$items[] = array(
					'course_id'    => $course->get_id(),
					'section_id'   => $current_section_id,
					'section_name' => (string) ( $section->section_name ?? $section->title ?? '' ),
					'item_id'      => absint( $item->item_id ?? $item->id ?? 0 ),
					'preview'      => ! empty( $item->preview ),
				);
			}
		}

		return $items;
	}
}
