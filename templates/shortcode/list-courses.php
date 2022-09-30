<?php
/**
 * Template for displaying list courses shortcode.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $query ) ) {
	return;
}

?>
<div class="lp-archive-courses">
	<div class="lp-content-area">
		<?php if ( ! empty( $title ) ) : ?>
			<header class="learn-press-courses-header">
				<h1><?php echo esc_html( $title ); ?></h1>
			</header>
		<?php endif; ?>

		<?php
		if ( $query->have_posts() ) :
			/**
			 * LP Hook
			 */
			do_action( 'learn-press/shortcode/before-courses-loop' );

			LearnPress::instance()->template( 'course' )->begin_courses_loop();

			while ( $query->have_posts() ) :
				$query->the_post();

				learn_press_get_template_part( 'content', 'course' );

			endwhile;

			LearnPress::instance()->template( 'course' )->end_courses_loop();

			/**
			 * LP Hook
			 */
			do_action( 'learn-press/shortcode/after-main-content' );
			wp_reset_postdata();
		else :
			_e( 'No courses', 'learnpress' );
		endif;
		?>
	</div>
</div>

