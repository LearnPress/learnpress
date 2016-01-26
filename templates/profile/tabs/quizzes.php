<?php
/**
 * Template for displaying the quizzes in profile
 *
 * @author ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

!defined( 'ASBPATH' ) || exit();

do_action( 'learn_press_before_enrolled_course' );
$my_query = learn_press_get_enrolled_courses( $user->ID );
$check    = 0;
if ( $my_query->have_posts() ) :
	while ( $my_query->have_posts() ) : $my_query->the_post();
		$quizzes = learn_press_get_quizzes( get_the_ID() );
		do_action( 'learn_press_before_quiz_result' );
		foreach ( $quizzes as $quiz ) {
			if ( learn_press_user_has_completed_quiz( $user->ID, $quiz ) ) {
				$check = 1;
				learn_press_get_template( 'profile/quiz-content.php', array( 'user_id' => $user->ID, 'quiz_id' => $quiz ) );
			}
		}
		do_action( 'learn_press_after_quiz_result' );
	endwhile;
	if ( !$check ) :
		do_action( 'learn_press_before_no_completed_quiz' );
		learn_press_display_message( __( 'You have not finished any quizzes yet!', 'learn_press' ) );
		do_action( 'learn_press_after_no_completed_quiz' );
	endif;
else :
	do_action( 'learn_press_before_no_enrolled_course' );
	learn_press_display_message( __( 'You have not taken any courses yet!', 'learn_press' ) );
	do_action( 'learn_press_after_no_enrolled_course' );
endif;
wp_reset_postdata();
do_action( 'learn_press_after_quiz_results' );
