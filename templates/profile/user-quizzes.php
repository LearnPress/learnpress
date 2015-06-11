<?php

do_action( 'learn_press_before_quiz_results' );

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
				learn_press_get_template( 'profile/quiz-content.php', array( 'quiz_id' => $quiz ) );
			}
		}
		do_action( 'learn_press_after_quiz_result' );
	endwhile;
	if ( !$check ) :
		do_action( 'learn_press_before_no_completed_quiz' );
		echo '<p>' . __( 'You have not finished any quizzes yet!', 'learn_press' ) . '</p>';
		do_action( 'learn_press_after_no_completed_quiz' );
	endif;
else :
	do_action( 'learn_press_before_no_enrolled_course' );
	echo '<p>' . __( 'You have not taken any courses yet!', 'learn_press' ) . '</p>';
	do_action( 'learn_press_after_no_enrolled_course' );
endif;
do_action( 'learn_press_after_enrolled_course' );
wp_reset_postdata();

do_action( 'learn_press_after_quiz_results' );
