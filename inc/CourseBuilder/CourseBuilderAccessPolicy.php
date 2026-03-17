<?php

namespace LearnPress\CourseBuilder;

use LearnPress\Models\CourseModel;

/**
 * Centralized access policy for Course Builder object-level permissions.
 *
 * @since 4.3.0
 */
class CourseBuilderAccessPolicy {
	/**
	 * Check if current user can edit a specific course.
	 *
	 * @param int $course_id
	 *
	 * @return bool
	 */
	public static function can_edit_course_by_id( int $course_id ): bool {
		if ( $course_id <= 0 ) {
			return false;
		}

		$course_model = CourseModel::find( $course_id, true );
		if ( ! $course_model ) {
			return false;
		}

		return self::can_edit_course( $course_model );
	}

	/**
	 * Check if current user can access the tab/post pair in Course Builder.
	 *
	 * @param string       $tab
	 * @param int|string   $post_id
	 *
	 * @return bool
	 */
	public static function can_access_tab_post( string $tab, $post_id ): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$item_type = self::get_item_type_by_tab( $tab );
		if ( empty( $item_type ) ) {
			return false;
		}

		if ( (string) $post_id === CourseBuilder::POST_NEW ) {
			return self::can_create_item_type( $item_type );
		}

		$item_id = absint( $post_id );
		if ( $item_id <= 0 ) {
			return false;
		}

		if ( $item_type === LP_COURSE_CPT ) {
			return self::can_edit_course_by_id( $item_id );
		}

		return self::can_edit_item( $item_type, $item_id );
	}

	/**
	 * Check if current user can create a specific item type in Course Builder.
	 *
	 * @param string $item_type
	 *
	 * @return bool
	 */
	public static function can_create_item_type( string $item_type ): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$capability = self::resolve_create_capability_by_item_type( $item_type );
		if ( empty( $capability ) ) {
			return false;
		}

		return current_user_can( $capability );
	}

	/**
	 * Check if current user can edit a specific lesson/quiz/question item.
	 *
	 * @param string $item_type
	 * @param int    $item_id
	 *
	 * @return bool
	 */
	public static function can_edit_item( string $item_type, int $item_id ): bool {
		if ( ! is_user_logged_in() || $item_id <= 0 ) {
			return false;
		}

		if ( self::is_admin_user() ) {
			return true;
		}

		$post = get_post( $item_id );
		if ( ! $post || $post->post_type !== $item_type ) {
			return false;
		}

		$current_user_id = get_current_user_id();
		if ( absint( $post->post_author ) === $current_user_id ) {
			return true;
		}

		$course_id = self::get_course_id_by_item( $item_type, $item_id );
		if ( $course_id <= 0 ) {
			return false;
		}

		return self::can_edit_course_by_id( $course_id );
	}

	/**
	 * Check if current user can edit a course model by owner/co-instructor/admin.
	 *
	 * @param CourseModel $course_model
	 *
	 * @return bool
	 */
	private static function can_edit_course( CourseModel $course_model ): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( self::is_admin_user() ) {
			return true;
		}

		$current_user_id = get_current_user_id();
		if ( absint( $course_model->post_author ) === $current_user_id ) {
			return true;
		}

		$co_instructor_ids = $course_model->get_meta_value_by_key( '_lp_co_teacher', [] );
		if ( empty( $co_instructor_ids ) ) {
			$co_instructor_ids = get_post_meta( $course_model->ID, '_lp_co_teacher', false );
		}

		$co_instructor_ids = self::normalize_user_ids( $co_instructor_ids );

		return in_array( $current_user_id, $co_instructor_ids, true );
	}

	/**
	 * Find related course ID for item.
	 *
	 * @param string $item_type
	 * @param int    $item_id
	 *
	 * @return int
	 */
	private static function get_course_id_by_item( string $item_type, int $item_id ): int {
		static $cache = [];

		$cache_key = $item_type . ':' . $item_id;
		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		global $wpdb;

		$tb_sections      = $wpdb->learnpress_sections ?? ( $wpdb->prefix . 'learnpress_sections' );
		$tb_section_items = $wpdb->learnpress_section_items ?? ( $wpdb->prefix . 'learnpress_section_items' );

		$course_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT s.section_course_id
				FROM {$tb_sections} s
				INNER JOIN {$tb_section_items} si ON si.section_id = s.section_id
				WHERE si.item_id = %d
				ORDER BY si.section_id DESC
				LIMIT 1",
				$item_id
			)
		);

		// Questions belong to quizzes; resolve via quiz->course relation when needed.
		if ( $course_id <= 0 && $item_type === LP_QUESTION_CPT ) {
			$tb_quiz_questions = $wpdb->learnpress_quiz_questions ?? ( $wpdb->prefix . 'learnpress_quiz_questions' );

			$course_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT s.section_course_id
					FROM {$tb_quiz_questions} qq
					INNER JOIN {$tb_section_items} si ON si.item_id = qq.quiz_id
					INNER JOIN {$tb_sections} s ON s.section_id = si.section_id
					WHERE qq.question_id = %d
					ORDER BY si.section_id DESC
					LIMIT 1",
					$item_id
				)
			);
		}

		$cache[ $cache_key ] = absint( $course_id );

		return $cache[ $cache_key ];
	}

	/**
	 * Map Course Builder tab to post type.
	 *
	 * @param string $tab
	 *
	 * @return string
	 */
	private static function get_item_type_by_tab( string $tab ): string {
		switch ( $tab ) {
			case 'courses':
				return LP_COURSE_CPT;
			case 'lessons':
				return LP_LESSON_CPT;
			case 'quizzes':
				return LP_QUIZ_CPT;
			case 'questions':
				return LP_QUESTION_CPT;
			default:
				return '';
		}
	}

	/**
	 * Resolve create capability from registered post type capabilities.
	 * Falls back to legacy mapping when post type object is unavailable.
	 *
	 * @param string $item_type
	 *
	 * @return string
	 */
	private static function resolve_create_capability_by_item_type( string $item_type ): string {
		$post_type_object = get_post_type_object( $item_type );
		if ( $post_type_object && isset( $post_type_object->cap ) ) {
			$cap = $post_type_object->cap;
			if ( ! empty( $cap->create_posts ) ) {
				return $cap->create_posts;
			}

			if ( ! empty( $cap->edit_posts ) ) {
				return $cap->edit_posts;
			}
		}

		switch ( $item_type ) {
			case LP_COURSE_CPT:
				return 'edit_lp_courses';
			case LP_LESSON_CPT:
				return 'edit_lp_lessons';
			case LP_QUIZ_CPT:
				return 'edit_lp_quizzes';
			case LP_QUESTION_CPT:
				return 'edit_lp_questions';
			default:
				return '';
		}
	}

	/**
	 * Check if current user is admin.
	 *
	 * @return bool
	 */
	private static function is_admin_user(): bool {
		if ( defined( 'ADMIN_ROLE' ) ) {
			return current_user_can( ADMIN_ROLE );
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Normalize values to unique integer user IDs.
	 *
	 * @param mixed $ids
	 *
	 * @return array
	 */
	private static function normalize_user_ids( $ids ): array {
		if ( empty( $ids ) ) {
			return [];
		}

		if ( is_object( $ids ) ) {
			$ids = (array) $ids;
		}

		if ( ! is_array( $ids ) ) {
			$ids = [ $ids ];
		}

		$ids = array_map( 'absint', $ids );
		$ids = array_filter( $ids );
		$ids = array_values( array_unique( $ids ) );

		return $ids;
	}
}
