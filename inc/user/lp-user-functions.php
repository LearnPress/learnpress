<?php
/**
 * @file
 */

add_action( 'learn_press_user_finished_course', 'learn_press_user_finished_course_send_email', 999, 2 );

function learn_press_user_finished_course_send_email( $course_id = null, $user_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );
	if ( !$user_id ) $user_id = get_current_user_id();
	$user = get_user_by( 'id', $user_id );
	if ( empty( $user->ID ) || !$course_id ) return false;
	$mail_to = $user->user_email;

	$assessment = get_post_meta( $course_id, '_lpr_course_final', true );
	if ( 'yes' == $assessment ) {
		$quiz_id       = lpr_get_final_quiz( $course_id );
		$quiz_result   = learn_press_get_quiz_result( $user_id, $quiz_id );
		$course_result = $quiz_result['mark_percent'] * 100;
	} else {
		$course_result = 100;
	}
	$args = apply_filters(
		'learn_press_vars_passed_course',
		array(
			'user_name'     => !empty( $user->display_name ) ? $user->display_name : $user->user_nicename,
			'course_name'   => get_the_title( $course_id ),
			'course_link'   => get_permalink( $course_id ),
			'course_result' => sprintf( __( '%d%% of total', 'learn_press' ), intval( $course_result ) )
		)
	);
	learn_press_send_mail(
		$mail_to,
		'passed_course',
		$args
	);
}


function learn_press_get_current_user_id() {
	$user = learn_press_get_current_user();
	return $user->id;
}

/**
 * Get current user
 * @return LP_User
 */
function learn_press_get_current_user() {
	$user_id      = get_current_user_id();
	$current_user = false;
	if ( $user_id ) {
		$current_user = learn_press_get_user( $user_id );
	} else {
		$current_user = LP_User_Guest::instance();
	}
	return $current_user;
}

function learn_press_get_user( $user_id ) {
	if( $user_id ){
		return LP_User::get_user( $user_id );
	}
	return LP_User_Guest::instance();
}

/**
 * Get all prerequisite courses that user need to pass before take a course
 *
 * @param bool $user_id
 * @param      $course_id -=90a4
 *
 * @return array|bool
 */
function learn_press_user_prerequisite_courses( $user_id = false, $course_id ) {
	if ( !$user_id ) {
		$user    = learn_press_get_current_user();
		$user_id = $user->ID;
	}
	$prerequisite = (array) get_post_meta( $course_id, '_lpr_course_prerequisite', true );
	$courses      = false;
	if ( $prerequisite ) {

		$course_completed = get_user_meta( $user_id, '_lpr_course_completed', true );
		foreach ( $prerequisite as $course ) {
			if ( $course && $course_completed ) {
				if ( !array_key_exists( $course, $course_completed ) ) {
					if ( !$courses ) $courses = array();
					$courses[] = $course;
				}
			}
		}
	}
	return $courses;
}

function learn_press_before_take_course_prerequisite( $can_take, $user_id, $course_id ) {
	return false == learn_press_user_prerequisite_courses( $user_id, $course_id );
}

//add_filter( 'learn_press_before_take_course', 'learn_press_before_take_course_prerequisite', 5, 3 );


function learn_press_send_user_email_order( $status, $order_id ) {
	return;
	$status = strtolower( $status );
	if ( 'completed' == $status ) {
		$order        = new LP_Order( $order_id );
		$mail_to      = $order->get_user( 'email' );
		$instructors  = array();
		$course_title = '';

		$transaction_object = $order->get_items();
		$items              = $transaction_object->products;
		$item               = array_shift( $items );

		$course = get_post( $item['id'] );

		$course_title = get_the_title( $item['id'] );

		$instructor                   = LP_User::get_user( $course->post_author );
		$instructors[$instructor->ID] = $instructor->data->display_name;

		learn_press_send_mail(
			$mail_to,
			'enrolled_course',
			apply_filters(
				'learn_press_vars_enrolled_course',
				array(
					'user_name'   => $order->get_user( 'display_name' ),
					'course_name' => $course_title,
					'course_link' => get_permalink( $item['id'] )
				),
				$course,
				$instructor
			)
		);
	}
}

