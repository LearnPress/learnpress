<?php
/**
 * Template for displaying content of archive courses page.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.1
 */

//use LearnPress\TemplateHooks\Course\ListCoursesTemplate;

defined( 'ABSPATH' ) || exit;
wp_enqueue_script( 'lp-courses' );
wp_enqueue_style( 'learnpress' );

/**
 * @since 4.0.0
 *
 * @see LP_Template_General::template_header()
 */
if ( ! wp_is_block_theme() ) {
	do_action( 'learn-press/template-header' );
}

/**
 * LP Hook
 */
do_action( 'learn-press/before-main-content' );

$page_title = learn_press_page_title( false );
$classes    = [];

if ( is_active_sidebar( 'archive-courses-sidebar' ) ) {
	$classes[] = 'has-sidebar';
}
// For test
/*$m = ListCoursesTemplate::instance();
echo $m->html_count_course_free();
echo $m->html_count_students();*/
// End test
/**
 * @since 4.2.3.4
 *
 *  filter lp/show-archive-course/title
 */
?>

<div class="lp-content-area <?php echo esc_attr( implode( $classes ) ); ?>">
	<div class="lp-main-content">
	<?php if ( $page_title && apply_filters( 'lp/show-archive-course/title', true ) ) : ?>
		<header class="learn-press-courses-header">
			<h1><?php echo wp_kses_post( $page_title ); ?></h1>

			<?php do_action( 'lp/template/archive-course/description' ); ?>
		</header>
	<?php endif; ?>

	<?php
	/**
	 * LP Hook
	 */
	do_action( 'learn-press/before-courses-loop' );
	LearnPress::instance()->template( 'course' )->begin_courses_loop();

	if ( LP_Settings_Courses::is_ajax_load_courses() && ! LP_Settings_Courses::is_no_load_ajax_first_courses() ) {
		echo '<div class="lp-archive-course-skeleton" style="width:100%">';
		//lp_skeleton_animation_html( 10, 'random', 'height:20px', 'width:100%' );
		echo '</div>';
	} else {
		if ( have_posts() ) {
			while ( have_posts() ) :
				the_post();

				learn_press_get_template_part( 'content', 'course' );

			endwhile;
		} else {
			LearnPress::instance()->template( 'course' )->no_courses_found();
		}

		if ( LP_Settings_Courses::is_ajax_load_courses() ) {
			echo '<div class="lp-archive-course-skeleton no-first-load-ajax" style="width:100%; display: none">';
			//lp_skeleton_animation_html( 10, 'random', 'height:20px', 'width:100%' );
			echo '</div>';
		}
	}

	LearnPress::instance()->template( 'course' )->end_courses_loop();
	do_action( 'learn-press/after-courses-loop' );
	?>
	</div>
	<?php
	/**
	 * LP Hook
	 *
	 * @since 4.0.0
	 */
	do_action( 'learn-press/archive-course/sidebar' );
	?>
</div>

<?php
/**
 * LP Hook
 */
do_action( 'learn-press/after-main-content' );

/**
 * @since 4.0.0
 *
 * @see   LP_Template_General::template_footer()
 */
if ( ! wp_is_block_theme() ) {
	do_action( 'learn-press/template-footer' );
}
