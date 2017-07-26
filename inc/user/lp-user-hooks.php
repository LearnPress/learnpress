<?php

/**********************************************/
/*       Functions are used for hooks         */
/**********************************************/

function learn_press_hk_before_start_quiz( $true, $quiz_id, $course_id, $user_id ) {
	if ( 'yes' !== get_post_meta( $quiz_id, '_lp_archive_history', true ) ) {
		learn_press_remove_user_items( $user_id, $quiz_id, $course_id );
	}

	return $true;
}

add_filter( 'learn-press/before-start-quiz', 'learn_press_hk_before_start_quiz', 10, 4 );