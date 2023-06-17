<?php
/**
 * Template for displaying courses tab in user profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/tabs/courses.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.11
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $user ) || ! isset( $courses_enrolled_tab ) ||
	 ! isset( $courses_enrolled_tab_active ) ||
	 ! isset( $args_query_user_courses_attend ) ||
	 ! isset( $args_query_user_courses_statistic ) ) {
	return;
}
?>

<div class="learn-press-subtab-content">
	<div class="learn-press-profile-course__statistic">
		<?php lp_skeleton_animation_html( 4, 'random', 'height: 30px;border-radius:4px;' ); ?>
		<input type="hidden" name="args_query_user_courses_statistic"
			   value="<?php echo sanitize_text_field( htmlentities( wp_json_encode( $args_query_user_courses_statistic ) ) ); ?>">
	</div>

	<div class="learn-press-profile-course__tab">
		<div class="learn-press-course-tab-enrolled learn-press-course-tab-filters" data-tab="enrolled">
			<ul class="learn-press-filters">
				<?php foreach ( $courses_enrolled_tab as $key => $enrolled ) : ?>
					<li>
						<a class="<?php echo esc_attr( $key === $courses_enrolled_tab_active ? 'active' : '' ); ?>"
						   data-tab="<?php echo esc_attr( $key === '' ? 'all' : $key ); ?>">
							<?php echo esc_html( $enrolled ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="learn-press-profile-course__progress">
				<?php foreach ( $courses_enrolled_tab as $key => $enrolled ) : ?>
					<div class="learn-press-course-tab__filter__content"
						 data-tab="<?php echo esc_attr( $key === '' ? 'all' : $key ); ?>"
						 style="<?php echo esc_attr( $key !== $courses_enrolled_tab_active ? 'display: none' : '' ); ?>">
						<?php lp_skeleton_animation_html( 4, 'random', 'height: 30px;border-radius:4px;' ); ?>
					</div>
				<?php endforeach; ?>
				<input class="lp_profile_tab_input_param" type="hidden" name="args_query_user_courses_attend"
					   value="<?php echo sanitize_text_field( htmlentities( wp_json_encode( $args_query_user_courses_attend ) ) ); ?>">
			</div>
		</div>
	</div>
</div>
