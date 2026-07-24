<?php
/**
 * Template for displaying the Users statistics dashboard tab.
 *
 * Data is rendered by assets/src/js/admin/statistics/tab-users.js from the
 * `dashboard` key of the user-statistics endpoint.
 *
 * @since 4.4.2
 */

defined( 'ABSPATH' ) || exit();

$kpi_cards = array(
	'users-activated' => __( 'Users activated', 'learnpress' ),
	'students'        => __( 'Students', 'learnpress' ),
	'instructors'     => __( 'Instructors', 'learnpress' ),
	'not-started'     => __( 'Not started', 'learnpress' ),
	'in-progress'     => __( 'In progress', 'learnpress' ),
	'finished'        => __( 'Finished', 'learnpress' ),
);

$funnel_steps = array(
	'registered' => __( 'Registered', 'learnpress' ),
	'enrolled'   => __( 'Enrolled', 'learnpress' ),
	'started'    => __( 'Started learning', 'learnpress' ),
	'completed'  => __( 'Completed', 'learnpress' ),
	'failed'     => __( 'Failed', 'learnpress' ),
);

// Section config filters — add/remove/relabel cards and lists. @since 4.4.2
$kpi_cards    = (array) apply_filters( 'learn-press/statistics/users/kpi-cards', $kpi_cards );
$funnel_steps = (array) apply_filters( 'learn-press/statistics/users/funnel-steps', $funnel_steps );
?>
<div class="lp-admin-statistics-tab-content lp-stats-tab-users">
	<?php
	/**
	 * Fires at the top of the Users statistics tab, inside the tab container.
	 *
	 * @since 4.4.2
	 */
	do_action( 'learn-press/statistics/users/before' );

	learn_press_admin_view( 'statistics/parts/filter-bar' );
	?>

	<div class="lp-stats-dashboard-body">
		<div class="lp-stats-kpi-grid">
			<?php
			foreach ( $kpi_cards as $key => $title ) {
				learn_press_admin_view(
					'statistics/parts/kpi-card',
					array(
						'key'   => $key,
						'title' => $title,
					)
				);
			}
			?>
		</div>

		<div class="lp-stats-overview-grid lp-stats-overview-grid--chart">
			<div class="lp-stats-section statistics-content">
				<div class="lp-stats-section__header">
					<div>
						<h3 class="lp-stats-section__title"><?php esc_html_e( 'Learner registration and progress', 'learnpress' ); ?></h3>
						<p class="lp-stats-section__description"><?php esc_html_e( 'Registration, enrollment, and graduation activity across the learning lifecycle.', 'learnpress' ); ?></p>
					</div>
				</div>
				<div id="user-chart" class="statistics-chart-wrapper">
					<?php lp_skeleton_animation_html( 10, 100 ); ?>
					<canvas id="user-chart-content" style="display: none;"></canvas>
				</div>
			</div>

			<div class="lp-stats-section">
				<div class="lp-stats-section__header">
					<div>
						<h3 class="lp-stats-section__title"><?php esc_html_e( 'Learner funnel', 'learnpress' ); ?></h3>
						<p class="lp-stats-section__description"><?php esc_html_e( 'Registered users to completed courses.', 'learnpress' ); ?></p>
					</div>
				</div>
				<?php lp_skeleton_animation_html( 5, 100 ); ?>
				<div class="lp-stats-funnel">
					<?php foreach ( $funnel_steps as $step => $label ) : ?>
						<div class="lp-stats-funnel__step" data-step="<?php echo esc_attr( $step ); ?>">
							<span class="lp-stats-funnel__label"><?php echo esc_html( $label ); ?></span>
							<span class="lp-stats-funnel__track"><span class="lp-stats-funnel__bar" style="width: 0;"></span></span>
							<span class="lp-stats-funnel__count">&ndash;</span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="lp-stats-overview-grid lp-stats-overview-grid--tables">
			<div class="lp-stats-section">
				<div class="lp-stats-section__header">
					<h3 class="lp-stats-section__title"><?php esc_html_e( 'Top students', 'learnpress' ); ?></h3>
					<button type="button" class="button-link lp-stats-section__action lp-stats-view-all-students"><?php esc_html_e( 'Open report', 'learnpress' ); ?></button>
				</div>
				<?php lp_skeleton_animation_html( 5, 100 ); ?>
				<div class="lp-stats-table-top-students"></div>
			</div>

			<div class="lp-stats-section">
				<div class="lp-stats-section__header">
					<h3 class="lp-stats-section__title"><?php esc_html_e( 'Top courses by students', 'learnpress' ); ?></h3>
					<button type="button" class="button-link lp-stats-section__action lp-stats-view-all-courses-by-students"><?php esc_html_e( 'Open report', 'learnpress' ); ?></button>
				</div>
				<?php lp_skeleton_animation_html( 5, 100 ); ?>
				<div class="lp-stats-table-courses-by-students"></div>
			</div>
		</div>
	</div>

	<?php
	learn_press_admin_view( 'statistics/parts/report-modal' );

	/**
	 * Fires at the bottom of the Users statistics tab, inside the tab container.
	 *
	 * @since 4.4.2
	 */
	do_action( 'learn-press/statistics/users/after' );
	?>
</div>
