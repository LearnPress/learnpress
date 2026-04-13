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
	 * Get the completed result of a specific quiz item for smart review.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 * @param int $quiz_id
	 *
	 * @return array{quiz: array}|array{error: string}
	 */
	public function get_quiz_review_result( int $user_id, int $course_id, int $quiz_id ): array {
		if ( $user_id <= 0 || $course_id <= 0 || $quiz_id <= 0 ) {
			return array(
				'error' => __( 'Quiz review is unavailable for this request.', 'learnpress' ),
			);
		}

		$course = CourseModel::find( $course_id, true );
		if ( ! $course ) {
			return array(
				'error' => __( 'Course not found.', 'learnpress' ),
			);
		}

		$quiz_items    = $this->get_course_quiz_items( $course );
		$quiz_item_map = array_column( $quiz_items, null, 'id' );
		if ( ! isset( $quiz_item_map[ $quiz_id ] ) ) {
			return array(
				'error' => __( 'Smart Review is only available for quizzes in this course.', 'learnpress' ),
			);
		}

		$user_quiz = UserQuizModel::find_user_item(
			$user_id,
			$quiz_id,
			LP_QUIZ_CPT,
			$course_id,
			LP_COURSE_CPT,
			true
		);
		if ( ! $user_quiz instanceof UserQuizModel ) {
			return array(
				'error' => __( 'Please complete this quiz to use Smart Review.', 'learnpress' ),
			);
		}

		if ( $user_quiz->get_status() !== LP_ITEM_COMPLETED ) {
			return array(
				'error' => __( 'Please complete this quiz to use Smart Review.', 'learnpress' ),
			);
		}

		$result = $user_quiz->get_result();
		if ( ! is_array( $result ) || empty( $result ) ) {
			return array(
				'error' => __( 'Quiz result is unavailable for Smart Review.', 'learnpress' ),
			);
		}

		return array(
			'quiz' => array(
				'quiz_id'    => $quiz_id,
				'quiz_title' => (string) ( $quiz_item_map[ $quiz_id ]['title'] ?? get_the_title( $quiz_id ) ),
				'result'     => $result,
			),
		);
	}

	/**
	 * Collect quiz items from course outline.
	 *
	 * @param CourseModel $course
	 *
	 * @return array<int, array{id: int, title: string}>
	 */
	private function get_course_quiz_items( CourseModel $course ): array {
		$sections_items = $course->get_section_items();
		$quiz_items     = array();

		foreach ( $sections_items as $section ) {
			if ( empty( $section->items ) ) {
				continue;
			}

			foreach ( $section->items as $item ) {
				$item_id = (int) ( $item->id ?? $item->item_id ?? 0 );
				if ( $item_id <= 0 ) {
					continue;
				}

				$item_type = (string) ( $item->item_type ?? $item->type ?? '' );
				if ( '' === $item_type ) {
					$item_type = (string) get_post_type( $item_id );
				}
				if ( $item_type !== LP_QUIZ_CPT ) {
					continue;
				}

				$quiz_items[ $item_id ] = array(
					'id'    => $item_id,
					'title' => (string) ( $item->title ?? get_the_title( $item_id ) ),
				);
			}
		}

		return array_values( $quiz_items );
	}
}
