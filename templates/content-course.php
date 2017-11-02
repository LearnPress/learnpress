<?php
/**
 * Template for displaying course content within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-course.php
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<li id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<?php do_action( 'learn_press_before_courses_loop_item' ); ?>

    <a href="<?php the_permalink(); ?>" class="course-permalink">

		<?php do_action( 'learn_press_courses_loop_item_title' ); ?>

    </a>

	<?php do_action( 'learn_press_after_courses_loop_item' ); ?>

	<?php learn_press_get_template( 'single-course/buttons.php' ); ?>

</li>