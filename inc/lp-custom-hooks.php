<?php

/**
 * Re-evaluate course results when user started, tried and finished quiz.
 *
 * @since 3.x.x
 *
 * @param int $quiz_id
 * @param int $course_id
 * @param int $user_id
 */
function learn_press_evaluate_course_results( $quiz_id, $course_id, $user_id ) {
	$user = learn_press_get_user( $user_id );

	LP_Object_Cache::delete( 'user-course-' . $user_id . '-' . $course_id, 'learn-press/course-results' );

	if ( $courseData = $user->get_course_data( $course_id ) ) {
		$r = $courseData->calculate_course_results();
	}

}

add_action( 'learn-press/user/quiz-started', 'learn_press_evaluate_course_results', 10, 3 );
add_action( 'learn-press/user/quiz-redone', 'learn_press_evaluate_course_results', 10, 3 );
add_action( 'learn-press/user/quiz-finished', 'learn_press_evaluate_course_results', 10, 3 );

add_filter( 'learn-press/create-user-item-meta', function ( $meta, $item ) {
//	switch ( $item['item_type'] ) {
//		case LP_QUIZ_CPT:
//			shuffle( $meta['questions'] );
//	}

	return $meta;
}, 100, 2 );

