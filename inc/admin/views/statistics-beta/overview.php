<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="lp-admin-statistics-tab-content">
	<div class="btn-group btn-group-filter">
	  <button class="btn-filter-time" type="button" data-filter="today" ><?php _e( 'Today', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="yesterday" ><?php _e( 'Yesterday', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="last7days" ><?php _e( 'Last 7 days', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="last30days" ><?php _e( 'Last 30 days', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="thismonth" ><?php _e( 'This month', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="last12months"><?php _e( 'Last 12 months', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="thisyear" ><?php _e( 'This year', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="custom" ><?php _e( 'Custom', 'learnpress' ) ?></button>
	  <div class="custom-filter-time">
	  	<input type="date" id="ct-filter-1" />
	  	<input type="date" id="ct-filter-2">
	  	<button class="custom-filter-btn button button-primary" type="button"><?php _e( 'Filter', 'learnpress' ) ?></button>
	  </div>
	</div>
	<div class="statistics-content">
		<div class="btn-group group-statistic-overview">
			
		</div>
		<h3 class="statistics-title"><?php _e( 'Net Sales', 'learnpress' ) ?></h3>
		<div id="net-sales-chart">
			<canvas id="net-sales-chart-content">
				
			</canvas>
		</div>
	</div>
</div>