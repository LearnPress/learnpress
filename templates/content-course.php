<?php
/**
 * Template for displaying course content within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-course.php
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

?>

<li id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<?php

	// @since 3.0.0
	do_action( 'learn-press/before-courses-loop-item' );
	?>

    <a href="<?php the_permalink(); ?>" class="course-permalink">

		<?php
		// @since 3.0.0
		do_action( 'learn-press/courses-loop-item-title' );
		'abc/].php';
		?>

    </a>

	<?php

	// @since 3.0.0
	do_action( 'learn-press/after-courses-loop-item' );

	?>
</li>