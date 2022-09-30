<?php
/**
 * Template for displaying course sidebar.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hide sidebar if there is no content
 */
if ( ! is_active_sidebar( 'course-sidebar' ) && ! LearnPress::instance()->template( 'course' )->has_sidebar() ) {
	return;
}
?>

<aside class="course-summary-sidebar">
	<div class="course-summary-sidebar__inner">
		<div class="course-sidebar-top">
			<?php
			/**
			 * LP Hook
			 *
			 * @since 4.0.0
			 */
			do_action( 'learn-press/before-course-summary-sidebar' );

			/**
			 * LP Hook
			 *
			 * @since 4.0.0
			 *
			 * @see   LP_Template_Course::course_sidebar_preview() - 10
			 * @see   LP_Template_Course::course_featured_review() - 20
			 */
			do_action( 'learn-press/course-summary-sidebar' );

			/**
			 * LP Hook
			 *
			 * @since 4.0.0
			 */
			do_action( 'learn-press/after-course-summary-sidebar' );

			?>
		</div>

		<?php if ( is_active_sidebar( 'course-sidebar' ) ) : ?>
			<div class="course-sidebar-secondary">
				<?php dynamic_sidebar( 'course-sidebar' ); ?>
			</div>
		<?php endif; ?>
	</div>
</aside>
