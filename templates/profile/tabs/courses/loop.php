<?php
/**
 * User Courses enrolled
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

?>
<li>
	<?php do_action( 'learn_press_before_enrolled_course' );?>

	<?php the_title( sprintf( '<a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a>' ); ?>

	<?php do_action( 'learn_press_after_enrolled_course_title' ); ?>

</li>