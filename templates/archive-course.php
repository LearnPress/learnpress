<?php
/**
 * Template for displaying content of archive courses page.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * @since 4.0.0
 *
 * @see LP_Template_General::template_header()
 */
if ( empty( $is_block_theme ) ) {
	do_action( 'learn-press/template-header' );
}

/**
 * LP Hook
 */
do_action( 'learn-press/before-main-content' );

$page_title = learn_press_page_title( false );
?>

<div class="lp-content-area">
	<?php if ( $page_title ) : ?>
		<header class="learn-press-courses-header">
			<h1><?php echo $page_title; ?></h1>

			<?php do_action( 'lp/template/archive-course/description' ); ?>
		</header>
	<?php endif; ?>

	<?php
	/**
	 * LP Hook
	 */
	do_action( 'learn-press/before-courses-loop' );
	LP()->template( 'course' )->begin_courses_loop();

	if ( lp_is_archive_course_load_via_api() ) {
		echo '<div class="lp-archive-course-skeleton" style="width:100%">';
		echo lp_skeleton_animation_html( 10, 'random', 'height:20px', 'width:100%' );
		echo '</div>';
	} else {
		if ( have_posts() ) {
			while ( have_posts() ) :
				the_post();

				learn_press_get_template_part( 'content', 'course' );

			endwhile;
		} else {
			LP()->template( 'course' )->no_courses_found();
		}
	}

	LP()->template( 'course' )->end_courses_loop();
	do_action( 'learn-press/after-courses-loop' );


	/**
	 * LP Hook
	 */
	do_action( 'learn-press/after-main-content' );

	/**
	 * LP Hook
	 *
	 * @since 4.0.0
	 */
	do_action( 'learn-press/sidebar' );
	?>
</div>

<?php
/**
 * @since 4.0.0
 *
 * @see   LP_Template_General::template_footer()
 */
if ( empty( $is_block_theme ) ) {
	do_action( 'learn-press/template-footer' );
}
