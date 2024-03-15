<?php
/**
 * Template for displaying list courses shortcode.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.1
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
		$posts = $query->posts;
		if ( count( $posts ) > 0 ) :
			echo '<ul class="learn-press-courses" data-layout="grid">';

			foreach ( $posts as $post ) {
				$course = learn_press_get_course( $post->ID );
				if ( ! $course ) {
					continue;
				}

				echo ListCoursesTemplate::render_course( $course );
			}

			echo '</ul>';
		else :
			_e( 'No courses', 'learnpress' );
		endif;
		?>
	</div>
</div>

