<?php
/**
 * Template for displaying general statistic in user profile overview.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.1
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $statistic ) || empty( $user ) ) {
	return;
}

?>

<div id="dashboard-statistic">

	<?php do_action( 'learn-press/before-profile-dashboard-general-statistic-row' ); ?>

	<div class="dashboard-statistic__row">

		<?php do_action( 'learn-press/before-profile-dashboard-user-general-statistic' ); ?>

		<div class=" statistic-box" title="<?php esc_html_e( 'Total enrolled courses', 'learnpress' ); ?>">
			<p class="statistic-box__text"><?php esc_html_e( 'Enrolled Courses', 'learnpress' ); ?></p>
			<span class="statistic-box__number"><?php echo esc_html( $statistic['enrolled_courses'] ); ?></span>
		</div>
		<div class="statistic-box"
			title="<?php esc_html_e( 'The total number of courses is being learned', 'learnpress' ); ?>">
			<p class="statistic-box__text"><?php esc_html_e( 'Active Courses', 'learnpress' ); ?></p>
			<span class="statistic-box__number"><?php echo esc_html( $statistic['active_courses'] ); ?></span>
		</div>
		<div class="statistic-box" title="<?php esc_html_e( 'Total courses have finished', 'learnpress' ); ?>">
			<p class="statistic-box__text"><?php esc_html_e( 'Completed Courses', 'learnpress' ); ?></p>
			<span class="statistic-box__number"><?php echo esc_html( $statistic['completed_courses'] ); ?></span>
		</div>

		<?php do_action( 'learn-press/after-profile-dashboard-user-general-statistic' ); ?>

		<?php if ( $user->can_create_course() ) : ?>
			<?php do_action( 'learn-press/before-profile-dashboard-instructor-general-statistic' ); ?>
		<div class="statistic-box" title="<?php esc_html_e( 'Total created courses', 'learnpress' ); ?>">
			<p class="statistic-box__text"><?php esc_html_e( 'Total Courses', 'learnpress' ); ?></p>
			<span class="statistic-box__number"><?php echo esc_html( $statistic['total_courses'] ); ?></span>
		</div>
		<div class="statistic-box" title="<?php esc_html_e( 'Total attended students', 'learnpress' ); ?>">
			<p class="statistic-box__text"><?php esc_html_e( 'Total Students', 'learnpress' ); ?></p>
			<span class="statistic-box__number"><?php echo esc_html( $statistic['total_users'] ); ?></span>
		</div>

			<?php do_action( 'learn-press/after-profile-dashboard-instructor-general-statistic' ); ?>
		<?php endif; ?>
	</div>

	<?php do_action( 'learn-press/profile-dashboard-general-statistic-row' ); ?>

	<?php do_action( 'learn-press/after-profile-dashboard-general-statistic-row' ); ?>
</div>
