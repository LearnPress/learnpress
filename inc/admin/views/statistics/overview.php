<?php
/**
 * Template for displaying the Overview statistics dashboard tab.
 *
 * Data is rendered by assets/src/js/admin/statistics/tab-overview.js from the
 * `dashboard` key of the overviews-statistics endpoint.
 *
 * @since 4.4.2
 */

defined( 'ABSPATH' ) || exit();

$order_health_boxes = array(
	'completed'        => __( 'Completed', 'learnpress' ),
	'processing'       => __( 'Processing', 'learnpress' ),
	'pending'          => __( 'Pending', 'learnpress' ),
	'cancelled_failed' => __( 'Cancelled / failed', 'learnpress' ),
);

$health_checks = array(
	'no_enrollment'  => array(
		'label' => __( 'Published courses without any enrollment', 'learnpress' ),
		'url'   => admin_url( 'edit.php?post_type=lp_course&post_status=publish' ),
	),
	'no_content'     => array(
		'label' => __( 'Published courses with an empty curriculum', 'learnpress' ),
		'url'   => admin_url( 'edit.php?post_type=lp_course&post_status=publish' ),
	),
	'pending_review' => array(
		'label' => __( 'Courses waiting for review', 'learnpress' ),
		'url'   => admin_url( 'edit.php?post_type=lp_course&post_status=pending' ),
	),
	'quiz_low_pass'  => array(
		'label' => __( 'Quizzes with a low pass rate', 'learnpress' ),
		'url'   => admin_url( 'edit.php?post_type=lp_quiz' ),
	),
	'low_completion' => array(
		'label' => __( 'Courses below the completion target', 'learnpress' ),
		'url'   => admin_url( 'admin.php?page=learn-press-statistics&tab=courses' ),
	),
);

$funnel_steps = array(
	'registered' => __( 'Registered', 'learnpress' ),
	'enrolled'   => __( 'Enrolled', 'learnpress' ),
	'started'    => __( 'Started learning', 'learnpress' ),
	'completed'  => __( 'Completed', 'learnpress' ),
);

$kpi_cards = array(
	'net-sales'        => __( 'Net sales', 'learnpress' ),
	'completed-orders' => __( 'Completed orders', 'learnpress' ),
	'enrollments'      => __( 'Enrollments', 'learnpress' ),
	'completion-rate'  => __( 'Completion rate', 'learnpress' ),
	'active-learners'  => __( 'Active learners', 'learnpress' ),
	'failed-orders'    => __( 'Failed orders', 'learnpress' ),
);

// Section config filters — add/remove/relabel cards and lists. @since 4.4.2
$kpi_cards          = (array) apply_filters( 'learn-press/statistics/overview/kpi-cards', $kpi_cards );
$health_checks      = (array) apply_filters( 'learn-press/statistics/overview/health-checks', $health_checks );
$funnel_steps       = (array) apply_filters( 'learn-press/statistics/overview/funnel-steps', $funnel_steps );
$order_health_boxes = (array) apply_filters( 'learn-press/statistics/overview/order-health-boxes', $order_health_boxes );
?>
<div class="lp-admin-statistics-tab-content lp-stats-tab-overview">
	<?php
	/**
	 * Fires at the top of the Overview statistics tab, inside the tab container.
	 *
	 * @since 4.4.2
	 */
	do_action( 'learn-press/statistics/overview/before' );

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
						<h3 class="lp-stats-section__title"><?php esc_html_e( 'Revenue and enrollments', 'learnpress' ); ?></h3>
						<p class="lp-stats-section__description"><?php esc_html_e( 'Completed order revenue with enrollment volume for the selected range.', 'learnpress' ); ?></p>
					</div>
				</div>
				<div class="statistics-chart-wrapper">
					<?php lp_skeleton_animation_html( 10, 100 ); ?>
					<canvas id="net-sales-chart-content" style="display: none;"></canvas>
				</div>
			</div>

			<div class="lp-stats-section">
				<div class="lp-stats-section__header">
					<div>
						<h3 class="lp-stats-section__title"><?php esc_html_e( 'Learner funnel', 'learnpress' ); ?></h3>
						<p class="lp-stats-section__description"><?php esc_html_e( 'Registered users to completed courses.', 'learnpress' ); ?></p>
					</div>
				</div>
				<?php lp_skeleton_animation_html( 4, 100 ); ?>
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
					<h3 class="lp-stats-section__title"><?php esc_html_e( 'Top course performance', 'learnpress' ); ?></h3>
					<button type="button" class="button-link lp-stats-section__action lp-stats-view-all-courses"><?php esc_html_e( 'View all courses', 'learnpress' ); ?></button>
				</div>
				<?php lp_skeleton_animation_html( 5, 100 ); ?>
				<div class="lp-stats-table-top-courses"></div>
			</div>

			<div class="lp-stats-section">
				<div class="lp-stats-section__header">
					<h3 class="lp-stats-section__title"><?php esc_html_e( 'Instructor performance', 'learnpress' ); ?></h3>
					<a class="lp-stats-section__action" href="<?php echo esc_url( admin_url( 'users.php?role=lp_teacher' ) ); ?>"><?php esc_html_e( 'Manage instructors', 'learnpress' ); ?></a>
				</div>
				<?php lp_skeleton_animation_html( 5, 100 ); ?>
				<div class="lp-stats-table-instructors"></div>
			</div>
		</div>

		<div class="lp-stats-overview-grid lp-stats-overview-grid--health">
			<div class="lp-stats-section">
				<div class="lp-stats-section__header">
					<h3 class="lp-stats-section__title"><?php esc_html_e( 'Order health', 'learnpress' ); ?></h3>
				</div>
				<div class="lp-stats-health-grid lp-stats-order-health">
					<?php foreach ( $order_health_boxes as $status => $label ) : ?>
						<a class="lp-stats-health-box" data-status="<?php echo esc_attr( $status ); ?>"
							href="<?php echo esc_url( admin_url( 'admin.php?page=learn-press-statistics&tab=orders&order_status=' . $status ) ); ?>">
							<span class="lp-stats-health-box__count">&ndash;</span>
							<span class="lp-stats-health-box__label"><?php echo esc_html( $label ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="lp-stats-section">
				<div class="lp-stats-section__header">
					<h3 class="lp-stats-section__title"><?php esc_html_e( 'Course health checks', 'learnpress' ); ?></h3>
				</div>
				<ul class="lp-stats-health-checks">
					<?php foreach ( $health_checks as $check => $item ) : ?>
						<li>
							<a href="<?php echo esc_url( $item['url'] ); ?>">
								<span class="lp-stats-health-check__count" data-check="<?php echo esc_attr( $check ); ?>">&ndash;</span>
								<?php echo esc_html( $item['label'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>

	<?php
	learn_press_admin_view( 'statistics/parts/report-modal' );

	/**
	 * Fires at the bottom of the Overview statistics tab, inside the tab container.
	 *
	 * @since 4.4.2
	 */
	do_action( 'learn-press/statistics/overview/after' );
	?>
</div>
