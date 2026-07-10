<?php

namespace LearnPress\MCP\Support;

use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\CourseSectionModel;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\Quiz\QuizQuestionModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserModel;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Shared validation + entity-resolution helpers for Phase 2 MCP tools.
 *
 * Validates IDs, allowlists, and target existence consistently so domain
 * executors do not reimplement the same checks.
 */
class Validator {

	/**
	 * Read a required positive integer ID from input.
	 *
	 * @param array  $args Input args.
	 * @param string $key  Field name.
	 *
	 * @return int|WP_Error
	 */
	public static function require_id( array $args, string $key ) {
		$id = absint( $args[ $key ] ?? 0 );
		if ( $id <= 0 ) {
			/* translators: %s: field name. */
			return Errors::invalid( sprintf( __( '%s is required and must be a positive integer.', 'learnpress' ), $key ) );
		}

		return $id;
	}

	/**
	 * Validate an optional post status against the Phase 2 allowlist.
	 *
	 * @param mixed $value Raw status value.
	 *
	 * @return string|WP_Error Empty string when not provided.
	 */
	public static function status( $value ) {
		if ( null === $value || '' === $value ) {
			return '';
		}

		$status = sanitize_key( (string) $value );
		if ( ! in_array( $status, Schemas::allowed_statuses(), true ) ) {
			return Errors::invalid(
				sprintf(
					/* translators: 1: provided status, 2: allowed statuses. */
					__( 'Invalid status "%1$s". Allowed: %2$s.', 'learnpress' ),
					$status,
					implode( ', ', Schemas::allowed_statuses() )
				)
			);
		}

		return $status;
	}

	/**
	 * Allowed enrollment statuses.
	 *
	 * @return string[]
	 */
	public static function enrollment_statuses(): array {
		return array(
			UserItemModel::STATUS_ENROLLED,
			UserItemModel::STATUS_FINISHED,
			UserItemModel::STATUS_PURCHASED,
			UserItemModel::STATUS_COMPLETED,
			UserItemModel::STATUS_CANCEL,
		);
	}

	/**
	 * Statuses allowed when creating a manual enrollment (active states only).
	 *
	 * Finished/completed/cancel are reached via update-enrollment, not creation,
	 * to avoid inconsistent records (e.g. finished with no end_time/result).
	 *
	 * @return string[]
	 */
	public static function enroll_create_statuses(): array {
		return array(
			UserItemModel::STATUS_ENROLLED,
			UserItemModel::STATUS_PURCHASED,
		);
	}

	/**
	 * Allowed graduation values.
	 *
	 * @return string[]
	 */
	public static function graduations(): array {
		return array(
			UserItemModel::GRADUATION_IN_PROGRESS,
			UserItemModel::GRADUATION_PASSED,
			UserItemModel::GRADUATION_FAILED,
		);
	}

	/**
	 * Validate an optional enrollment status.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string|WP_Error Empty string when not provided.
	 */
	public static function enrollment_status( $value ) {
		if ( null === $value || '' === $value ) {
			return '';
		}

		$status = sanitize_key( (string) $value );
		if ( ! in_array( $status, self::enrollment_statuses(), true ) ) {
			return Errors::invalid(
				sprintf(
					/* translators: 1: status, 2: allowed list. */
					__( 'Invalid enrollment status "%1$s". Allowed: %2$s.', 'learnpress' ),
					$status,
					implode( ', ', self::enrollment_statuses() )
				)
			);
		}

		return $status;
	}

	/**
	 * Validate an optional graduation value.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string|WP_Error Empty string when not provided.
	 */
	public static function graduation( $value ) {
		if ( null === $value || '' === $value ) {
			return '';
		}

		$graduation = sanitize_key( (string) $value );
		if ( ! in_array( $graduation, self::graduations(), true ) ) {
			return Errors::invalid(
				sprintf(
					/* translators: 1: graduation, 2: allowed list. */
					__( 'Invalid graduation "%1$s". Allowed: %2$s.', 'learnpress' ),
					$graduation,
					implode( ', ', self::graduations() )
				)
			);
		}

		return $graduation;
	}

