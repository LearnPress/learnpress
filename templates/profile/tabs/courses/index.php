<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
} ?>

<a href="<?php the_permalink(); ?>" class="course-title">

	<?php do_action( 'learn_press_courses_loop_item_title' ); ?>

</a>
