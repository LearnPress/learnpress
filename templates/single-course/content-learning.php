<?php
/**
 * Template for displaying content of learning course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content-learning.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
// add nofication when duration blocked
$user        = learn_press_get_current_user();
$course      = learn_press_get_the_course();
if($user->user_check_blocked_duration( $course->get_id() ) == true && $user->can_retake_course($course->get_id()) >=0 && ! $user->get_course_data( $course->get_id() )->is_finished() ){
    echo '<div class="lp-nofication__duration">';
    echo '<p style="color:red">'.esc_html__('The course duration has run out. You cannot access the content of this course anymore.','learnpress').'</p>';
    echo '</div>';
}
?>

<?php
/**
 * @deprecated
 */
do_action( 'learn_press_before_content_learning' );
?>

<div class="course-learning-summary">
    <?php

    ?>
	<?php
	/**
	 * @deprecated
	 */
	do_action( 'learn_press_content_learning_summary' );

	/**
	 * @since 3.0.0
	 *
	 * @see   learn_press_course_meta_start_wrapper()
	 */
	do_action( 'learn-press/content-learning-summary' );
	?>

</div>

<?php
/**
 * @deprecated
 */
do_action( 'learn_press_after_content_learning' );
?>

