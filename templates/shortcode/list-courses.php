<?php
/**
 * Template for displaying list courses shortcode.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

use LearnPress\TemplateHooks\Course\ListCoursesTemplate;

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
			//do_action( 'learn-press/shortcode/before-courses-loop' );

			echo '<ul class="learn-press-courses" data-layout="grid">';

			while ( $query->have_posts() ) :
				$query->the_post();

				$course = learn_press_get_course( $query->post->ID );
				if ( ! $course ) {
					continue;
				}

				echo ListCoursesTemplate::render_course( $course );
			endwhile;

			echo '</ul>';

			/**
			 * LP Hook
			 */
			//do_action( 'learn-press/shortcode/after-main-content' );
		else :
			_e( 'No courses', 'learnpress' );
		endif;
		?>
	</div>
</div>

