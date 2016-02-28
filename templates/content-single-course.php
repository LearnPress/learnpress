<?php
/**
 * The template for display the content of single course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $course;
do_action( 'learn_press_before_single_course' ); ?>

<div id="post-<?php the_ID(); ?>" <?php post_class(); ?> itemscope itemtype="http://schema.org/CreativeWork">

	<?php do_action( 'learn_press_before_single_course_summary' ); ?>

	<div class="course-summary">

		<?php if ( LP()->user->has( 'enrolled-course', $course->id ) || LP()->user->has( 'finished-course', $course->id ) ) { ?>

			<?php learn_press_get_template( 'single-course/content-learning.php' ); ?>

		<?php } else { ?>

			<?php learn_press_get_template( 'single-course/content-landing.php' ); ?>

		<?php } ?>
	</div>

	<?php do_action( 'learn_press_after_single_course_summary' ); ?>

	<?php /*
	<?php do_action( 'learn_press_before_course_header' ); ?>
	<header class="entry-header">
		<?php
		do_action( 'learn_press_before_the_title' );
		the_title( '<h1 class="entry-title">', '</h1>' );
		do_action( 'learn_press_after_the_title' );
		?>
	</header>
	<!-- .entry-header -->
	<?php do_action( 'learn_press_before_course_content' ); ?>
	<div class="entry-content">
		<?php
		do_action( 'learn_press_before_the_content' );
		if ( learn_press_is_enrolled_course() ) {
			learn_press_get_template_part( 'course_content', 'learning_page' );
		} else
			learn_press_get_template_part( 'course_content', 'landing_page' );
		do_action( 'learn_press_after_the_content' );
		?>
	</div>
	<!-- .entry-content -->
	<?php do_action( 'learn_press_before_course_footer' ); ?>
	<footer class="entry-footer">
		<?php
		edit_post_link( __( 'Edit', 'learnpress' ), '<span class="edit-link">', '</span>' );
		?>
	</footer>
	<!-- .entry-footer -->
	*/ ?>
</div><!-- #post-## -->

<?php do_action( 'learn_press_after_single_course' ); ?>
