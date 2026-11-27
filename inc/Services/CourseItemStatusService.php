<?php

namespace LearnPress\Services;

use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CourseSectionItemModel;
use LP_Background_Single_Course;
use LP_Debug;
use LP_Section_Items_DB;
use LP_Section_Items_Filter;
use LP_User_Items_DB;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Keep curriculum data of courses in sync when a course item changes status or is deleted.
 *
 * - Item changes status (any status):
 *   item keeps its position in sections, courses contain item are refreshed
 *   (json snapshot: sections_items, sections_items_edit, total_items, first_item_id, final quiz + caches).
 * - Item title changed: courses contain item are refreshed (titles in snapshot).
 * - Item deleted permanently: item is removed from all sections, courses are refreshed.
 *
 * Courses are refreshed once per request, on shutdown.
 *
 * @since 4.3.3
 * @version 1.0.0
 */
class CourseItemStatusService {
	use Singleton;

	/**
	 * Course ids need refresh curriculum data.
	 *
	 * @var int[]
	 */
	protected $course_ids_queued = [];

	public function init() {
		add_action( 'transition_post_status', [ $this, 'on_transition_post_status' ], 10, 3 );
		add_action( 'post_updated', [ $this, 'on_post_updated' ], 10, 3 );
		add_action( 'before_delete_post', [ $this, 'on_before_delete_post' ], 10, 2 );
		add_action( 'shutdown', [ $this, 'refresh_courses_queued' ] );
	}

	/**
	 * Item change status: queue courses contain item to refresh.
	 * Any status change: publish side changes frontend curriculum (sections_items),
	 * all statuses change edit curriculum (sections_items_edit).
	 *
	 * @param string $new_status
	 * @param string $old_status
	 * @param \WP_Post|object $post
	 *
	 * @return void
	 */
	public function on_transition_post_status( $new_status, $old_status, $post ) {
		try {
			if ( $new_status === $old_status || ! is_object( $post ) ) {
				return;
			}

			$post_type = $post->post_type ?? '';
			if ( ! $this->is_course_item_type( $post_type ) ) {
				return;
			}

			$this->queue_courses( $this->get_course_ids_of_item( (int) $post->ID, $post_type ) );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}

	/**
	 * Item title changed: queue courses contain item to refresh (titles saved on sections_items, sections_items_edit).
	 *
	 * @param int $post_id
	 * @param \WP_Post|object $post_after
	 * @param \WP_Post|object $post_before
	 *
	 * @return void
	 */
	public function on_post_updated( $post_id, $post_after, $post_before ) {
		try {
			if ( ! is_object( $post_after ) || ! is_object( $post_before ) ) {
				return;
			}

			if ( ( $post_after->post_title ?? '' ) === ( $post_before->post_title ?? '' ) ) {
				return;
			}

			$post_type = $post_after->post_type ?? '';
			if ( ! $this->is_course_item_type( $post_type ) ) {
				return;
			}

			$this->queue_courses( $this->get_course_ids_of_item( (int) $post_id, $post_type ) );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}

	/**
	 * Item deleted permanently: remove item from sections, queue courses to refresh.
	 *
	 * @param int $post_id
	 * @param \WP_Post|object|null $post
	 *
	 * @return void
	 */
	public function on_before_delete_post( $post_id, $post = null ) {
		try {
			$post_id   = (int) $post_id;
			$post_type = $post->post_type ?? get_post_type( $post_id );
			if ( ! $this->is_course_item_type( (string) $post_type ) ) {
				return;
			}

			$course_ids = $this->get_course_ids_of_item( $post_id, (string) $post_type );
			if ( empty( $course_ids ) ) {
				return;
			}

			$this->remove_item_from_sections( $post_id, $course_ids );
			$this->queue_courses( $course_ids );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}

	/**
	 * Refresh curriculum data of courses queued.
	 *
	 * @return void
	 */
	public function refresh_courses_queued() {
		$course_ids              = $this->course_ids_queued;
		$this->course_ids_queued = [];

		foreach ( $course_ids as $course_id ) {
			$this->refresh_course( $course_id );
		}
	}

	/**
	 * Get queued course ids.
	 *
	 * @return int[]
	 */
	public function get_course_ids_queued(): array {
		return array_values( $this->course_ids_queued );
	}

	/**
	 * Queue course ids (unique).
	 *
	 * @param int[] $course_ids
	 *
	 * @return void
	 */
	protected function queue_courses( array $course_ids ) {
		foreach ( $course_ids as $course_id ) {
			$course_id = (int) $course_id;
			if ( $course_id > 0 ) {
				$this->course_ids_queued[ $course_id ] = $course_id;
			}
		}
	}

	/**
	 * Check post type is item type of course.
	 *
	 * @param string $post_type
	 *
	 * @return bool
	 */
	protected function is_course_item_type( string $post_type ): bool {
		return '' !== $post_type && in_array( $post_type, CourseModel::item_types_support(), true );
	}

	/**
	 * Get course ids contain item.
	 *
	 * @param int $item_id
	 * @param string $item_type
	 *
	 * @return int[]
	 */
	protected function get_course_ids_of_item( int $item_id, string $item_type ): array {
		$rows       = CourseSectionItemModel::get_courses_from_item_id( $item_id, $item_type );
		$course_ids = [];

		foreach ( $rows as $row ) {
			$course_id = is_object( $row ) ? (int) ( $row->section_course_id ?? 0 ) : (int) $row;
			if ( $course_id > 0 ) {
				$course_ids[ $course_id ] = $course_id;
			}
		}

		return array_values( $course_ids );
	}

	/**
	 * Remove item from all sections, reset current item of users on courses.
	 *
	 * @param int $item_id
	 * @param int[] $course_ids
	 *
	 * @return void
	 */
	protected function remove_item_from_sections( int $item_id, array $course_ids ) {
		$filter           = new LP_Section_Items_Filter();
		$filter->item_ids = [ $item_id ];
		LP_Section_Items_DB::getInstance()->delete_items( $filter );

		foreach ( $course_ids as $course_id ) {
			// Old hook, keep for compatible with LP_Course_CURD::remove_item().
			do_action( 'learn-press/removed-item-from-section', $item_id, $course_id );
			LP_User_Items_DB::getInstance()->reset_course_current_item( $course_id, $item_id );
		}
	}

	/**
	 * Recalculate curriculum data of course: sections_items (only published items, frontend),
	 * sections_items_edit (all statuses, edit curriculum), save json and clear caches.
	 * Not call LP_Course_Post_Type::save_post() because it reads request params of course form.
	 *
	 * @param int $course_id
	 *
	 * @return void
	 */
	protected function refresh_course( int $course_id ) {
		try {
			$courseModel = CourseModel::find( $course_id );
			if ( ! $courseModel instanceof CourseModel ) {
				return;
			}

			unset( $courseModel->first_item_id );
			unset( $courseModel->total_items );
			unset( $courseModel->sections_items );
			unset( $courseModel->sections_items_edit );
			if ( isset( $courseModel->meta_data ) ) {
				unset( $courseModel->meta_data->_lp_final_quiz );
			}

			$courseModel->get_first_item_id();
			$courseModel->get_total_items();
			$courseModel->get_section_items();
			$courseModel->get_section_items_for_edit();
			$courseModel->get_final_quiz();
			$courseModel->save( true );

			LP_Background_Single_Course::clean_cache_courses();

			do_action( 'learn-press/course/curriculum/refreshed', $courseModel );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}
}
