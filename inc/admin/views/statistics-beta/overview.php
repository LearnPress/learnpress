<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<style type="text/css">
	.lp-admin-tab-content.card{
		max-width: 100%;
	}
	.btn-group button.btn-filter-time {
	  border: 1px solid #284BCA;
	  color: #000; /* White text */
	  margin-right:10px;
	  padding: 10px 24px; /* Some padding */
	  cursor: pointer; /* Pointer/hand icon */
	  float: left; /* Float the buttons side by side */
	  background:linear-gradient(0deg, #FFFFFF, #FFFFFF);
	}

	.btn-group:after {
	  content: "";
	  clear: both;
	  display: table;
	  height: 20px;
	}
	.btn-group button:not(:last-child) {

	}
	.statistics-content{
		border: 1px solid #D9D9D9;
		padding: 22px;
	}
	.statistics-title{
		color: #000;
		text-align: center;
		font-family: Inter;
		font-size: 24px;
		font-style: normal;
		font-weight: 700;
		line-height: normal;
		text-transform: capitalize;
	}
	#net-sales-chart{
		min-height: 630px;
		width: 100%;
	}
</style>
<div class="lp-admin-tab-content card">
	<div class="btn-group btn-group-filter">
	  <button class="btn-filter-time" type="button" data-filter="today" ><?php _e( 'Today', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="yesterday" ><?php _e( 'Yesterday', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="lastweek" ><?php _e( 'Last week', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="lastmonth" ><?php _e( 'Last month', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="custom" ><?php _e( 'Custom', 'learnpress' ) ?></button>
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