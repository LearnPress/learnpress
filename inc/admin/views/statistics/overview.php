<?php
/**
 * Template for displaying orders statistics tab Overview statistics page.
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
		<div class="statistics-group group-statistic-overview">
			<input class="statistics-type" type="hidden" value="overview-statistics">
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Total Sales', 'learnpress' ); ?></span>
				<span class="statistics-item-count total-sales">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Total Orders', 'learnpress' ); ?></span>
				<span class="statistics-item-count total-orders">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Total Courses', 'learnpress' ); ?></span>
				<span class="statistics-item-count total-courses">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Total Instructors', 'learnpress' ); ?></span>
				<span class="statistics-item-count total-instructors">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Total Students', 'learnpress' ); ?></span>
				<span class="statistics-item-count total-students">0</span>
			</div>
		</div>
		<h3 class="statistics-title"><?php _e( 'Net Sales', 'learnpress' ); ?></h3>
		<div id="net-sales-chart" class="statistics-chart-wrapper">
			<?php lp_skeleton_animation_html( 10, 100 ); ?>
			<canvas id="net-sales-chart-content" style="display: none;"></canvas>
		</div>
		<div class="sold-course-analytics">
			<div class="col-50">
				<div class="col-50-contents">
					<h5 class="top-course-analytics-title"><?php _e( 'Top Courses Sold', 'learnpress' ); ?></h5>
					<?php lp_skeleton_animation_html( 10, 100 ); ?>
					<ul class="top-course-sold"></ul>
				</div>
			</div>
			<div class="col-50">
				<div class="col-50-contents">
					<h5 class="top-course-analytics-title"><?php _e( 'Top Categories Sold', 'learnpress' ); ?></h5>
					<?php lp_skeleton_animation_html( 10, 100 ); ?>
					<ul class="top-category-sold"></ul>
				</div>
			</div>
		</div>
	</div>
</div>
