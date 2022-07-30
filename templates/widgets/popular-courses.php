<?php
/**
 * Template for displaying content of Popular Courses widget.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/widgets/popular-courses/default.php.
 *
 * @author   ThimPress
 * @category Widgets
 * @package  Learnpress/Templates
 * @version  4.0.1
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $instance ) ) {
	return;
}

if ( ! isset( $courses ) ) {
	esc_html_e( 'No courses', 'learnpress' );

	return;
}

global $post;
?>

<div class="lp-widget-popular-courses <?php echo esc_attr( $instance['css_class'] ); ?>">
	<div class="lp-widget-popular-courses__content">
		<?php
		foreach ( $courses as $course_id ) {
			if ( empty( $course_id ) ) {
				continue;
			}

			$post = get_post( $course_id );
			setup_postdata( $post );
			$course = learn_press_get_course( $course_id );
			?>

			<div class="lp-widget-course">

				<!-- course thumbnail -->
				<?php if ( ! empty( $instance['show_thumbnail'] ) && $course->get_image( 'medium' ) ) : ?>
					<div class="lp-widget-course__image">
						<a href="<?php echo esc_url_raw( $course->get_permalink() ); ?>">
							<?php echo wp_kses_post( $course->get_image( 'medium' ) ); ?>
						</a>
					</div>
				<?php endif; ?>

				<div class="lp-widget-course__content">
					<!-- course title -->
					<a href="<?php echo esc_url_raw( get_the_permalink( $course->get_id() ) ); ?>">
						<h3 class="lp-widget-course__title"><?php echo esc_html( $course->get_title() ); ?></h3>
					</a>

					<!-- course content -->
					<?php if ( ! empty( $instance['desc_length'] ) && absint( $instance['desc_length'] ) > 0 ) : ?>
						<div class="lp-widget-course__description">
							<?php echo wp_kses_post( $course->get_content( 'raw', absint( $instance['desc_length'] ), '...' ) ); ?></div>
					<?php endif; ?>

					<div class="lp-widget-course__meta">
						<!-- price -->
						<?php if ( ! empty( $instance['show_price'] ) ) : ?>
							<div class="course-price">
								<div class="lp-widget-course__price">
									<?php echo wp_kses_post( $course->get_course_price_html() ); ?>
								</div>
							</div>
						<?php endif; ?>

						<!-- instructor -->
						<?php if ( ! empty( $instance['show_teacher'] ) ) : ?>
							<div class="lp-widget-course__instructor">
								<span
									class="lp-widget-course__instructor__avatar"><?php echo wp_kses_post( $course->get_instructor()->get_profile_picture() ); ?>
								</span>
								<?php echo wp_kses_post( $course->get_instructor_html() ); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php } ?>

		<?php wp_reset_postdata(); ?>
	</div>

	<div class="lp-widget-popular-courses__footer">
		<?php if ( ! empty( $instance['bottom_link_text'] ) && learn_press_get_page_link( 'courses' ) ) : ?>
			<a class="lp-widget-popular-courses__footer__link"
				href="<?php echo esc_url_raw( learn_press_get_page_link( 'courses' ) ); ?>" rel="nofllow">
				<?php echo wp_kses_post( $instance['bottom_link_text'] ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
