<?php
/**
 * The template for displaying single course content
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
get_header( ); ?>

	<?php do_action( 'learn_press_before_main_content' ); ?>

	<?php while ( have_posts() ) : the_post();?>

		<?php learn_press_get_template_part( 'content', 'single-course' ); ?>

	<?php endwhile; ?>

	<?php do_action( 'learn_press_after_main_content' ); ?>

<?php get_footer( ); ?>
