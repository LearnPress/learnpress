<?php

/**
 * Class LP_Nonce_Helper
 */
class LP_Nonce_Helper {

	/**
	 * Create nonce for course action.
	 * Return nonce created with format 'learn-press-$action-$course_id-course-$user_id'
	 *
	 * @param string $action [retake, purchase, enroll]
	 * @param int    $course_id
	 * @param int    $user_id
	 *
	 * @since 3.x.x
	 *
	 * @return string
	 */
	public static function create_course( $action, $course_id = 0, $user_id = 0 ) {
		if ( ! $course_id ) {
			$course_id = get_the_ID();
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		return wp_create_nonce( sprintf( 'learn-press-%s-course-%s-%s', $action, $course_id, $user_id ) );
	}

	/**
	 * Verify nonce for course action.
	 *
	 * @param string $nonce
	 * @param string $action
	 * @param int    $course_id
	 * @param int    $user_id
	 *
	 * @since 3.x.x
	 *
	 * @return bool
	 */
	public static function verify_course( $nonce, $action, $course_id = 0, $user_id = 0 ) {
		if ( ! $course_id ) {
			$course_id = get_the_ID();
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		return wp_verify_nonce( $nonce, sprintf( 'learn-press-%s-course-%s-%s', $action, $course_id, $user_id ) );
	}
}