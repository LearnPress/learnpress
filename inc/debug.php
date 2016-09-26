<?php
//add_action( 'wp_head', 'learn_press_head_head' );
function learn_press_head_head() {
	learn_press_debug( $_REQUEST, false );
}


function test_mail() {
	$user = learn_press_get_user( 1 );

	//do_action( 'learn_press_course_submit_rejected', 1673, $user );
	//do_action( 'learn_press_course_submit_approved', 1673, $user );
	//do_action( 'learn_press_course_submit_for_reviewer', 1673, $user );
	//do_action( 'learn_press_user_enrolled_course', $user, 1673, 3 );
	//do_action( 'learn_press_order_status_pending_to_processing' );
	//do_action( 'learn_press_order_status_pending_to_completed' );
	//do_action( 'learn_press_order_status_processing_to_completed' );*/
	//do_action( 'learn_press_course_submitted', 920, $user );
	//do_action( 'learn_press_course_approved', 920, $user );
}

add_action( 'admin_footer', 'test_mail' );

