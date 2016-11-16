<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$sections       = array(
	'students'    => __( 'Students', 'learnpress' ),
	'instructors' => __( 'Instructors', 'learnpress' ),
);

$section        = $this->section ? $this->section : 'students';
$sections_count = sizeof( $sections );
$count          = 0;
?>
<div id="learn-press-statistic" class="learn-press-statistic-orders">
	<ul class="subsubsub chart-buttons">
		<li>
			<button class="button" data-type="order-last-7-days" disabled="disabled"><?php _e( 'Last 7 Days', 'learnpress' ); ?></button>
		</li>
		<li>
			<button class="button" data-type="order-last-30-days"><?php _e( 'Last 30 Days', 'learnpress' ); ?></button>
		</li>
		<li>
			<button class="button" data-type="order-last-12-months"><?php _e( 'Last 12 Months', 'learnpress' ); ?></button>
		</li>
		<li>
			<button class="button" data-type="order-all"><?php _e( 'All', 'learnpress' ); ?></button>
		</li>
		<li>
			<form id="order-custom-time">
				<span><?php _e( 'From', 'learnpress' ) ?></span>
				<input type="text" placeholder="Y/m/d" name="from" class="date-picker" readonly="readonly">
				<span><?php _e( 'To', 'learnpress' ) ?></span>
				<input type="text" placeholder="Y/m/d" name="to" class="date-picker" readonly="readonly">
				<input type="hidden" name="action" value="learnpress_custom_stats">
				<button class="button button-primary" data-type="order-custom-time" type="submit" disabled="disabled"><?php _e( 'Go', 'learnpress' ); ?></button>
			</form>
		</li>
	</ul>
	<div class="clear"></div>
	<br/>
	<div id="chart-options">
		<?php _e( 'Sale by', 'learnpress' ); ?>
		<select id="report_sales_by">
			<option value="date"><?php _e( 'Date', 'learnpress' ); ?></option>
			<option value="course"><?php _e( 'Course', 'learnpress' ); ?></option>
			<option value="category"><?php _e( 'Course Category', 'learnpress' ); ?></option>
		</select>
		<span id="panel_report_sales_by_course" class="panel_report_option">
			<?php _e( 'Select a course', 'learnpress' );?>
			<input id="report-by-course-id" class="statistics-search-course" />
		</span>
		<span id="panel_report_sales_by_category" class="panel_report_option">
			<?php _e( 'Select a course category', 'learnpress' );?>
			<input id="report-by-course-category-id" class="statistics-search-course-category" />
		</span>
	</div>

	<div class="clear"></div>
	<ul class="chart-description">
		<li class="all"><span><?php _e( 'All', 'learnpress' ); ?></span></li>
		<li class="instructors"><span><?php _e( 'Completed', 'learnpress' ); ?></span></li>
		<li class="students"><span><?php _e( 'Pending', 'learnpress' ); ?></span></li>
	</ul>
	<div id="learn-press-chart" class="learn-press-chart">
	</div>

	<script type="text/javascript">
		var LP_Chart_Config =  <?php learn_press_config_chart();?>;
		jQuery(document).ready(function ($) {
			$('#learn-press-chart').LP_Chart_Line(<?php echo json_encode( learn_press_get_chart_orders( null, 'days', 7 ) );?>, LP_Chart_Config);
		});
	</script>
</div>

