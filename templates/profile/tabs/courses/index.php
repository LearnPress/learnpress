<?php
/**
 * Template for displaying index of courses tab of user profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/courses/index.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<a href="<?php the_permalink(); ?>" class="course-title">

	<?php do_action( 'learn_press_courses_loop_item_title' ); ?>

</a>
