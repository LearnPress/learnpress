<?php
/**
 * Template for displaying archive course content
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 2.0.6
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $post, $wp_query, $lp_tax_query;
?>
<?php do_action( 'learn_press_before_main_content' ); ?>

<?php do_action( 'learn_press_archive_description' ); ?>

<?php if ( LP()->wp_query->have_posts() ) : ?>

	<?php do_action( 'learn_press_before_courses_loop' ); ?>

	<?php learn_press_begin_courses_loop(); ?>


	<?php while ( LP()->wp_query->have_posts() ) : LP()->wp_query->the_post(); ?>

		<?php learn_press_get_template_part( 'content', 'course' ); ?>

	<?php endwhile; ?>

	<?php learn_press_end_courses_loop(); ?>

	<?php do_action( 'learn_press_after_courses_loop' ); ?>
<?php else: ?>
	<?php learn_press_display_message( __( 'No course found.', 'learnpress' ), 'error' ); ?>
<?php endif; ?>

<?php do_action( 'learn_press_after_main_content' ); ?>
