<?php
/**
 * Template for displaying orders statistics tab Courses statistics page.
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
		<input class="statistics-type" type="hidden" value="courses-statistics">
		<div class="statistics-group">
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Total Courses', 'learnpress' ); ?></span>
				<span class="statistics-item-count statistics-courses total">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Published Courses', 'learnpress' ); ?></span>
				<span class="statistics-item-count statistics-courses published">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Pending Courses', 'learnpress' ); ?></span>
				<span class="statistics-item-count statistics-courses pending">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Future Courses', 'learnpress' ); ?></span>
				<span class="statistics-item-count statistics-courses future">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Lessons', 'learnpress' ); ?></span>
				<span class="statistics-item-count statistics-items lessons">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Quizes', 'learnpress' ); ?></span>
				<span class="statistics-item-count statistics-items quizes">0</span>
			</div>
			<?php if ( class_exists( 'LP_Assignment' ) ) : ?>
				<div class="statistics-item">
					<span class="statistics-item-title"><?php _e( 'Assginments', 'learnpress' ); ?></span>
					<span class="statistics-item-count statistics-items assignment">0</span>
				</div>
			<?php endif; ?>
		</div>
		<h3 class="statistics-title"><?php _e( 'Published Courses', 'learnpress' ); ?></h3>
		<div id="course-chart" class="statistics-chart-wrapper">
			<?php lp_skeleton_animation_html( 10, 100 ); ?>
			<canvas id="course-chart-content" style="display: none;"></canvas>
		</div>
	</div>
</div>
