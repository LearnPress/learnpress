<?php
/**
 * Template for displaying orders statistics tab Users statistics page.
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
		<input class="statistics-type" type="hidden" value="users-statistics">
		<div class="statistics-group">
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Users activated', 'learnpress' ); ?></span>
				<span class="statistics-item-count statistics-user-actived">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Instructors', 'learnpress' ); ?></span>
				<span class="statistics-item-count statistics-instructors">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Students', 'learnpress' ); ?></span>
				<span class="statistics-item-count statistics-students">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Inprogress', 'learnpress' ); ?></span>
				<span class="statistics-item-count statistics-graduration in-progress">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Finished', 'learnpress' ); ?></span>
				<span class="statistics-item-count statistics-graduration finished">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Not Started', 'learnpress' ); ?></span>
				<span class="statistics-item-count statistics-not-started">0</span>
			</div>
		</div>
		<h3 class="statistics-title"><?php _e( 'Registed Users', 'learnpress' ); ?></h3>
		<div id="user-chart" class="statistics-chart-wrapper">
			<?php lp_skeleton_animation_html( 10, 100 ); ?>
			<canvas id="user-chart-content" style="display: none;"></canvas>
		</div>
		<div class="sold-course-analytics">
			<div class="col-50">
				<div class="col-50-contents">
					<h4 class="top-course-analytics-title"><?php _e( 'Top Courses By Students', 'learnpress' ); ?></h4>
					<?php lp_skeleton_animation_html( 10, 100 ); ?>
					<ul class="top-course-by-student"></ul>
				</div>
			</div>
			<div class="col-50">
				<div class="col-50-contents">
					<h4 class="top-course-analytics-title"><?php _e( 'Top Instructors By Students Enrolled Times', 'learnpress' ); ?></h4>
					<?php lp_skeleton_animation_html( 10, 100 ); ?>
					<ul class="top-intructor-by-student"></ul>
				</div>
			</div>
		</div>
	</div>
</div>
