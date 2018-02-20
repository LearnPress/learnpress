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

gmdate( 'Y-m-d H:i:s', ( time() + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) );
$now		= current_time( 'timestamp' );
$now_mysql	= current_time( 'mysql' );
$last_sunday		= strtotime('Last Sunday', $now );

$last_sunday_sql	= gmdate( 'Y-m-d H:i:s', $last_sunday );

$pre_sunday = strtotime('-7 day',$last_sunday);
$pre_sunday_sql	= gmdate( 'Y-m-d H:i:s', $pre_sunday );

learn_press_get_chart_general( null, 'days', 7 );

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
				<span><?php _e( 'From', 'learnpress' ) ?></span>
				<input type="text" placeholder="Y/m/d" name="from" class="date-picker" readonly="readonly">
				<span><?php _e( 'To', 'learnpress' ) ?></span>
				<input type="text" placeholder="Y/m/d" name="to" class="date-picker" readonly="readonly">
				<input type="hidden" name="action" value="learnpress_custom_stats">
				<button class="button button-primary" data-type="course-custom-time" type="submit" disabled="disabled"><?php _e( 'Go', 'learnpress' ); ?></button>
			</form>
		</li>
	</ul>
	<div class="clear"></div>
	<ul class="chart-description">
		<li class="all"><span><?php _e( 'All', 'learnpress' ); ?></span></li>
		<li class="free"><span><?php _e( 'Public', 'learnpress' ); ?></span></li>
		<li class="paid"><span><?php _e( 'Pending', 'learnpress' ); ?></span></li>
		<li class="all"><span><?php _e( 'Paid', 'learnpress' ); ?></span></li>
		<li class="free"><span><?php _e( 'Free', 'learnpress' ); ?></span></li>
	</ul>
	<div id="learn-press-chart" class="learn-press-chart">
	</div>

	<script type="text/javascript">
		var LP_Chart_Config =  <?php learn_press_config_chart();?>;
		jQuery(document).ready(function ($) {
			$('#learn-press-chart').LP_Chart_Line(<?php echo json_encode( learn_press_get_chart_general( null, 'days', 7 ) );?>, LP_Chart_Config);
		});
	</script>
</div>

