<?php

namespace LearnPress\MCP\Mappers;

use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\CourseSectionModel;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\Question\QuestionAnswerModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserModel;

defined( 'ABSPATH' ) || exit;

/**
 * Response payload composition for Phase 2 MCP tools.
 *
 * Keeps executor logic free of inline array assembly and guarantees a
 * consistent shape across create/update/read responses.
 */
class ResponseMapper {

	/**
	 * Minimal course write result.
	 *
	 * @param CourseModel $course Course model.
	 *
	 * @return array
	 */
	public static function course_write_result( CourseModel $course ): array {
		return array(
			'course_id' => $course->get_id(),
			'title'     => (string) $course->get_title(),
			'status'    => (string) $course->get_status(),
			'permalink' => (string) $course->get_permalink(),
			'edit_url'  => (string) $course->get_post_model()->get_edit_link(),
		);
	}

	/**
	 * Full course object.
	 *
	 * @param CourseModel $course Course model.
	 *
	 * @return array
	 */
	public static function course_object( CourseModel $course ): array {
		$categories = array();
		foreach ( $course->get_categories() as $term ) {
			$categories[] = array(
				'term_id' => (int) $term->term_id,
				'name'    => (string) $term->name,
				'slug'    => (string) $term->slug,
			);
		}

		$tags = array();
		foreach ( $course->get_tags() as $term ) {
			$tags[] = array(
				'term_id' => (int) $term->term_id,
				'name'    => (string) $term->name,
				'slug'    => (string) $term->slug,
			);
		}

		$author = $course->get_author_model();

		return array(
			'course_id'        => $course->get_id(),
			'title'            => (string) $course->get_title(),
			'status'           => (string) $course->get_status(),
			'excerpt'          => (string) ( $course->post_excerpt ?? '' ),
			'description'      => (string) $course->get_description(),
			'price'            => (float) $course->get_price(),
			'regular_price'    => (float) $course->get_regular_price(),
			'sale_price'       => (float) $course->get_sale_price(),
			'duration'         => (string) $course->get_duration(),
			'level'            => (string) $course->get_meta_value_by_key( CoursePostModel::META_KEY_LEVEL, '' ),
			'requirements'     => $course->get_meta_value_by_key( CoursePostModel::META_KEY_REQUIREMENTS, '' ),
			'target_audiences' => $course->get_meta_value_by_key( CoursePostModel::META_KEY_TARGET, '' ),
			'features'         => $course->get_meta_value_by_key( CoursePostModel::META_KEY_FEATURES, '' ),
			'faqs'             => $course->get_meta_value_by_key( CoursePostModel::META_KEY_FAQS, '' ),
			'permalink'        => (string) $course->get_permalink(),
			'edit_url'         => (string) $course->get_post_model()->get_edit_link(),
			'instructor'       => array(
				'user_id'      => $author instanceof UserModel ? $author->get_id() : 0,
				'display_name' => $author instanceof UserModel ? $author->get_display_name() : '',
			),
			'categories'       => $categories,
			'tags'             => $tags,
		);
	}

	/**
	 * Section object.
	 *
	 * @param CourseSectionModel $section Section model.
	 *
	 * @return array
	 */
	public static function section( CourseSectionModel $section ): array {
		return array(
			'section_id'  => (int) $section->get_section_id(),
			'course_id'   => (int) $section->section_course_id,
			'name'        => (string) $section->section_name,
			'description' => (string) $section->section_description,
			'order'       => (int) $section->section_order,
		);
	}

	/**
	 * Lesson object.
	 *
	 * @param LessonPostModel $lesson    Lesson model.
	 * @param array           $location  Optional curriculum location data.
	 *
	 * @return array
	 */
	public static function lesson( LessonPostModel $lesson, array $location = array() ): array {
		return array(
			'lesson_id'   => $lesson->get_id(),
			'course_id'   => absint( $location['course_id'] ?? 0 ),
			'section_id'  => absint( $location['section_id'] ?? 0 ),
			'title'       => (string) $lesson->get_the_title(),
			'content'     => (string) $lesson->get_the_content(),
			'excerpt'     => (string) $lesson->get_the_excerpt(),
			'status'      => (string) $lesson->post_status,
			'duration'    => (string) $lesson->get_duration(),
			'preview'     => (bool) $lesson->has_preview(),
			'video_intro' => (string) $lesson->get_meta_value_by_key( '_lp_lesson_video_intro', '' ),
			'permalink'   => (string) $lesson->get_permalink(),
		);
	}

	/**
	 * Lesson minimal write result.
	 *
	 * @param LessonPostModel $lesson   Lesson model.
	 * @param array           $location Curriculum location data.
	 *
	 * @return array
	 */
	public static function lesson_write_result( LessonPostModel $lesson, array $location = array() ): array {
		return array(
			'lesson_id'  => $lesson->get_id(),
			'course_id'  => absint( $location['course_id'] ?? 0 ),
			'section_id' => absint( $location['section_id'] ?? 0 ),
			'title'      => (string) $lesson->get_the_title(),
			'status'     => (string) $lesson->post_status,
			'permalink'  => (string) $lesson->get_permalink(),
		);
	}

