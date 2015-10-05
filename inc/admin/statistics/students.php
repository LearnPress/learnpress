<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div>
	<h3><?php _e('Number of student enrolled your courses', 'learn_press') ?></h3>
	<ul class="subsubsub">
		<li>
			<button class="button" onclick="drawStudentsChart(last_seven_days,config); return false; " href="#"><?php _e( 'Last 7 Days', 'learn_press' ); ?></button>
		</li>
		<li>
			<button class="button" onclick="drawStudentsChart(last_thirty_days,config); return false; " href="#"><?php _e( 'Last 30 Days', 'learn_press' ); ?></button>
		</li>
		<li>
			<button class="button" onclick="drawStudentsChart(last_twelve_months,config); return false;" href="#"><?php _e( 'Last 12 Months', 'learn_press' ); ?></button>
		</li>
		<li>
			<form id="lpr-custom-time">
				<span><?php _e( 'From', 'learn_press' ) ?></span>
				<input type="text" placeholder="mm/dd/yyyy" name="from" class="lpr-time">
				<span><?php _e( 'To', 'learn_press' ) ?></span>
				<input type="text" placeholder="mm/dd/yyyy" name="to" class="lpr-time">
				<input type="hidden" name="action" value="learnpress_custom_stats">
				<button class="button button-primary" type="submit"><?php _e( 'Go', 'learn_press' ); ?></button>
			</form>
		</li>
	</ul>
</div>
<div class="lpr-chart-wrapper">
	<canvas id="lpr-chart-students"></canvas>
</div>

<script>
	var last_seven_days = <?php learn_press_process_chart( learn_press_get_chart_students( null, 'days', 7 )); ?>;
	var last_thirty_days = <?php learn_press_process_chart( learn_press_get_chart_students( null, 'days', 30 )); ?>;
	var last_twelve_months = <?php learn_press_process_chart( learn_press_get_chart_students( null, 'months', 12 )); ?>;
	var config =  <?php learn_press_config_chart();?>;
</script>
