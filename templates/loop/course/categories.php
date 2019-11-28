<?php
/**
 * Template for displaying categories of a course in loop.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die();
?>
<div class="course-categories">
	<?php echo get_the_term_list( '', 'course_category', sprintf( '<span>%s</span>', __( 'in', 'learnpress' ) ), '|', '' ) ?>
</div>
