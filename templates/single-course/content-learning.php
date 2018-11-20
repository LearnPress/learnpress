<?php
/**
 * Template for displaying content of learning course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content-learning.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

?>

<?php
/**
 * @deprecated
 */
do_action( 'learn_press_before_content_learning' );
?>

<div class="course-learning-summary">

	<?php
	/**
	 * @deprecated
	 */
	do_action( 'learn_press_content_learning_summary' );

	/**
	 * @since 3.0.0
	 *
	 * @see   learn_press_course_meta_start_wrapper
	 * @see   learn_press_course_instructor
	 * @see   learn_press_course_students
	 * @see   learn_press_course_meta_end_wrapper
	 * @see   //learn_press_single_course_content_lesson
	 * @see   learn_press_single_course_content_item
	 * @see   learn_press_course_progress
	 * @see   learn_press_course_tabs
	 * @see   learn_press_course_buttons
	 * @see   learn_press_course_remaining_time
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

