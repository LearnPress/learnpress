<?php
/**
 * Template for displaying archive course content
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<?php get_header(); ?>

<?php do_action( 'learn_press_before_main_content' ); ?>

<?php if ( apply_filters( 'learn_press_show_page_title', true ) ) { ?>

	<h1 class="page-title">

		<?php learn_press_page_title(); ?>

	</h1>

<?php } ?>

<?php do_action( 'learn_press_archive_description' ); ?>

<?php if ( have_posts() ) : ?>

	<?php do_action( 'learn_press_before_courses_loop' ); ?>

	<?php while ( have_posts() ) : the_post(); ?>

		<?php learn_press_get_template_part( 'content', 'course' ); ?>

	<?php endwhile; ?>

	<?php do_action( 'learn_press_after_courses_loop' ); ?>

<?php endif; ?>

<?php do_action( 'learn_press_after_main_content' ); ?>

<?php get_footer(); ?>
