<?php

namespace LearnPress\MCP\Concerns;

use LearnPress\Databases\UserItemsDB;
use LearnPress\Filters\Course\CourseJsonFilter;
use LearnPress\Filters\UserItemsFilter;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\Courses;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LP_Helper;
use LP_Material_Files_DB;
use Throwable;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Ability execute callbacks for LearnPress MCP read-only abilities.
 */
trait AbilityExecutors {
	/**
	 * Execute `learnpress/get-courses`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function execute_get_courses( $input ) {
		$args = self::input_arr( $input, 'learnpress/get-courses' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$page                = self::page( $args['page'] ?? 1 );
		$per_page            = self::per_page( $args['per_page'] ?? 10 );
		$filter              = new CourseJsonFilter();
		$filter->page        = $page;
		$filter->limit       = $per_page;
		$filter->post_status = self::status_list( $args['status'] ?? array( 'publish' ) );
		if ( empty( $filter->post_status ) ) {
			$filter->post_status = array( 'publish' );
		}

		if ( ! empty( $args['category'] ) ) {
			$filter->term_ids = array( absint( $args['category'] ) );
		}
		if ( ! empty( $args['instructor'] ) ) {
			$filter->post_author = absint( $args['instructor'] );
		}
		if ( isset( $args['search'] ) ) {
			$filter->post_title = LP_Helper::sanitize_params_submitted( (string) $args['search'] );
		}

		$price_min = isset( $args['price_min'] ) && is_numeric( $args['price_min'] ) ? (float) $args['price_min'] : null;
		$price_max = isset( $args['price_max'] ) && is_numeric( $args['price_max'] ) ? (float) $args['price_max'] : null;
		if ( null !== $price_min && $price_min < 0 ) {
			return self::invalid( __( 'price_min must be greater than or equal to 0.', 'learnpress' ) );
		}
		if ( null !== $price_max && $price_max < 0 ) {
			return self::invalid( __( 'price_max must be greater than or equal to 0.', 'learnpress' ) );
		}
		if ( null !== $price_min && null !== $price_max && $price_min > $price_max ) {
			return self::invalid( __( 'price_min must not be greater than price_max.', 'learnpress' ) );
		}

		global $wpdb;
		if ( null !== $price_min && $wpdb ) {
			$filter->where[] = $wpdb->prepare( 'AND c.price_to_sort >= %f', $price_min );
		}
		if ( null !== $price_max && $wpdb ) {
			$filter->where[] = $wpdb->prepare( 'AND c.price_to_sort <= %f', $price_max );
		}

		try {
			$total_rows = 0;
			$rows       = Courses::get_list_courses( $filter, $total_rows );
			$rows       = is_array( $rows ) ? $rows : array();
			$items      = array();
			foreach ( $rows as $row ) {
				$course = CourseModel::find( absint( $row->ID ?? 0 ), true );
				if ( $course instanceof CourseModel ) {
					$items[] = self::course_summary( $course );
				}
			}

			return array(
				'items'      => $items,
				'pagination' => array(
					'page'        => $page,
					'per_page'    => $per_page,
					'total_items' => (int) $total_rows,
					'total_pages' => self::total_pages( (int) $total_rows, $per_page ),
				),
			);
		} catch ( Throwable $e ) {
			return self::internal();
		}
	}

	/**
	 * Execute `learnpress/get-course-details`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function execute_get_course_details( $input ) {
		$args = self::input_arr( $input, 'learnpress/get-course-details' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$course_id = absint( $args['course_id'] ?? 0 );
		if ( $course_id <= 0 ) {
			return self::invalid( __( 'course_id is required and must be a positive integer.', 'learnpress' ) );
		}

		$course = CourseModel::find( $course_id, true );
		if ( ! $course instanceof CourseModel ) {
			return self::not_found( __( 'Course not found.', 'learnpress' ) );
		}

		$sections = array();
		foreach ( $course->get_section_items() as $section ) {
			$items = array();
			foreach ( $section->items as $item ) {
				$items[] = array(
					'item_id'   => absint( $item->item_id ?? $item->id ?? 0 ),
					'item_type' => (string) ( $item->item_type ?? $item->type ?? '' ),
					'title'     => (string) ( $item->title ?? '' ),
					'preview'   => ! empty( $item->preview ),
				);
			}
			$sections[] = array(
				'section_id'          => absint( $section->section_id ?? $section->id ?? 0 ),
				'section_name'        => (string) ( $section->section_name ?? $section->title ?? '' ),
				'section_description' => (string) ( $section->section_description ?? $section->description ?? '' ),
				'items'               => $items,
			);
		}

		$detail                 = self::course_summary( $course );
		$detail['description']  = (string) $course->get_description();
		$detail['requirements'] = (string) $course->get_meta_value_by_key( CoursePostModel::META_KEY_REQUIREMENTS, '' );
		$detail['curriculum']   = array(
			'sections'    => $sections,
			'items_count' => (int) $course->count_items(),
		);

		return array( 'course' => $detail );
	}

	/**
	 * Execute `learnpress/list-lessons`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function execute_list_lessons( $input ) {
		$args = self::input_arr( $input, 'learnpress/list-lessons' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$course_id = absint( $args['course_id'] ?? 0 );
		if ( $course_id <= 0 ) {
			return self::invalid( __( 'course_id is required and must be a positive integer.', 'learnpress' ) );
		}

		$course = CourseModel::find( $course_id, true );
		if ( ! $course instanceof CourseModel ) {
			return self::not_found( __( 'Course not found.', 'learnpress' ) );
		}

		$section_id = absint( $args['section_id'] ?? 0 );
		$page       = self::page( $args['page'] ?? 1 );
		$per_page   = self::per_page( $args['per_page'] ?? 10 );
		$statuses   = self::status_list( $args['status'] ?? null );
		$refs       = self::collect_items( $course, LP_LESSON_CPT, $section_id );

		$all = array();
		foreach ( $refs as $ref ) {
			$lesson = LessonPostModel::find( (int) $ref['item_id'], true );
			if ( ! $lesson instanceof LessonPostModel ) {
				continue;
			}
			if ( ! empty( $statuses ) && ! in_array( $lesson->post_status, $statuses, true ) ) {
				continue;
			}
			$all[] = self::lesson_summary( $lesson, $ref );
		}

		$total = count( $all );
		$items = array_slice( $all, ( $page - 1 ) * $per_page, $per_page );

		return array(
			'items'      => array_values( $items ),
			'pagination' => array(
				'page'        => $page,
				'per_page'    => $per_page,
				'total_items' => $total,
				'total_pages' => self::total_pages( $total, $per_page ),
			),
		);
	}

	/**
	 * Execute `learnpress/get-lesson-details`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function execute_get_lesson_details( $input ) {
		$args = self::input_arr( $input, 'learnpress/get-lesson-details' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$lesson_id = absint( $args['lesson_id'] ?? 0 );
		if ( $lesson_id <= 0 ) {
			return self::invalid( __( 'lesson_id is required and must be a positive integer.', 'learnpress' ) );
		}

		$lesson = LessonPostModel::find( $lesson_id, true );
		if ( ! $lesson instanceof LessonPostModel ) {
			return self::not_found( __( 'Lesson not found.', 'learnpress' ) );
		}

		$materials = array();
		if ( class_exists( 'LP_Material_Files_DB' ) ) {
			$materials_rs = LP_Material_Files_DB::getInstance()->get_material_by_item_id( $lesson_id, 0, 0, false );
			$materials_rs = is_array( $materials_rs ) ? $materials_rs : array();
			foreach ( $materials_rs as $material ) {
				$materials[] = array(
					'file_id'   => absint( $material->file_id ?? 0 ),
					'name'      => (string) ( $material->file_name ?? '' ),
					'type'      => (string) ( $material->file_type ?? '' ),
					'method'    => (string) ( $material->method ?? '' ),
					'file_path' => (string) ( $material->file_path ?? '' ),
					'url'       => (string) ( $material->file_url ?? '' ),
				);
			}
		}

		return array(
			'lesson' => array(
				'lesson_id'    => $lesson->get_id(),
				'title'        => (string) $lesson->get_the_title(),
				'content'      => (string) $lesson->get_the_content(),
				'excerpt'      => (string) $lesson->get_the_excerpt(),
				'duration'     => (string) $lesson->get_duration(),
				'video_intro'  => (string) $lesson->get_meta_value_by_key( '_lp_lesson_video_intro', '' ),
				'preview'      => (bool) $lesson->has_preview(),
				'status'       => (string) $lesson->post_status,
				'permalink'    => (string) $lesson->get_permalink(),
				'materials'    => $materials,
				'materials_no' => count( $materials ),
			),
		);
	}

	/**
	 * Execute `learnpress/list-quizzes`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function execute_list_quizzes( $input ) {
		$args = self::input_arr( $input, 'learnpress/list-quizzes' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$course_id = absint( $args['course_id'] ?? 0 );
		if ( $course_id <= 0 ) {
			return self::invalid( __( 'course_id is required and must be a positive integer.', 'learnpress' ) );
		}

		$course = CourseModel::find( $course_id, true );
		if ( ! $course instanceof CourseModel ) {
			return self::not_found( __( 'Course not found.', 'learnpress' ) );
		}

		$page     = self::page( $args['page'] ?? 1 );
		$per_page = self::per_page( $args['per_page'] ?? 10 );
		$refs     = self::collect_items( $course, LP_QUIZ_CPT );

		$all = array();
		foreach ( $refs as $ref ) {
			$quiz = QuizPostModel::find( (int) $ref['item_id'], true );
			if ( $quiz instanceof QuizPostModel ) {
				$all[] = self::quiz_summary( $quiz, $ref );
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
				'total_pages' => self::total_pages( $total, $per_page ),
			),
		);
	}

	/**
	 * Execute `learnpress/get-quiz-details`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function execute_get_quiz_details( $input ) {
		$args = self::input_arr( $input, 'learnpress/get-quiz-details' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$quiz_id = absint( $args['quiz_id'] ?? 0 );
		if ( $quiz_id <= 0 ) {
			return self::invalid( __( 'quiz_id is required and must be a positive integer.', 'learnpress' ) );
		}

		$quiz = QuizPostModel::find( $quiz_id, true );
		if ( ! $quiz instanceof QuizPostModel ) {
			return self::not_found( __( 'Quiz not found.', 'learnpress' ) );
		}

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
				'questions_count'      => (int) $quiz->count_questions(),
				'mark'                 => (float) $quiz->get_mark(),
				'instant_check'        => (bool) $quiz->has_instant_check(),
				'negative_marking'     => (bool) $quiz->has_negative_marking(),
				'minus_skip_questions' => (bool) $quiz->has_minus_skip_questions(),
				'show_correct_review'  => (bool) $quiz->has_show_correct_review(),
			),
		);
	}

	/**
	 * Execute `learnpress/get-student-progress`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function execute_get_student_progress( $input ) {
		$args = self::input_arr( $input, 'learnpress/get-student-progress' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$user_id   = absint( $args['user_id'] ?? 0 );
		$course_id = absint( $args['course_id'] ?? 0 );
		if ( $user_id <= 0 || $course_id <= 0 ) {
			return self::invalid( __( 'user_id and course_id are required positive integers.', 'learnpress' ) );
		}

		try {
			$user_course = UserCourseModel::find( $user_id, $course_id, true );
			if ( ! $user_course instanceof UserCourseModel ) {
				return self::not_found( __( 'Enrollment not found for this user and course.', 'learnpress' ) );
			}

			$course  = CourseModel::find( $course_id, true );
			$user    = UserModel::find( $user_id, true );
			$results = $user_course->calculate_course_results();
			$count   = (int) ( $results['count_items'] ?? 0 );
			$done    = (int) ( $results['completed_items'] ?? 0 );
			$percent = $count > 0 ? round( ( $done * 100 ) / $count, 2 ) : 0;

			return array(
				'progress' => array(
					'user'       => array(
						'user_id'      => $user_id,
						'display_name' => $user instanceof UserModel ? $user->get_display_name() : '',
						'email'        => $user instanceof UserModel ? $user->get_email() : '',
					),
					'course'     => array(
						'course_id' => $course_id,
						'title'     => $course instanceof CourseModel ? $course->get_title() : '',
					),
					'enrollment' => array(
						'status'     => (string) $user_course->status,
						'graduation' => (string) $user_course->graduation,
						'start_time' => (string) $user_course->start_time,
						'end_time'   => (string) $user_course->end_time,
					),
					'result'     => array(
						'count_items'      => $count,
						'completed_items'  => $done,
						'progress_percent' => $percent,
						'evaluate_type'    => (string) ( $results['evaluate_type'] ?? '' ),
						'pass'             => (int) ( $results['pass'] ?? 0 ),
						'result'           => (float) ( $results['result'] ?? 0 ),
						'items'            => (array) ( $results['items'] ?? array() ),
					),
				),
			);
		} catch ( Throwable $e ) {
			return self::internal();
		}
	}

	/**
	 * Execute `learnpress/get-enrollments`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function execute_get_enrollments( $input ) {
		$args = self::input_arr( $input, 'learnpress/get-enrollments' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$page              = self::page( $args['page'] ?? 1 );
		$per_page          = self::per_page( $args['per_page'] ?? 10 );
		$filter            = new UserItemsFilter();
		$filter->item_type = LP_COURSE_CPT;
		$filter->page      = $page;
		$filter->limit     = $per_page;
		$filter->order_by  = 'ui.user_item_id';
		$filter->order     = 'DESC';

		if ( ! empty( $args['course_id'] ) ) {
			$filter->item_id = absint( $args['course_id'] );
		}
		if ( ! empty( $args['user_id'] ) ) {
			$filter->user_id = absint( $args['user_id'] );
		}

		$statuses = self::status_list( $args['status'] ?? null );
		if ( count( $statuses ) === 1 ) {
			$filter->status = $statuses[0];
		} elseif ( count( $statuses ) > 1 ) {
			$filter->statues = $statuses;
		}

		try {
			$total_rows = 0;
			$rows       = UserItemsDB::getInstance()->get_user_items( $filter, $total_rows );
			$rows       = is_array( $rows ) ? $rows : array();
			$items      = array();

			foreach ( $rows as $row ) {
				$enroll_user_id   = absint( $row->user_id ?? 0 );
				$enroll_course_id = absint( $row->item_id ?? 0 );
				$course           = $enroll_course_id > 0 ? CourseModel::find( $enroll_course_id, true ) : false;
				$user             = $enroll_user_id > 0 ? UserModel::find( $enroll_user_id, true ) : false;

				$items[] = array(
					'enrollment_id' => absint( $row->user_item_id ?? 0 ),
					'user'          => array(
						'user_id'      => $enroll_user_id,
						'display_name' => $user instanceof UserModel ? $user->get_display_name() : '',
						'email'        => $user instanceof UserModel ? $user->get_email() : '',
					),
					'course'        => array(
						'course_id' => $enroll_course_id,
						'title'     => $course instanceof CourseModel ? $course->get_title() : '',
					),
					'status'        => (string) ( $row->status ?? '' ),
					'graduation'    => (string) ( $row->graduation ?? '' ),
					'start_time'    => (string) ( $row->start_time ?? '' ),
					'end_time'      => (string) ( $row->end_time ?? '' ),
					'ref_id'        => absint( $row->ref_id ?? 0 ),
					'ref_type'      => (string) ( $row->ref_type ?? '' ),
					'parent_id'     => absint( $row->parent_id ?? 0 ),
				);
			}

			return array(
				'items'      => $items,
				'pagination' => array(
					'page'        => $page,
					'per_page'    => $per_page,
					'total_items' => (int) $total_rows,
					'total_pages' => self::total_pages( (int) $total_rows, $per_page ),
				),
			);
		} catch ( Throwable $e ) {
			return self::internal();
		}
	}
}
