<?php
/**
 * Template for displaying 'Featured' badge in archive course page for each course.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die;

if ( ! $course = learn_press_get_course() ) {
	return;
}

if ( ! $course->is_featured() ) {
	return;
}

?>
<span class="lp-badge featured-course"
      data-text="<?php echo _x( 'Featured', 'badge label featured', 'learnpress' ); ?>"></span>
