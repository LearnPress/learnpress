<?php
/**
 * Template for Step item.
 *
 * This template for js read and render each item.
 * Purpose:
 * 1. Define struct template group step
 * 2. On js will read ".example-lp-item-step" and clone content ".lp-item-step"
 * 3. Js will render data to template have just clone, and append to ".lp-group-step"
 *
 * @author  tungnx
 * @package  Learnpress/Templates
 * @version  1.0.0
 * @since   4.0.3
 */

?>

<div class="example-lp-group-step">
	<div class="lp-group-step">
	</div>
	<div class="example-lp-item-step">
		<h3></h3>
		<div class="lp-item-step">
			<div class="lp-item-step-left">
				<input type="hidden" name="" value=""  />
			</div>
			<div class="lp-item-step-right">
				<label for=""><strong></strong></label>
				<div class="description"></div>
				<div class="percent"></div>
				<span class="progress-bar"></span>
			</div>
		</div>
	</div>
</div>

