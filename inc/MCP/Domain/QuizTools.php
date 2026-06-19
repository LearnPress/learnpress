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
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\QuizPostModel;
use Throwable;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Quiz create/update/delete executors.
 *
 * Creates quizzes through the LearnPress section curriculum API and persists
 * quiz settings. Delete is reversible (trash + relationship removal with
 * recovery metadata) and preserves quiz-question relationships/posts.
 */
class QuizTools {

	/**
	 * Execute `learnpress/create-quiz`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function create_quiz( $input ) {
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
					'item_type'    => LP_QUIZ_CPT,
					'item_title'   => $title,
					'item_content' => Sanitizer::html( $args['content'] ?? '' ),
				)
			);

			$quiz_id = absint( $section_item->item_id );
			$quiz    = QuizPostModel::find( $quiz_id, true );
			if ( ! $quiz instanceof QuizPostModel ) {
				return Errors::internal();
			}

			// Default to draft on create (create_item_and_add publishes by default).
			self::apply_fields( $quiz, $args, '' !== $status ? $status : 'draft' );

			if ( array_key_exists( 'order', $args ) ) {
				$course_post = CoursePostModel::find( $course_id, true );
				if ( $course_post instanceof CoursePostModel ) {
					Curriculum::place_item( $course_post, $course_id, $quiz_id, $section_id, $section_id, absint( $args['order'] ) );
				}
			}

			$quiz = QuizPostModel::find( $quiz_id, true );

			return array(
				'quiz' => ResponseMapper::quiz_write_result(
					$quiz,
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
	 * Execute `learnpress/update-quiz`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function update_quiz( $input ) {
		$args    = is_array( $input ) ? $input : array();
		$quiz_id = Validator::require_id( $args, 'quiz_id' );
		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}

		$quiz = Validator::find_quiz( $quiz_id );
		if ( is_wp_error( $quiz ) ) {
			return $quiz;
		}

		if ( ! Permissions::can_edit_post( $quiz_id ) ) {
			return Errors::forbidden();
		}

		$status = Validator::status( $args['status'] ?? '' );
		if ( is_wp_error( $status ) ) {
			return $status;
		}

		$location = Curriculum::resolve_location( $quiz_id, LP_QUIZ_CPT );

		try {
			if ( array_key_exists( 'title', $args ) ) {
				$quiz->post_title = Sanitizer::text( $args['title'] );
			}
			self::apply_fields( $quiz, $args, $status );

			$move = self::move_within_curriculum( $quiz_id, $location, $args );
			if ( is_wp_error( $move ) ) {
				return $move;
			}
			$location = $move;

			$quiz = QuizPostModel::find( $quiz_id, true );

			return array( 'quiz' => ResponseMapper::quiz( $quiz, $location ) );
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Execute `learnpress/delete-quiz` (reversible trash).
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function delete_quiz( $input ) {
		$args    = is_array( $input ) ? $input : array();
		$quiz_id = Validator::require_id( $args, 'quiz_id' );
		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}

		$quiz = Validator::find_quiz( $quiz_id );
		if ( is_wp_error( $quiz ) ) {
			return $quiz;
		}

		if ( ! Permissions::can_delete_post( $quiz_id ) ) {
			return Errors::forbidden();
		}

		$hint_course = absint( $args['course_id'] ?? 0 );
		$location    = Curriculum::resolve_location( $quiz_id, LP_QUIZ_CPT, $hint_course );

		if ( $hint_course > 0 && $location['course_id'] !== $hint_course ) {
			return Errors::not_found( __( 'Quiz does not belong to the provided course.', 'learnpress' ) );
		}
		if ( ! empty( $args['section_id'] ) && absint( $args['section_id'] ) !== $location['section_id'] ) {
			return Errors::not_found( __( 'Quiz does not belong to the provided section.', 'learnpress' ) );
		}

		try {
			$removed_from_curriculum = false;
			if ( $location['section_id'] > 0 ) {
				$removed_from_curriculum = Curriculum::remove_item_from_section( $location['section_id'], $quiz_id, $location['course_id'] );
			}

			$trashed = wp_trash_post( $quiz_id );
			if ( ! $trashed ) {
				return Errors::internal();
			}

			return array(
				'trashed'                 => true,
				'quiz_id'                 => $quiz_id,
				'removed_from_curriculum' => $removed_from_curriculum,
				'recovery'                => array(
					'course_id'      => $location['course_id'],
					'section_id'     => $location['section_id'],
					'previous_order' => $location['item_order'],
					'item_type'      => LP_QUIZ_CPT,
					'note'           => __( 'Quiz moved to trash. Question relationships and posts are preserved. Restore from WP admin and re-add to the section to recover.', 'learnpress' ),
				),
			);
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Execute `learnpress/list-quizzes`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function list_quizzes( $input ) {
		$args = Sanitizer::input_array( $input, 'learnpress/list-quizzes' );
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

		$page     = Pagination::page( $args['page'] ?? 1 );
		$per_page = Pagination::per_page( $args['per_page'] ?? 10 );
		$refs     = Curriculum::collect_items( $course, LP_QUIZ_CPT );

		$all = array();
		foreach ( $refs as $ref ) {
			$quiz = QuizPostModel::find( (int) $ref['item_id'], true );
			if ( $quiz instanceof QuizPostModel ) {
				$all[] = ResponseMapper::quiz_summary( $quiz, $ref );
			}
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
	 * Execute `learnpress/get-quiz-details`.
	 *
	 * Adds the `quiz.questions` payload while protecting privileged answer data
	 * (correct answers, hints, explanations). Phase 1 overview fields stay
	 * backward compatible.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function get_quiz_details( $input ) {
		$args    = is_array( $input ) ? $input : array();
		$quiz_id = Validator::require_id( $args, 'quiz_id' );
		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}

		$quiz = Validator::find_quiz( $quiz_id );
		if ( is_wp_error( $quiz ) ) {
			return $quiz;
		}

		try {
			$privileged = Permissions::can_read_sensitive_quiz( $quiz_id );
			$questions  = self::map_questions( $quiz, $privileged );

			return array(
				'quiz' => array(
					'quiz_id'              => $quiz->get_id(),
					'title'                => (string) $quiz->get_the_title(),
					'excerpt'              => (string) $quiz->get_the_excerpt(),
					'status'               => (string) $quiz->post_status,
					'permalink'            => (string) $quiz->get_permalink(),
					'duration'             => (string) $quiz->get_duration(),
					'passing_grade'        => (float) $quiz->get_passing_grade(),
					'retake_count'         => (int) $quiz->get_retake_count(),
					'questions_count'      => count( $questions ),
					'mark'                 => (float) $quiz->get_mark(),
					'instant_check'        => (bool) $quiz->has_instant_check(),
					'negative_marking'     => (bool) $quiz->has_negative_marking(),
					'minus_skip_questions' => (bool) $quiz->has_minus_skip_questions(),
					'show_correct_review'  => (bool) $quiz->has_show_correct_review(),
					'questions'            => $questions,
				),
			);
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Map quiz questions in quiz order (privileged gates sensitive fields).
	 *
	 * @param QuizPostModel $quiz       Quiz model.
	 * @param bool          $privileged Whether sensitive data may be exposed.
	 *
	 * @return array
	 */
	protected static function map_questions( QuizPostModel $quiz, bool $privileged ): array {
		$questions = array();
		$order     = 0;
		// Privileged editors see drafts/pending questions; students see published only.
		$statuses = $privileged ? array( 'publish', 'pending', 'draft' ) : array( 'publish' );

		foreach ( $quiz->get_question_ids( $statuses ) as $question_id ) {
			$question = QuestionPostModel::find( (int) $question_id, true );
			if ( ! $question instanceof QuestionPostModel ) {
				continue;
			}
			++$order;
			$questions[] = ResponseMapper::question( $question, $privileged, $order );
		}

		return $questions;
	}

