<?php
/**
 * Template for displaying the Instructors statistics dashboard tab.
 *
 * Data is rendered by assets/src/js/admin/statistics/tab-instructors.js from
 * the `dashboard` key of the instructor-statistics endpoint.
 *
 * @since 4.4.2
 */

defined( 'ABSPATH' ) || exit();

$kpi_cards = array(
	'active-instructors' => __( 'Active instructors', 'learnpress' ),
	'instructor-revenue' => __( 'Instructor revenue', 'learnpress' ),
	'courses-managed'    => __( 'Courses managed', 'learnpress' ),
	'students-reached'   => __( 'Students reached', 'learnpress' ),
	'avg-completion'     => __( 'Avg. completion', 'learnpress' ),
	'needs-review'       => __( 'Needs review', 'learnpress' ),
);

$operations = array(
	'top_revenue'        => __( 'Top revenue', 'learnpress' ),
	'top_completion'     => __( 'Best completion', 'learnpress' ),
	'most_pending'       => __( 'Most pending review', 'learnpress' ),
	'no_new_enrollments' => __( 'No new enrollments', 'learnpress' ),
	'review_queue_count' => __( 'Review queue', 'learnpress' ),
);

// Section config filters — add/remove/relabel cards and lists. @since 4.4.2
$kpi_cards  = (array) apply_filters( 'learn-press/statistics/instructors/kpi-cards', $kpi_cards );
$operations = (array) apply_filters( 'learn-press/statistics/instructors/operations', $operations );
?>
<div class="lp-admin-statistics-tab-content lp-stats-tab-instructors">
	<?php
	/**
	 * Fires at the top of the Instructors statistics tab, inside the tab container.
	 *
	 * @since 4.4.2
	 */
	do_action( 'learn-press/statistics/instructors/before' );

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
						<h3 class="lp-stats-section__title"><?php esc_html_e( 'Instructor revenue and enrollment', 'learnpress' ); ?></h3>
						<p class="lp-stats-section__description"><?php esc_html_e( 'Scoped sales and enrollment trend for instructor performance review.', 'learnpress' ); ?></p>
					</div>
				</div>
				<div id="instructor-chart" class="statistics-chart-wrapper">
					<?php lp_skeleton_animation_html( 10, 100 ); ?>
					<canvas id="instructor-chart-content" style="display: none;"></canvas>
				</div>
			</div>

			<div class="lp-stats-section">
				<div class="lp-stats-section__header">
					<h3 class="lp-stats-section__title"><?php esc_html_e( 'Instructor operations', 'learnpress' ); ?></h3>
				</div>
				<?php lp_skeleton_animation_html( 5, 100 ); ?>
				<ul class="lp-stats-operations">
					<?php foreach ( $operations as $op => $label ) : ?>
						<li class="lp-stats-operations__row" data-op="<?php echo esc_attr( $op ); ?>">
							<span class="lp-stats-operations__label"><?php echo esc_html( $label ); ?></span>
							<span class="lp-stats-operations__value">&ndash;</span>
							<span class="lp-stats-operations__name"></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>

		<div class="lp-stats-overview-grid lp-stats-overview-grid--tables">
			<div class="lp-stats-section">
				<div class="lp-stats-section__header">
					<h3 class="lp-stats-section__title"><?php esc_html_e( 'Instructor performance', 'learnpress' ); ?></h3>
					<button type="button" class="button-link lp-stats-section__action lp-stats-view-all-instructors"><?php esc_html_e( 'Open report', 'learnpress' ); ?></button>
				</div>
				<?php lp_skeleton_animation_html( 5, 100 ); ?>
				<div class="lp-stats-table-instructor-performance"></div>
			</div>

			<div class="lp-stats-section">
				<div class="lp-stats-section__header">
					<h3 class="lp-stats-section__title"><?php esc_html_e( 'Instructor course watchlist', 'learnpress' ); ?></h3>
				</div>
				<?php lp_skeleton_animation_html( 5, 100 ); ?>
				<div class="lp-stats-table-instructor-watchlist"></div>
			</div>
		</div>
	</div>

	<?php
	learn_press_admin_view( 'statistics/parts/report-modal' );

	/**
	 * Fires at the bottom of the Instructors statistics tab, inside the tab container.
	 *
	 * @since 4.4.2
	 */
	do_action( 'learn-press/statistics/instructors/after' );
	?>
</div>
