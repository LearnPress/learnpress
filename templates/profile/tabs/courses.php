<?php
/**
 * Template for displaying courses tab in user profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/tabs/courses.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.12
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $user ) || ! isset( $courses_created_tab ) || ! isset( $args_query_user_courses_created ) ||
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
		<div class="learn-press-course-tab-created learn-press-course-tab-filters" data-tab="created">
			<ul class="learn-press-filters">
				<?php foreach ( $courses_created_tab as $key => $created ) : ?>
					<li>
						<a class="<?php echo esc_attr( $key === '' ? 'active' : '' ); ?>"
							data-tab="<?php echo esc_attr( $key === '' ? 'all' : $key ); ?>">
							<?php echo esc_html( $created ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="learn-press-profile-course__progress">
				<?php foreach ( $courses_created_tab as $key => $created ) : ?>
					<div class="learn-press-course-tab__filter__content"
						data-tab="<?php echo esc_attr( $key === '' ? 'all' : $key ); ?>"
						style="<?php echo esc_attr( $key !== '' ? 'display: none' : '' ); ?>">
						<?php lp_skeleton_animation_html( 4, 'random', 'height: 30px;border-radius:4px;' ); ?>
					</div>
				<?php endforeach; ?>
				<input class="lp_profile_tab_input_param" type="hidden" name="args_query_user_courses_created"
					value="<?php echo sanitize_text_field( htmlentities( wp_json_encode( $args_query_user_courses_created ) ) ); ?>">
			</div>
		</div>
	</div>
</div>
