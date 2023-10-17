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
	.statistics-group .statistics-item {
	  	border: 1px solid #000;
	      color: #000;
	      margin-right: 10px;
	      padding: 15px 30px;
/*	      cursor: pointer;*/
	      float: left;
	      display: flex;
	      flex-direction: column;
	      text-align: center;
	}

	.btn-group:after, .statistics-group {
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
	/*#orders-chart{
		min-height: 630px;
		width: 100%;
	}*/
</style>
<div class="lp-admin-tab-content card">
	<div class="btn-group btn-group-filter">
	  <button class="btn-filter-time" type="button" data-filter="today" ><?php _e( 'Today', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="yesterday" ><?php _e( 'Yesterday', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="last7days" ><?php _e( 'Last 7 days', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="last30days" ><?php _e( 'Last 30 days', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="lastyear" ><?php _e( 'Last month', 'learnpress' ) ?></button>
	  <button class="btn-filter-time" type="button" data-filter="custom" ><?php _e( 'Custom', 'learnpress' ) ?></button>
	</div>
	<div class="statistics-content">
		<input class="statistics-type" type="hidden" value="orders-statistics">
		<div class="statistics-group group-statistic-order">
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Total Orders', 'learnpress' ) ?></span>
				<span class="statistics-item-count total-order-count">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Completed Orders', 'learnpress' ) ?></span>
				<span class="statistics-item-count completed-order-count">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Proccessing Orders', 'learnpress' ) ?></span>
				<span class="statistics-item-count processing-order-count">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Pending Orders', 'learnpress' ) ?></span>
				<span class="statistics-item-count pending-order-count">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Cancelled Orders', 'learnpress' ) ?></span>
				<span class="statistics-item-count cancelled-order-count">0</span>
			</div>
			<div class="statistics-item">
				<span class="statistics-item-title"><?php _e( 'Fail Orders', 'learnpress' ) ?></span>
				<span class="statistics-item-count failed-order-count">0</span>
			</div>
		</div>
		<h3 class="statistics-title"><?php _e( 'Completed Orders', 'learnpress' ) ?></h3>
		<div id="orders-chart">
			<canvas id="orders-chart-content">
				
			</canvas>
		</div>
	</div>
</div>