<?php

namespace LearnPress\MCP\Concerns;

use LearnPress\Models\CourseModel;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserModel;
use LP_Helper;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Shared helper methods used across ability executors.
 */
trait AbilityHelpers {
	/**
	 * Normalize ability input to array.
	 *
	 * @param mixed  $input   Raw ability input.
	 * @param string $ability Ability name for error context.
	 *
	 * @return array|WP_Error
	 */
	protected static function input_arr( $input, string $ability ) {
		if ( null === $input ) {
			return array();
		}
		if ( is_array( $input ) ) {
			return $input;
		}

		return self::invalid(
			sprintf(
				__( 'Invalid input for ability "%s". Input must be an object.', 'learnpress' ),
				$ability
			)
		);
	}

	/**
	 * Sanitize page number.
	 *
	 * @param mixed $value Raw page value.
	 *
	 * @return int
	 */
	protected static function page( $value ): int {
		$page = absint( $value );
		return $page > 0 ? $page : 1;
	}

	/**
	 * Sanitize per-page number and clamp to safe range.
	 *
	 * @param mixed $value Raw per-page value.
	 *
	 * @return int
	 */
	protected static function per_page( $value ): int {
		$per_page = absint( $value );
		if ( $per_page < 1 ) {
			$per_page = 10;
		}
		if ( $per_page > 100 ) {
			$per_page = 100;
		}

		return $per_page;
	}

	/**
	 * Normalize status input into unique sanitized status list.
	 *
	 * @param mixed $input String, CSV string, array, or null.
	 *
	 * @return array
	 */
	protected static function status_list( $input ): array {
		if ( null === $input || '' === $input ) {
			return array();
		}

		$values = is_array( $input )
			? $input
			: ( false !== strpos( (string) $input, ',' ) ? explode( ',', (string) $input ) : array( (string) $input ) );

		$out = array();
		foreach ( $values as $value ) {
			$status = LP_Helper::sanitize_params_submitted( (string) $value, 'key' );
			if ( '' !== $status ) {
				$out[] = $status;
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * Calculate total pages from total item count.
	 *
	 * @param int $total_items Total items.
	 * @param int $per_page    Items per page.
	 *
	 * @return int
	 */
	protected static function total_pages( int $total_items, int $per_page ): int {
		return $per_page > 0 ? (int) ceil( $total_items / $per_page ) : 0;
	}

	/**
	 * Build invalid input error.
	 *
	 * @param string $message Error message.
	 *
	 * @return WP_Error
	 */
	protected static function invalid( string $message ): WP_Error {
		return new WP_Error( 'lp_mcp_invalid_input', $message, array( 'status' => 400 ) );
	}

	/**
	 * Build not-found error.
	 *
	 * @param string $message Error message.
	 *
	 * @return WP_Error
	 */
	protected static function not_found( string $message ): WP_Error {
		return new WP_Error( 'lp_mcp_not_found', $message, array( 'status' => 404 ) );
	}

	/**
	 * Build generic internal error.
	 *
	 * @return WP_Error
	 */
	protected static function internal(): WP_Error {
		return new WP_Error(
			'lp_mcp_internal_error',
			__( 'An internal LearnPress MCP error occurred.', 'learnpress' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Map course model into ability response summary.
	 *
	 * @param CourseModel $course Course model.
	 *
	 * @return array
	 */
	protected static function course_summary( CourseModel $course ): array {
		$categories = array();
		foreach ( $course->get_categories() as $term ) {
			$categories[] = array(
				'term_id' => (int) $term->term_id,
				'name'    => (string) $term->name,
				'slug'    => (string) $term->slug,
			);
		}
		$author = $course->get_author_model();

		return array(
			'course_id'  => $course->get_id(),
			'title'      => (string) $course->get_title(),
			'status'     => (string) $course->get_status(),
			'price'      => (float) $course->get_price(),
			'duration'   => (string) $course->get_duration(),
			'permalink'  => (string) $course->get_permalink(),
			'instructor' => array(
				'user_id'      => $author instanceof UserModel ? $author->get_id() : 0,
				'display_name' => $author instanceof UserModel ? $author->get_display_name() : '',
			),
			'categories' => $categories,
		);
	}

	/**
	 * Map lesson model + curriculum ref into summary response.
	 *
	 * @param LessonPostModel $lesson Lesson model.
	 * @param array           $ref    Curriculum reference.
	 *
	 * @return array
	 */
	protected static function lesson_summary( LessonPostModel $lesson, array $ref ): array {
		return array(
			'lesson_id'    => $lesson->get_id(),
			'course_id'    => (int) $ref['course_id'],
			'section_id'   => (int) $ref['section_id'],
			'section_name' => (string) $ref['section_name'],
			'title'        => (string) $lesson->get_the_title(),
			'excerpt'      => (string) $lesson->get_the_excerpt(),
			'duration'     => (string) $lesson->get_duration(),
			'preview'      => (bool) $ref['preview'],
			'status'       => (string) $lesson->post_status,
			'permalink'    => (string) $lesson->get_permalink(),
		);
	}

	/**
	 * Map quiz model + curriculum ref into summary response.
	 *
	 * @param QuizPostModel $quiz Quiz model.
	 * @param array         $ref  Curriculum reference.
	 *
	 * @return array
	 */
	protected static function quiz_summary( QuizPostModel $quiz, array $ref ): array {
		return array(
			'quiz_id'         => $quiz->get_id(),
			'course_id'       => (int) $ref['course_id'],
			'section_id'      => (int) $ref['section_id'],
			'section_name'    => (string) $ref['section_name'],
			'title'           => (string) $quiz->get_the_title(),
			'duration'        => (string) $quiz->get_duration(),
			'passing_grade'   => (float) $quiz->get_passing_grade(),
			'questions_count' => (int) $quiz->count_questions(),
			'status'          => (string) $quiz->post_status,
			'permalink'       => (string) $quiz->get_permalink(),
		);
	}

	/**
	 * Collect course items by type, optionally filtered by section.
	 *
	 * @param CourseModel $course     Course model.
	 * @param string      $item_type  LearnPress item type.
	 * @param int         $section_id Optional section filter.
	 *
	 * @return array
	 */
	protected static function collect_items( CourseModel $course, string $item_type, int $section_id = 0 ): array {
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
