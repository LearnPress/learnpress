<?php
/**
 * Template for displaying courses tab in user profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/tabs/courses.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.9
 */

defined( 'ABSPATH' ) || exit();

if ( ! LP_Profile::instance()->current_user_can( 'view-tab-courses' ) ) {
	return;
}

$user = LP_Profile::instance()->get_user();

$data_course_progress = apply_filters(
	'learnpress/template/profile/tabs/courses/course_progress',
	array(
		'userID' => $user->get_id(),
		'status' => 'in-progress',
	)
);

$enrolleds = array(
	''            => esc_html__( 'All', 'learnpress' ),
	'in-progress' => esc_html__( 'In Progress', 'learnpress' ),
	'finished'    => esc_html__( 'Finished', 'learnpress' ),
	'passed'      => esc_html__( 'Passed', 'learnpress' ),
	'failed'      => esc_html__( 'Failed', 'learnpress' ),
);

$createds = array(
	''        => esc_html__( 'All', 'learnpress' ),
	'publish' => esc_html__( 'Publish', 'learnpress' ),
	'pending' => esc_html__( 'Pending', 'learnpress' ),
);

$enrolled_active = ! learn_press_user_maybe_is_a_teacher() ? 'in-progress' : '';
$tab_active      = ! learn_press_user_maybe_is_a_teacher() ? 'enrolled' : 'created';
?>

<div class="learn-press-subtab-content">
	<div class="learn-press-profile-course__statistic"
		 data-ajax="<?php echo htmlentities( wp_json_encode( array( 'userID' => $user->get_id() ) ) ); ?>">
		<?php lp_skeleton_animation_html( 4, 'random', 'height: 30px;border-radius:4px;' ); ?>
	</div>

	<div class="learn-press-profile-course__tab">
		<ul class="learn-press-profile-course__tab__inner">
			<li><a class="<?php echo $tab_active === 'enrolled' ? 'active' : ''; ?>" data-tab="enrolled"><?php esc_html_e( 'Enrolled', 'learnpress' ); ?></a></li>

			<?php if ( learn_press_user_maybe_is_a_teacher() ) : ?>
				<li><a class="<?php echo $tab_active === 'created' ? 'active' : ''; ?>" data-tab="created"><?php esc_html_e( 'Created', 'learnpress' ); ?></a></li>
			<?php endif; ?>
		</ul>

		<div class="learn-press-course-tab-enrolled learn-press-course-tab-filters" data-tab="enrolled" style="<?php echo $tab_active !== 'enrolled' ? 'display: none;' : ''; ?>">
			<ul class="learn-press-filters">
				<?php foreach ( $enrolleds as $key => $enrolled ) : ?>
					<li>
						<a class="<?php echo $key === $enrolled_active ? 'active' : ''; ?>" data-tab="<?php echo $key === '' ? 'all' : $key; ?>">
							<?php echo esc_html( $enrolled ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="learn-press-profile-course__progress">
				<?php foreach ( $enrolleds as $key => $enrolled ) : ?>
					<div class="learn-press-course-tab__filter__content" data-tab="<?php echo $key === '' ? 'all' : $key; ?>" data-ajax="<?php echo htmlentities( wp_json_encode( [ 'userID' => $user->get_id(), 'status' => $key, 'query' => 'purchased', 'layout' => 'list' ] ) ); ?>" style="<?php echo $key !== $enrolled_active ? 'display: none' : ''; ?>"> <?php // phpcs:ignore ?>
						<?php lp_skeleton_animation_html( 4, 'random', 'height: 30px;border-radius:4px;' ); ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<?php if ( learn_press_user_maybe_is_a_teacher() ) : ?>
			<div class="learn-press-course-tab-created learn-press-course-tab-filters" data-tab="created" style="<?php echo $tab_active !== 'created' ? 'display: none;' : ''; ?>">
				<ul class="learn-press-filters">
					<?php foreach ( $createds as $key => $created ) : ?>
						<li>
							<a class="<?php echo $key === '' ? 'active' : ''; ?>" data-tab="<?php echo $key === '' ? 'all' : $key; ?>">
								<?php echo esc_html( $created ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php foreach ( $createds as $key => $created ) : ?>
					<div class="learn-press-course-tab__filter__content" data-tab="<?php echo $key === '' ? 'all' : $key; ?>" data-ajax="<?php echo htmlentities( wp_json_encode( [ 'userID' => $user->get_id(), 'status' => $key, 'query' => 'own' ] ) ); ?>" style="<?php echo $key !== '' ? 'display: none' : ''; ?>"> <?php // phpcs:ignore ?>
						<?php lp_skeleton_animation_html( 4, 'random', 'height: 30px;border-radius:4px;' ); ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
