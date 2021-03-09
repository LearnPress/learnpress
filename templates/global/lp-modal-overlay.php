<?php
/**
 * Template for displaying modal overlay.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/lp-modal-overlay.php.
 *
 * @author  tungnx
 * @package  Learnpress/Templates
 * @version  1.0.0
 */

?>

<div class="lp-modal-dialog">
	<div class="lp-modal-content">
		<div class="lp-modal-header">
			<h3 class="modal-title">Modal title</h3>
		</div>
		<div class="lp-modal-body">
			<div class="main-content">Main Content</div>
		</div>
		<div class="lp-modal-footer">
			<button type="button" class="lp-button btn-no"><?php esc_html_e( 'No', 'learnpress' ); ?></button>
			<button type="button" class="lp-button btn-yes"><?php esc_html_e( 'Yes', 'learnpress' ); ?></button>
		</div>
	</div>
</div>
