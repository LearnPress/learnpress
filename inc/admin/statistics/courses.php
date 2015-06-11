<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div>
	<h3><?php _e('Relation of your courses', 'learn_press') ?></h3>
</div>
<div class="lpr-chart-wrapper">
	<canvas id="lpr-chart-courses"></canvas>
</div>

<script>
	var data = <?php learn_press_process_chart( learn_press_get_chart_courses()); ?>;
	var config =  <?php learn_press_config_chart();?>;
</script>

