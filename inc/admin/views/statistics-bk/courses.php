<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sections       = array(
	'students'    => __( 'Students', 'learnpress' ),
	'instructors' => __( 'Instructors', 'learnpress' ),
);
$section        = 'students';// $this->section ? $this->section : 'students';
$sections_count = sizeof( $sections );
$count          = 0;
?>
<div id="learn-press-statistic" class="learn-press-statistic-courses">
	<ul class="subsubsub chart-buttons">
		<li>
			<button class="button" data-type="course-last-7-days" disabled="disabled"><?php _e( 'Last 7 Days', 'learnpress' ); ?></button>
		</li>
		<li>
			<button class="button" data-type="course-last-30-days"><?php _e( 'Last 30 Days', 'learnpress' ); ?></button>
		</li>
		<li>
			<button class="button" data-type="course-last-12-months"><?php _e( 'Last 12 Months', 'learnpress' ); ?></button>
		</li>
		<li>
			<button class="button" data-type="course-all"><?php _e( 'All', 'learnpress' ); ?></button>
		</li>
		<li>
			<form id="course-custom-time">
				<span><?php _e( 'From', 'learnpress' ); ?></span>
				<input type="text" placeholder="Y/m/d" name="from" class="date-picker" readonly="readonly">
				<span><?php _e( 'To', 'learnpress' ); ?></span>
				<input type="text" placeholder="Y/m/d" name="to" class="date-picker" readonly="readonly">
				<input type="hidden" name="action" value="learnpress_custom_stats">
				<button class="button button-primary" data-type="course-custom-time" type="submit" disabled="disabled"><?php _e( 'Go', 'learnpress' ); ?></button>
			</form>
		</li>
	</ul>
	<div class="clear"></div>
	<div id="learn-press-chart" class="learn-press-chart">
	</div>

	<script type="text/javascript">
		var LP_Chart_Config =  <?php learn_press_config_chart(); ?>;
		jQuery(document).ready(function ($) {
			$('#learn-press-chart').LP_Chart_Line(<?php echo json_encode( learn_press_get_chart_courses( null, 'days', 7 ) ); ?>, LP_Chart_Config);
		});
	</script>
</div>