add_action( 'learn_press_update_order_status', 'learn_press_send_user_email_order', 5, 2 );


function learn_press_post_review_message_box(){
	$user = learn_press_get_current_user();
	if( ! $user->is_instructor() ) {
		return;
	}
	?>
	<div id="learn-press-review-message">
		<h4><?php _e( 'Your message to Reviewer', 'learn_press' );?></h4>
		<textarea resize="none" placeholder="<?php _e( 'Enter some information here for reviewer', 'learn_press' );?>"></textarea>
	</div>
	<?php
}
add_action( 'post_submitbox_start', 'learn_press_post_review_message_box');

/**
 * Add more 2 user roles teacher and student
 *
 * @access public
 * @return void
 */
function learn_press_add_user_roles() {

	/* translators: user role */
	_x( 'Instructor', 'User role' );
	add_role(
		LP()->teacher_role,
		'Instructor',
		array()
	);
	$course_cap = LP()->course_post_type . 's';
	$lesson_cap = LP()->lesson_post_type . 's';
	$order_cap  = LP()->order_post_type . 's';
	// teacher
	$teacher = get_role( LP()->teacher_role );
	$teacher->add_cap( 'delete_published_' . $course_cap );
	$teacher->add_cap( 'edit_published_' . $course_cap );
	$teacher->add_cap( 'edit_' . $course_cap );
	$teacher->add_cap( 'delete_' . $course_cap );

	$teacher->add_cap( 'delete_published_' . $lesson_cap );
	$teacher->add_cap( 'edit_published_' . $lesson_cap );
	$teacher->add_cap( 'edit_' . $lesson_cap );
	$teacher->add_cap( 'delete_' . $lesson_cap );
	$teacher->add_cap( 'publish_' . $lesson_cap );
	$teacher->add_cap( 'upload_files' );
	$teacher->add_cap( 'read' );
	$teacher->add_cap( 'edit_posts' );

	// administrator
	$admin = get_role( 'administrator' );
	$admin->add_cap( 'delete_' . $course_cap );
	$admin->add_cap( 'delete_published_' . $course_cap );
	$admin->add_cap( 'edit_' . $course_cap );
	$admin->add_cap( 'edit_published_' . $course_cap );
	$admin->add_cap( 'publish_' . $course_cap );
	$admin->add_cap( 'delete_private_' . $course_cap );
	$admin->add_cap( 'edit_private_' . $course_cap );
	$admin->add_cap( 'delete_others_' . $course_cap );
	$admin->add_cap( 'edit_others_' . $course_cap );

	$admin->add_cap( 'delete_' . $lesson_cap );
	$admin->add_cap( 'delete_published_' . $lesson_cap );
	$admin->add_cap( 'edit_' . $lesson_cap );
	$admin->add_cap( 'edit_published_' . $lesson_cap );
	$admin->add_cap( 'publish_' . $lesson_cap );
	$admin->add_cap( 'delete_private_' . $lesson_cap );
	$admin->add_cap( 'edit_private_' . $lesson_cap );
	$admin->add_cap( 'delete_others_' . $lesson_cap );
	$admin->add_cap( 'edit_others_' . $lesson_cap );

	$admin->add_cap( 'delete_' . $order_cap );
	$admin->add_cap( 'delete_published_' . $order_cap );
	$admin->add_cap( 'edit_' . $order_cap );
	$admin->add_cap( 'edit_published_' . $order_cap );
	$admin->add_cap( 'publish_' . $order_cap );
	$admin->add_cap( 'delete_private_' . $order_cap );
	$admin->add_cap( 'edit_private_' . $order_cap );
	$admin->add_cap( 'delete_others_' . $order_cap );
	$admin->add_cap( 'edit_others_' . $order_cap );
}
add_action( 'init', 'learn_press_add_user_roles' );

function learn_press_after_logged_in() {

}
add_action('wp_login', 'learn_press_after_logged_in');

function head_head_head(){

}

add_action( 'admin_head', 'head_head_head' );
