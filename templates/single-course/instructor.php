<?php
/**
 * Template for displaying the instructor of a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = LP_Global::course();
?>

<div class="course-author">
	<h3><?php _e('About the Instructor', 'learnpress');?></h3>
	<p class="author-name">
        <?php echo $course->get_instructor()->get_profile_picture();?>
        <?php echo $course->get_instructor_html();?>
    </p>
	<div class="author-bio">
		<?php echo $course->get_author()->get_description();?>
	</div>
</div>