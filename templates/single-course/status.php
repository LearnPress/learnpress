<?php
global $course;
$user = learn_press_get_current_user();

if ( !$user->has( 'purchased-course', $course->id ) ) {
	return;
}

?>

<span class="learn-press-course-status"><?php echo $user->get_course_status( $course->id ); ?></span>
