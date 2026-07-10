<?php
/**
 * Shared statistics filter bar: time buttons + instructor/category scope + CSV export.
 *
 * Time-button markup is copied unchanged from the legacy tab views — JS depends on
 * .btn-filter-time / data-filter / .custom-filter-time. Scope selects are populated
 * by JS from the filter-options endpoint.
 *
 * @since 4.4.2
 */

defined( 'ABSPATH' ) || exit();
?>
<div class="btn-group btn-group-filter lp-statistics-filter-bar">
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
	<div class="lp-stats-filter-scope">
		<select class="lp-stats-filter-instructor" aria-label="<?php esc_attr_e( 'Filter by instructor', 'learnpress' ); ?>">
			<option value="0"><?php esc_html_e( 'All instructors', 'learnpress' ); ?></option>
		</select>
		<select class="lp-stats-filter-category" aria-label="<?php esc_attr_e( 'Filter by category', 'learnpress' ); ?>">
			<option value="0"><?php esc_html_e( 'All categories', 'learnpress' ); ?></option>
		</select>
		<button type="button" class="lp-stats-export-csv button"><?php esc_html_e( 'Export CSV', 'learnpress' ); ?></button>
	</div>
</div>
