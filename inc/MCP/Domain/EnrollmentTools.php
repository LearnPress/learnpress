<?php

namespace LearnPress\MCP\Domain;

use LearnPress\MCP\Mappers\ResponseMapper;
use LearnPress\MCP\Support\Errors;
use LearnPress\MCP\Support\Pagination;
use LearnPress\MCP\Support\Permissions;
use LearnPress\MCP\Support\Sanitizer;
use LearnPress\MCP\Support\Validator;
use LearnPress\Databases\UserItemsDB;
use LearnPress\Filters\UserItemsFilter;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserModel;
use LP_User_Items_Filter;
use Throwable;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Enrollment create/update executors.
 *
 * Uses LearnPress user-item models so course enrollment caches stay consistent.
 * Avoids creating duplicate active enrollments.
 */
class EnrollmentTools {

	/**
	 * Statuses considered an active enrollment.
	 */
	const ACTIVE_STATUSES = array(
		UserItemModel::STATUS_ENROLLED,
		UserItemModel::STATUS_PURCHASED,
		UserItemModel::STATUS_FINISHED,
		UserItemModel::STATUS_COMPLETED,
	);

	/**
	 * Execute `learnpress/enroll-student`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function enroll_student( $input ) {
		$args    = is_array( $input ) ? $input : array();
		$user_id = Validator::require_id( $args, 'user_id' );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}
		$course_id = Validator::require_id( $args, 'course_id' );
		if ( is_wp_error( $course_id ) ) {
			return $course_id;
		}

		$user = Validator::find_user( $user_id );
		if ( is_wp_error( $user ) ) {
			return $user;
		}
		$course = Validator::find_course( $course_id );
		if ( is_wp_error( $course ) ) {
			return $course;
		}

		if ( ! Permissions::can_manage_enrollments() ) {
			return Errors::forbidden();
		}

		$status = Validator::enrollment_status( $args['status'] ?? '' );
		if ( is_wp_error( $status ) ) {
			return $status;
		}
		if ( '' === $status ) {
			$status = UserItemModel::STATUS_ENROLLED;
		}
		if ( ! in_array( $status, Validator::enroll_create_statuses(), true ) ) {
			return Errors::invalid(
				__( 'enroll-student status must be "enrolled" or "purchased". Use update-enrollment for other states.', 'learnpress' )
			);
		}

		try {
			$existing = UserCourseModel::find( $user_id, $course_id, false );
			if ( $existing instanceof UserCourseModel
				&& in_array( $existing->get_status(), self::ACTIVE_STATUSES, true ) ) {
				return self::enroll_result( $existing, true );
			}

			$start_time = gmdate( 'Y-m-d H:i:s', time() );
			if ( isset( $args['start_time'] ) && '' !== $args['start_time'] ) {
				$parsed = Sanitizer::datetime( $args['start_time'] );
				if ( null === $parsed ) {
					return Errors::invalid( __( 'start_time is not a valid date/time.', 'learnpress' ) );
				}
				$start_time = $parsed;
			}

			$enrollment             = new UserCourseModel();
			$enrollment->user_id    = $user_id;
			$enrollment->item_id    = $course_id;
			$enrollment->item_type  = LP_COURSE_CPT;
			$enrollment->ref_type   = '';
			$enrollment->status     = $status;
			$enrollment->graduation = UserItemModel::GRADUATION_IN_PROGRESS;
			$enrollment->start_time = $start_time;
			$enrollment->save();

			return self::enroll_result( $enrollment, false );
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Execute `learnpress/update-enrollment`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function update_enrollment( $input ) {
		$args          = is_array( $input ) ? $input : array();
		$enrollment_id = Validator::require_id( $args, 'enrollment_id' );
		if ( is_wp_error( $enrollment_id ) ) {
			return $enrollment_id;
		}

		$enrollment = self::find_by_id( $enrollment_id );
		if ( ! $enrollment instanceof UserCourseModel ) {
			return Errors::not_found( __( 'Course enrollment not found.', 'learnpress' ) );
		}

		if ( ! Permissions::can_manage_enrollments() ) {
			return Errors::forbidden();
		}

		if ( ! empty( $args['user_id'] ) && absint( $args['user_id'] ) !== (int) $enrollment->user_id ) {
			return Errors::not_found( __( 'Enrollment does not match the provided user.', 'learnpress' ) );
		}
		if ( ! empty( $args['course_id'] ) && absint( $args['course_id'] ) !== (int) $enrollment->item_id ) {
			return Errors::not_found( __( 'Enrollment does not match the provided course.', 'learnpress' ) );
		}

		$status = Validator::enrollment_status( $args['status'] ?? '' );
		if ( is_wp_error( $status ) ) {
			return $status;
		}
		$graduation = Validator::graduation( $args['graduation'] ?? '' );
		if ( is_wp_error( $graduation ) ) {
			return $graduation;
		}

		// Do not silently reopen a finished/completed enrollment to an active state.
		if ( '' !== $status
			&& in_array( $enrollment->get_status(), array( UserItemModel::STATUS_FINISHED, UserItemModel::STATUS_COMPLETED ), true )
			&& in_array( $status, Validator::enroll_create_statuses(), true ) ) {
			return Errors::invalid(
				__( 'Cannot reopen a finished enrollment. Create a new enrollment instead.', 'learnpress' )
			);
		}

		$start_time = null;
		if ( isset( $args['start_time'] ) && '' !== $args['start_time'] ) {
			$start_time = Sanitizer::datetime( $args['start_time'] );
			if ( null === $start_time ) {
				return Errors::invalid( __( 'start_time is not a valid date/time.', 'learnpress' ) );
			}
		}
		$end_time = null;
		if ( isset( $args['end_time'] ) && '' !== $args['end_time'] ) {
			$end_time = Sanitizer::datetime( $args['end_time'] );
			if ( null === $end_time ) {
				return Errors::invalid( __( 'end_time is not a valid date/time.', 'learnpress' ) );
			}
		}

		try {
			if ( '' !== $status ) {
				$enrollment->status = $status;
			}
			if ( '' !== $graduation ) {
				$enrollment->graduation = $graduation;
			}
			if ( null !== $start_time ) {
				$enrollment->start_time = $start_time;
			}
			if ( null !== $end_time ) {
				$enrollment->end_time = $end_time;
			}

			$enrollment->save();

			return array( 'enrollment' => ResponseMapper::enrollment( $enrollment ) );
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Execute `learnpress/get-student-progress`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function get_student_progress( $input ) {
		$args = Sanitizer::input_array( $input, 'learnpress/get-student-progress' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$user_id = Validator::require_id( $args, 'user_id' );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}
		$course_id = Validator::require_id( $args, 'course_id' );
		if ( is_wp_error( $course_id ) ) {
			return $course_id;
		}

		try {
			$user_course = UserCourseModel::find( $user_id, $course_id, true );
			if ( ! $user_course instanceof UserCourseModel ) {
				return Errors::not_found( __( 'Enrollment not found for this user and course.', 'learnpress' ) );
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
			return Errors::internal();
		}
	}

	/**
	 * Execute `learnpress/get-enrollments`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function get_enrollments( $input ) {
		$args = Sanitizer::input_array( $input, 'learnpress/get-enrollments' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$page              = Pagination::page( $args['page'] ?? 1 );
		$per_page          = Pagination::per_page( $args['per_page'] ?? 10 );
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

		$statuses = Sanitizer::status_list( $args['status'] ?? null );
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
					'total_pages' => Pagination::total_pages( (int) $total_rows, $per_page ),
				),
			);
		} catch ( Throwable $e ) {
			return Errors::internal();
		}
	}

	/**
	 * Resolve a course enrollment by its user_item_id.
	 *
	 * @param int $enrollment_id Enrollment ID.
	 *
	 * @return UserCourseModel|false
	 */
	protected static function find_by_id( int $enrollment_id ) {
		$filter               = new LP_User_Items_Filter();
		$filter->user_item_id = $enrollment_id;
		$filter->item_type    = LP_COURSE_CPT;

		$model = UserCourseModel::get_user_item_model_from_db( $filter );

		return $model instanceof UserCourseModel ? $model : false;
	}

	/**
	 * Build the enroll-student result payload.
	 *
	 * @param UserCourseModel $enrollment Enrollment model.
	 * @param bool            $existing   Whether this was a pre-existing enrollment.
	 *
	 * @return array
	 */
	protected static function enroll_result( UserCourseModel $enrollment, bool $existing ): array {
		return array(
			'enrollment_id'    => (int) $enrollment->get_user_item_id(),
			'user_id'          => (int) $enrollment->user_id,
			'course_id'        => (int) $enrollment->item_id,
			'status'           => (string) $enrollment->get_status(),
			'start_time'       => (string) $enrollment->get_start_time(),
			'already_enrolled' => $existing,
		);
	}
}
