<?php
/**
 * Template for displaying content of single quiz
 */
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $quiz;


get_header();
?>
<?php do_action( 'learn_press_before_main_quiz_content' ); ?>

<?php while ( have_posts() ): the_post(); ?>
	<?php learn_press_get_template_part( 'content', 'quiz' ); ?>
<?php endwhile; ?>
<?php do_action( 'learn_press_after_main_quiz_content' ); ?>

<?php
get_footer();