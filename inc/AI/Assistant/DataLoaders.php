<?php

namespace LearnPress\AI\Assistant;

use LearnPress\Models\CourseModel;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\UserItems\UserQuizModel;

/**
 * Data loaders for AI Assistant tool calls.
 *
 * Provides grounded data from the LearnPress Models layer (no raw SQL).
 * Each method corresponds to an OpenAI function-calling tool definition.
 *
 * @package LearnPress\AI\Assistant
 * @since 4.3.5
 */
class DataLoaders {

	/**
	 * Get lesson content for a given lesson ID.
	 *
	 * @param int $lesson_id
	 * @param int $user_id
	 *
	 * @return array{title: string, content: string}|array{error: string}
	 */
	public function get_lesson_content( int $lesson_id, int $user_id ): array {
		$lesson = LessonPostModel::find( $lesson_id, true );

		if ( ! $lesson ) {
			return array(
				'error' => __( 'Lesson not found.', 'learnpress' ),
			);
		}

		return array(
			'title'   => $lesson->get_the_title(),
			'content' => $lesson->get_the_content(),
		);
	}

	/**
	 * Get the full course outline (sections, lessons, quizzes).
	 *
	 * @param int $course_id
	 *
	 * @return array{title: string, sections: array}|array{error: string}
	 */
	public function get_course_outline( int $course_id ): array {
		$course = CourseModel::find( $course_id, true );

		if ( ! $course ) {
			return array(
				'error' => __( 'Course not found.', 'learnpress' ),
			);
		}

		$sections_items = $course->get_section_items();
		$outline        = array();

		foreach ( $sections_items as $section ) {
			$items = array();

			if ( ! empty( $section->items ) ) {
				foreach ( $section->items as $item ) {
					$post_type = get_post_type( $item->id ?? $item->item_id ?? 0 );

					$items[] = array(
						'id'    => $item->id ?? $item->item_id ?? 0,
						'title' => get_the_title( $item->id ?? $item->item_id ?? 0 ),
						'type'  => $post_type === LP_QUIZ_CPT ? 'quiz' : 'lesson',
					);
				}
			}

			$outline[] = array(
				'section_id'    => $section->section_id ?? $section->id ?? 0,
				'section_title' => $section->section_name ?? $section->title ?? '',
				'items'         => $items,
			);
		}

		return array(
			'title'    => $course->get_the_title(),
			'sections' => $outline,
		);
	}

	/**
	 * Get quiz attempt results for a user across all quizzes in a course.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 *
	 * @return array{quizzes: array}|array{error: string}
	 */
	public function get_quiz_results( int $user_id, int $course_id ): array {
		$course = CourseModel::find( $course_id, true );

		if ( ! $course ) {
			return array(
				'error' => __( 'Course not found.', 'learnpress' ),
			);
		}

		$results  = array();
		$quiz_ids = $this->get_course_quiz_item_ids( $course );

		foreach ( $quiz_ids as $item_id ) {
			$user_quiz = $this->find_user_quiz_item( $user_id, (int) $item_id, $course_id );

			if ( ! $user_quiz || ! method_exists( $user_quiz, 'get_attempts' ) ) {
				continue;
			}

			$attempts = $user_quiz->get_attempts( 5 );

			if ( empty( $attempts ) ) {
				continue;
			}

			$normalized_attempts = array();
			foreach ( $attempts as $attempt ) {
				$normalized_attempts[] = array(
					'result'     => $attempt['result'] ?? array(),
					'graduation' => $attempt['graduation'] ?? '',
					'start_time' => $attempt['start_time'] ?? '',
					'end_time'   => $attempt['end_time'] ?? '',
					'time_spent' => $attempt['time_spent'] ?? '',
				);
			}

			$results[] = array(
				'quiz_id'    => $item_id,
				'quiz_title' => get_the_title( $item_id ),
				'attempts'   => $normalized_attempts,
			);
		}

		if ( empty( $results ) ) {
			return array(
				'error' => __( 'No quiz attempts found for this user in this course.', 'learnpress' ),
			);
		}

		return array( 'quizzes' => $results );
	}

	/**
	 * Check whether a learner has at least one quiz attempt in a course.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 *
	 * @return bool
	 */
	public function has_quiz_attempt( int $user_id, int $course_id ): bool {
		if ( $user_id <= 0 || $course_id <= 0 ) {
			return false;
		}

		$course = CourseModel::find( $course_id, true );
		if ( ! $course ) {
			return false;
		}

		$quiz_ids = $this->get_course_quiz_item_ids( $course );
		if ( empty( $quiz_ids ) ) {
			return false;
		}

		foreach ( $quiz_ids as $item_id ) {
			$user_quiz = $this->find_user_quiz_item( $user_id, (int) $item_id, $course_id );
			if ( ! $user_quiz || ! method_exists( $user_quiz, 'get_attempts' ) ) {
				continue;
			}

			$attempts = $user_quiz->get_attempts( 1 );
			if ( ! empty( $attempts ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Collect quiz item IDs from a course outline.
	 *
	 * @param CourseModel $course
	 *
	 * @return array<int>
	 */
	private function get_course_quiz_item_ids( CourseModel $course ): array {
		$sections_items = $course->get_section_items();
		$quiz_ids       = array();

		foreach ( $sections_items as $section ) {
			if ( empty( $section->items ) ) {
				continue;
			}

			foreach ( $section->items as $item ) {
				$item_id = (int) ( $item->id ?? $item->item_id ?? 0 );
				if ( $item_id <= 0 ) {
					continue;
				}

				if ( get_post_type( $item_id ) !== LP_QUIZ_CPT ) {
					continue;
				}

				$quiz_ids[] = $item_id;
			}
		}

		return array_values( array_unique( $quiz_ids ) );
	}

	/**
	 * Resolve learner quiz model for a quiz item.
	 *
	 * @param int $user_id
	 * @param int $quiz_id
	 * @param int $course_id
	 *
	 * @return mixed
	 */
	private function find_user_quiz_item( int $user_id, int $quiz_id, int $course_id ) {
		$quiz_item_type   = defined( 'LP_QUIZ_CPT' ) ? LP_QUIZ_CPT : 'lp_quiz';
		$course_item_type = defined( 'LP_COURSE_CPT' ) ? LP_COURSE_CPT : 'lp_course';

		return UserQuizModel::find_user_item(
			$user_id,
			$quiz_id,
			$quiz_item_type,
			$course_id,
			$course_item_type,
			true
		);
	}
}