	/**
	 * Quiz object.
	 *
	 * @param QuizPostModel $quiz     Quiz model.
	 * @param array         $location Optional curriculum location data.
	 *
	 * @return array
	 */
	public static function quiz( QuizPostModel $quiz, array $location = array() ): array {
		return array(
			'quiz_id'             => $quiz->get_id(),
			'course_id'           => absint( $location['course_id'] ?? 0 ),
			'section_id'          => absint( $location['section_id'] ?? 0 ),
			'title'               => (string) $quiz->get_the_title(),
			'content'             => (string) $quiz->get_the_content(),
			'status'              => (string) $quiz->post_status,
			'duration'            => (string) $quiz->get_duration(),
			'passing_grade'       => (float) $quiz->get_passing_grade(),
			'retake_count'        => (int) $quiz->get_retake_count(),
			'instant_check'       => (bool) $quiz->has_instant_check(),
			'negative_marking'    => (bool) $quiz->has_negative_marking(),
			'show_correct_review' => (bool) $quiz->has_show_correct_review(),
			'questions_count'     => (int) $quiz->count_questions(),
			'permalink'           => (string) $quiz->get_permalink(),
		);
	}

	/**
	 * Quiz minimal write result.
	 *
	 * @param QuizPostModel $quiz     Quiz model.
	 * @param array         $location Curriculum location data.
	 *
	 * @return array
	 */
	public static function quiz_write_result( QuizPostModel $quiz, array $location = array() ): array {
		return array(
			'quiz_id'    => $quiz->get_id(),
			'course_id'  => absint( $location['course_id'] ?? 0 ),
			'section_id' => absint( $location['section_id'] ?? 0 ),
			'title'      => (string) $quiz->get_the_title(),
			'status'     => (string) $quiz->post_status,
			'permalink'  => (string) $quiz->get_permalink(),
		);
	}

	/**
	 * Question object, with sensitive fields gated by privilege.
	 *
	 * @param QuestionPostModel $question   Question model.
	 * @param bool              $privileged Whether to expose sensitive data.
	 * @param int               $order      Question order in the quiz.
	 *
	 * @return array
	 */
	public static function question( QuestionPostModel $question, bool $privileged, int $order = 0 ): array {
		$answers = array();
		foreach ( $question->get_answer_option() as $answer ) {
			if ( $answer instanceof QuestionAnswerModel ) {
				$answers[] = self::answer( $answer, $privileged );
			}
		}

		$data = array(
			'question_id' => $question->get_id(),
			'title'       => (string) $question->get_the_title(),
			'content'     => (string) $question->get_the_content(),
			'type'        => (string) $question->get_type(),
			'mark'        => (float) $question->get_mark(),
			'order'       => $order,
			'status'      => (string) $question->post_status,
			'answers'     => $answers,
		);

		if ( $privileged ) {
			$data['hint']        = (string) $question->get_hint();
			$data['explanation'] = (string) $question->get_explanation();
		}

		return $data;
	}

	/**
	 * Answer object, with correctness gated by privilege.
	 *
	 * @param QuestionAnswerModel $answer     Answer model.
	 * @param bool                $privileged Whether to expose correctness.
	 *
	 * @return array
	 */
	public static function answer( QuestionAnswerModel $answer, bool $privileged ): array {
		$data = array(
			'answer_id' => (int) $answer->question_answer_id,
			'title'     => (string) $answer->title,
			'value'     => (string) $answer->value,
			'order'     => (int) $answer->order,
		);

		if ( $privileged ) {
			$data['is_correct'] = ( 'yes' === $answer->is_true );
		}

		return $data;
	}

	/**
	 * Enrollment object.
	 *
	 * @param UserItemModel $enrollment Enrollment model.
	 *
	 * @return array
	 */
	public static function enrollment( UserItemModel $enrollment ): array {
		return array(
			'enrollment_id' => (int) $enrollment->get_user_item_id(),
			'user_id'       => (int) $enrollment->user_id,
			'course_id'     => (int) $enrollment->item_id,
			'status'        => (string) $enrollment->get_status(),
			'graduation'    => (string) $enrollment->get_graduation(),
			'start_time'    => (string) $enrollment->get_start_time(),
			'end_time'      => (string) $enrollment->get_end_time(),
		);
	}

	/**
	 * Course list summary (read tools).
	 *
	 * @param CourseModel $course Course model.
	 *
	 * @return array
	 */
	public static function course_summary( CourseModel $course ): array {
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
	 * Lesson list summary (read tools).
	 *
	 * @param LessonPostModel $lesson Lesson model.
	 * @param array           $ref    Curriculum reference (from Curriculum::collect_items).
	 *
	 * @return array
	 */
	public static function lesson_summary( LessonPostModel $lesson, array $ref ): array {
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
	 * Quiz list summary (read tools).
	 *
	 * @param QuizPostModel $quiz Quiz model.
	 * @param array         $ref  Curriculum reference (from Curriculum::collect_items).
	 *
	 * @return array
	 */
	public static function quiz_summary( QuizPostModel $quiz, array $ref ): array {
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
}
