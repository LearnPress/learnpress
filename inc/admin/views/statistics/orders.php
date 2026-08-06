<?php
/**
 * Template for displaying the Orders statistics dashboard tab.
 *
 * Data is rendered by assets/src/js/admin/statistics/tab-orders.js from the
 * `dashboard` key of the order-statistics endpoint.
 *
 * @since 4.4.2
 */

defined( 'ABSPATH' ) || exit();

$kpi_cards = array(
	'net-sales'         => __( 'Net sales', 'learnpress' ),
	'completed-orders'  => __( 'Completed orders', 'learnpress' ),
	'processing'        => __( 'Processing orders', 'learnpress' ),
	'pending'           => __( 'Pending orders', 'learnpress' ),
	'cancelled-failed'  => __( 'Cancelled / failed', 'learnpress' ),
	'paid-courses-sold' => __( 'Paid courses sold', 'learnpress' ),
);

$payment_health = array(
	'completed'  => __( 'Completed', 'learnpress' ),
	'processing' => __( 'Processing', 'learnpress' ),
	'pending'    => __( 'Pending', 'learnpress' ),
	'cancelled'  => __( 'Cancelled', 'learnpress' ),
	'failed'     => __( 'Failed', 'learnpress' ),
);

// Section config filters — add/remove/relabel cards and lists. @since 4.4.2
$kpi_cards      = (array) apply_filters( 'learn-press/statistics/orders/kpi-cards', $kpi_cards );
$payment_health = (array) apply_filters( 'learn-press/statistics/orders/payment-health', $payment_health );
?>
<div class="lp-admin-statistics-tab-content lp-stats-tab-orders">
	<?php
	/**
	 * Fires at the top of the Orders statistics tab, inside the tab container.
	 *
	 * @since 4.4.2
	 */
	do_action( 'learn-press/statistics/orders/before' );

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
						<h3 class="lp-stats-section__title"><?php esc_html_e( 'Order volume and revenue', 'learnpress' ); ?></h3>
						<p class="lp-stats-section__description"><?php esc_html_e( 'Completed order revenue, order count, and status health for the selected period.', 'learnpress' ); ?></p>
					</div>
				</div>
				<div id="orders-chart" class="statistics-chart-wrapper">
					<?php lp_skeleton_animation_html( 10, 100 ); ?>
					<canvas id="orders-chart-content" style="display: none;"></canvas>
				</div>
			</div>

			<div class="lp-stats-section">
				<div class="lp-stats-section__header">
					<h3 class="lp-stats-section__title"><?php esc_html_e( 'Payment health', 'learnpress' ); ?></h3>
				</div>
				<ul class="lp-stats-payment-health">
					<?php foreach ( $payment_health as $status => $label ) : ?>
						<li class="lp-stats-payment-health__row" data-status="<?php echo esc_attr( $status ); ?>">
							<span class="lp-stats-payment-health__label"><?php echo esc_html( $label ); ?></span>
							<span class="lp-stats-payment-health__count">&ndash;</span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>

		<div class="lp-stats-overview-grid lp-stats-overview-grid--tables">
			<div class="lp-stats-section">
				<div class="lp-stats-section__header">
					<h3 class="lp-stats-section__title"><?php esc_html_e( 'Top sold courses', 'learnpress' ); ?></h3>
					<button type="button" class="button-link lp-stats-section__action lp-stats-view-all-top-sold"><?php esc_html_e( 'Open report', 'learnpress' ); ?></button>
				</div>
				<?php lp_skeleton_animation_html( 5, 100 ); ?>
				<div class="lp-stats-table-top-sold-courses"></div>
			</div>

			<div class="lp-stats-section">
				<div class="lp-stats-section__header">
					<h3 class="lp-stats-section__title"><?php esc_html_e( 'Recent order exceptions', 'learnpress' ); ?></h3>
					<button type="button" class="button-link lp-stats-section__action lp-stats-view-all-exceptions"><?php esc_html_e( 'Open report', 'learnpress' ); ?></button>
				</div>
				<?php lp_skeleton_animation_html( 5, 100 ); ?>
				<div class="lp-stats-table-order-exceptions"></div>
			</div>
		</div>
	</div>

	<?php
	learn_press_admin_view( 'statistics/parts/report-modal' );

	/**
	 * Fires at the bottom of the Orders statistics tab, inside the tab container.
	 *
	 * @since 4.4.2
	 */
	do_action( 'learn-press/statistics/orders/after' );
	?>
</div>
