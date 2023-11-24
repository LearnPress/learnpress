<?php
/**
 * Template for displaying orders statistics tab Orders statistics page.
 */
?>

<div class="lp-admin-statistics-tab-content">
	<div class="btn-group btn-group-filter">
		<button class="btn-filter-time active" type="button" data-filter="today" ><?php _e( 'Today', 'learnpress' ); ?></button>
		<!-- <button class="btn-filter-time" type="button" data-filter="yesterday" ><?php _e( 'Yesterday', 'learnpress' ); ?></button> -->
		<button class="btn-filter-time" type="button" data-filter="last7days" ><?php _e( 'Last 7 days', 'learnpress' ); ?></button>
		<button class="btn-filter-time" type="button" data-filter="last30days" ><?php _e( 'Last 30 days', 'learnpress' ); ?></button>
		<!-- <button class="btn-filter-time" type="button" data-filter="thismonth" ><?php _e( 'This month', 'learnpress' ); ?></button> -->
		<button class="btn-filter-time" type="button" data-filter="last12months"><?php _e( 'Last 12 months', 'learnpress' ); ?></button>
		<button class="btn-filter-time" type="button" data-filter="thisyear" ><?php _e( 'This year', 'learnpress' ); ?></button>
		<button class="btn-filter-time" type="button" data-filter="custom" ><?php _e( 'Custom', 'learnpress' ); ?></button>
		<div class="custom-filter-time">
			<input type="date" id="ct-filter-1" />
			<input type="date" id="ct-filter-2">
			<button class="custom-filter-btn button button-primary" type="button"><?php _e( 'Filter', 'learnpress' ); ?></button>
		</div>
	</div>
	<div class="statistics-content">
		<input class="statistics-type" type="hidden" value="orders-statistics">
		<div class="statistics-group group-statistic-order">
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Total Orders', 'learnpress' ); ?></span>
				<span class="statistics-item-count total-order-count">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Completed Orders', 'learnpress' ); ?></span>
				<span class="statistics-item-count completed-order-count">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Proccessing Orders', 'learnpress' ); ?></span>
				<span class="statistics-item-count processing-order-count">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Pending Orders', 'learnpress' ); ?></span>
				<span class="statistics-item-count pending-order-count">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Cancelled Orders', 'learnpress' ); ?></span>
				<span class="statistics-item-count cancelled-order-count">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Fail Orders', 'learnpress' ); ?></span>
				<span class="statistics-item-count failed-order-count">0</span>
			</div>
		</div>
		<h3 class="statistics-title"><?php _e( 'Completed Orders', 'learnpress' ); ?></h3>
		<div id="orders-chart" class="statistics-chart-wrapper">
			<?php lp_skeleton_animation_html( 10, 100 ); ?>
			<canvas id="orders-chart-content" style="display: none;"></canvas>
		</div>
	</div>
</div>
