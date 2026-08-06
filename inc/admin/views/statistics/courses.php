<?php
/**
 * Template for displaying the Courses statistics dashboard tab.
 *
 * Data is rendered by assets/src/js/admin/statistics/tab-courses.js from the
 * `dashboard` key of the course-statistics endpoint.
 *
 * @since 4.4.2
 */

defined( 'ABSPATH' ) || exit();

$kpi_cards = array(
	'published'                  => __( 'Published courses', 'learnpress' ),
	'pending-review'             => __( 'Pending review', 'learnpress' ),
	'future'                     => __( 'Future courses', 'learnpress' ),
	'enrollments'                => __( 'Enrollments', 'learnpress' ),
	'avg-completion'             => __( 'Avg. completion', 'learnpress' ),
	'courses-without-enrollment' => __( 'Courses without enrollment', 'learnpress' ),
);

$health_checks = array(
	'no_curriculum'  => array(
		'label' => __( 'Published courses with an empty curriculum', 'learnpress' ),
		'url'   => admin_url( 'edit.php?post_type=lp_course&post_status=publish' ),
	),
	'no_students'    => array(
		'label' => __( 'Published courses without any enrollment', 'learnpress' ),
		'url'   => admin_url( 'edit.php?post_type=lp_course&post_status=publish' ),
	),
	'low_completion' => array(
		'label' => __( 'Courses below the completion target', 'learnpress' ),
		'url'   => admin_url( 'admin.php?page=learn-press-statistics&tab=courses' ),
	),
	'low_quiz_pass'  => array(
		'label' => __( 'Quizzes with a low pass rate', 'learnpress' ),
		'url'   => admin_url( 'edit.php?post_type=lp_quiz' ),
	),
	'pending_review' => array(
		'label' => __( 'Courses waiting for review', 'learnpress' ),
		'url'   => admin_url( 'edit.php?post_type=lp_course&post_status=pending' ),
	),
);

// Section config filters — add/remove/relabel cards and lists. @since 4.4.2
$kpi_cards     = (array) apply_filters( 'learn-press/statistics/courses/kpi-cards', $kpi_cards );
$health_checks = (array) apply_filters( 'learn-press/statistics/courses/health-checks', $health_checks );
?>
<div class="lp-admin-statistics-tab-content lp-stats-tab-courses">
	<?php
	/**
	 * Fires at the top of the Courses statistics tab, inside the tab container.
	 *
	 * @since 4.4.2
	 */
	do_action( 'learn-press/statistics/courses/before' );

	learn_press_admin_view( 'statistics/parts/filter-bar' );
	?>

	<div class="lp-stats-dashboard-body">
		<div class="lp-stats-kpi-grid">
			<?php foreach ( $kpi_cards as $key => $title ) : ?>
				<?php if ( 'avg-completion' === $key ) : ?>
					<div class="lp-kpi-card lp-kpi-<?php echo esc_attr( $key ); ?>">
						<span class="lp-kpi-title"><?php echo esc_html( $title ); ?></span>
						<span class="lp-kpi-value">&ndash;</span>
						<span class="lp-kpi-delta"></span>
						<span class="lp-kpi-subline"></span>
						<span class="lp-kpi-progress"><span class="lp-kpi-progress__bar" style="width: 0;"></span></span>
					</div>
				<?php else : ?>
					<?php
					learn_press_admin_view(
						'statistics/parts/kpi-card',
						array(
							'key'   => $key,
							'title' => $title,
						)
					);
					?>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>

		<div class="lp-stats-overview-grid lp-stats-overview-grid--chart">
			<div class="lp-stats-section statistics-content">
				<div class="lp-stats-section__header">
					<div>
						<h3 class="lp-stats-section__title"><?php esc_html_e( 'Course publishing and enrollment', 'learnpress' ); ?></h3>
						<p class="lp-stats-section__description"><?php esc_html_e( 'Course creation, enrollment, and completion trend for content operations.', 'learnpress' ); ?></p>
					</div>
				</div>
				<div id="course-chart" class="statistics-chart-wrapper">
					<?php lp_skeleton_animation_html( 10, 100 ); ?>
					<canvas id="course-chart-content" style="display: none;"></canvas>
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

		<div class="lp-stats-overview-grid lp-stats-overview-grid--tables">
			<div class="lp-stats-section">
				<div class="lp-stats-section__header">
					<h3 class="lp-stats-section__title"><?php esc_html_e( 'Course performance', 'learnpress' ); ?></h3>
					<button type="button" class="button-link lp-stats-section__action lp-stats-view-all-performance"><?php esc_html_e( 'Open report', 'learnpress' ); ?></button>
				</div>
				<?php lp_skeleton_animation_html( 5, 100 ); ?>
				<div class="lp-stats-table-course-performance"></div>
			</div>

			<div class="lp-stats-section">
				<div class="lp-stats-section__header">
					<h3 class="lp-stats-section__title"><?php esc_html_e( 'Content inventory', 'learnpress' ); ?></h3>
				</div>
				<?php lp_skeleton_animation_html( 4, 100 ); ?>
				<div class="lp-stats-table-content-inventory"></div>
			</div>
		</div>
	</div>

	<?php
	learn_press_admin_view( 'statistics/parts/report-modal' );

	/**
	 * Fires at the bottom of the Courses statistics tab, inside the tab container.
	 *
	 * @since 4.4.2
	 */
	do_action( 'learn-press/statistics/courses/after' );
	?>
</div>