	/**
	 * Apply quiz post fields and settings metadata.
	 *
	 * @param QuizPostModel $quiz   Quiz model.
	 * @param array         $args   Input args.
	 * @param string        $status Validated status ('' when not provided).
	 *
	 * @return void
	 */
	protected static function apply_fields( QuizPostModel $quiz, array $args, string $status ): void {
		if ( array_key_exists( 'content', $args ) ) {
			$quiz->post_content = Sanitizer::html( $args['content'] );
		}
		if ( '' !== $status ) {
			$quiz->post_status = $status;
		}

		$quiz->save();

		if ( array_key_exists( 'duration', $args ) ) {
			$quiz->save_meta_value_by_key( QuizPostModel::META_KEY_DURATION, Sanitizer::text( $args['duration'] ) );
		}
		if ( array_key_exists( 'passing_grade', $args ) ) {
			$quiz->save_meta_value_by_key( QuizPostModel::META_KEY_PASSING_GRADE, (float) $args['passing_grade'] );
		}
		if ( array_key_exists( 'retake_count', $args ) ) {
			$quiz->save_meta_value_by_key( QuizPostModel::META_KEY_RETAKE_COUNT, absint( $args['retake_count'] ) );
		}
		if ( array_key_exists( 'instant_check', $args ) ) {
			$quiz->save_meta_value_by_key( QuizPostModel::META_KEY_INSTANT_CHECK, Sanitizer::boolean( $args['instant_check'] ) ? 'yes' : 'no' );
		}
		if ( array_key_exists( 'negative_marking', $args ) ) {
			$quiz->save_meta_value_by_key( QuizPostModel::META_KEY_NEGATIVE_MARKING, Sanitizer::boolean( $args['negative_marking'] ) ? 'yes' : 'no' );
		}
		if ( array_key_exists( 'show_correct_review', $args ) ) {
			$quiz->save_meta_value_by_key( QuizPostModel::META_KEY_SHOW_CORRECT_REVIEW, Sanitizer::boolean( $args['show_correct_review'] ) ? 'yes' : 'no' );
		}
	}

	/**
	 * Move/reorder the quiz within the curriculum when requested.
	 *
	 * @param int   $quiz_id  Quiz ID.
	 * @param array $location Current location.
	 * @param array $args     Input args.
	 *
	 * @return array|WP_Error Updated location.
	 */
	protected static function move_within_curriculum( int $quiz_id, array $location, array $args ) {
		$has_section = array_key_exists( 'section_id', $args );
		$has_order   = array_key_exists( 'order', $args );
		if ( ! $has_section && ! $has_order ) {
			return $location;
		}

		$course_id = $location['course_id'];
		if ( $course_id <= 0 ) {
			return Errors::invalid( __( 'Quiz is not assigned to a course; cannot move or reorder.', 'learnpress' ) );
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
			Curriculum::place_item( $course_post, $course_id, $quiz_id, $new_section_id, $old_section_id, $position );
		}

		return Curriculum::resolve_location( $quiz_id, LP_QUIZ_CPT, $course_id );
	}
}
