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
	 * @since 3.0.0
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
	 * @since 3.0.0
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

	public static function quiz_action( $action, $quiz_id, $course_id, $ajax = false ) {
		?>
        <input type="hidden" name="quiz-id" value="<?php echo $quiz_id; ?>">
        <input type="hidden" name="course-id" value="<?php echo $course_id; ?>">
		<?php if ( $ajax ) { ?>
            <input type="hidden" name="lp-ajax" value="<?php echo $action; ?>-quiz">
		<?php } else { ?>
            <input type="hidden" name="lp-<?php echo $action; ?>-quiz" value="<?php echo $quiz_id; ?>">
		<?php } ?>
        <input type="hidden" name="<?php echo $action; ?>-quiz-nonce"
               value="<?php echo wp_create_nonce( sprintf( 'learn-press/quiz/%s/%s-%s-%s', $action, get_current_user_id(), $course_id, $quiz_id ) ); ?>">
		<?php
	}

	public static function verify_quiz_action( $action, $nonce = '', $quiz_id = 0, $course_id = 0 ) {
		if ( ! $nonce ) {
			$nonce = LP_Request::get_post( $action . '-quiz-nonce' );
		}

		if ( ! $quiz_id ) {
			global $lp_course_item;
			$quiz_id = $lp_course_item instanceof LP_Course_Item ? $lp_course_item->get_id() : 0;
		}

		if ( ! $course_id ) {
			$course_id = get_the_ID();
		}

		return wp_verify_nonce( $nonce, sprintf( 'learn-press/quiz/%s/%s-%s-%s', $action, get_current_user_id(), $course_id, $quiz_id ) );
	}
}