	/**
	 * Validate a question type against LearnPress registered types.
	 *
	 * @param mixed $value Raw type.
	 *
	 * @return string|WP_Error
	 */
	public static function question_type( $value ) {
		$type = sanitize_key( (string) $value );
		if ( '' === $type || ! QuestionPostModel::check_type_valid( $type ) ) {
			return Errors::invalid(
				sprintf(
					/* translators: %s: provided type. */
					__( 'Invalid question type "%s".', 'learnpress' ),
					$type
				)
			);
		}

		return $type;
	}

	/**
	 * Resolve a course or return a 404.
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return CourseModel|WP_Error
	 */
	public static function find_course( int $course_id ) {
		$course = CourseModel::find( $course_id, true );
		if ( ! $course instanceof CourseModel ) {
			return Errors::not_found( __( 'Course not found.', 'learnpress' ) );
		}

		return $course;
	}

	/**
	 * Resolve a course post model or return a 404.
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return CoursePostModel|WP_Error
	 */
	public static function find_course_post( int $course_id ) {
		$course = CoursePostModel::find( $course_id, true );
		if ( ! $course instanceof CoursePostModel ) {
			return Errors::not_found( __( 'Course not found.', 'learnpress' ) );
		}

		return $course;
	}

	/**
	 * Resolve a section that belongs to a course, or return a 404.
	 *
	 * @param int $section_id Section ID.
	 * @param int $course_id  Course ID.
	 *
	 * @return CourseSectionModel|WP_Error
	 */
	public static function find_section( int $section_id, int $course_id ) {
		$section = CourseSectionModel::find( $section_id, $course_id, true );
		if ( ! $section instanceof CourseSectionModel ) {
			return Errors::not_found( __( 'Section not found in this course.', 'learnpress' ) );
		}

		return $section;
	}

	/**
	 * Resolve a lesson or return a 404.
	 *
	 * @param int $lesson_id Lesson ID.
	 *
	 * @return LessonPostModel|WP_Error
	 */
	public static function find_lesson( int $lesson_id ) {
		$lesson = LessonPostModel::find( $lesson_id, true );
		if ( ! $lesson instanceof LessonPostModel ) {
			return Errors::not_found( __( 'Lesson not found.', 'learnpress' ) );
		}

		return $lesson;
	}

	/**
	 * Resolve a quiz or return a 404.
	 *
	 * @param int $quiz_id Quiz ID.
	 *
	 * @return QuizPostModel|WP_Error
	 */
	public static function find_quiz( int $quiz_id ) {
		$quiz = QuizPostModel::find( $quiz_id, true );
		if ( ! $quiz instanceof QuizPostModel ) {
			return Errors::not_found( __( 'Quiz not found.', 'learnpress' ) );
		}

		return $quiz;
	}

	/**
	 * Resolve a question or return a 404.
	 *
	 * @param int $question_id Question ID.
	 *
	 * @return QuestionPostModel|WP_Error
	 */
	public static function find_question( int $question_id ) {
		$question = QuestionPostModel::find( $question_id, true );
		if ( ! $question instanceof QuestionPostModel ) {
			return Errors::not_found( __( 'Question not found.', 'learnpress' ) );
		}

		return $question;
	}

	/**
	 * Resolve a quiz-question relationship or return a 404.
	 *
	 * @param int $quiz_id     Quiz ID.
	 * @param int $question_id Question ID.
	 *
	 * @return QuizQuestionModel|WP_Error
	 */
	public static function find_quiz_question( int $quiz_id, int $question_id ) {
		$relation = QuizQuestionModel::find( $quiz_id, $question_id, true );
		if ( ! $relation instanceof QuizQuestionModel ) {
			return Errors::not_found( __( 'Question is not assigned to this quiz.', 'learnpress' ) );
		}

		return $relation;
	}

	/**
	 * Resolve a user or return a 404.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return UserModel|WP_Error
	 */
	public static function find_user( int $user_id ) {
		$user = UserModel::find( $user_id, true );
		if ( ! $user instanceof UserModel ) {
			return Errors::not_found( __( 'User not found.', 'learnpress' ) );
		}

		return $user;
	}
}
