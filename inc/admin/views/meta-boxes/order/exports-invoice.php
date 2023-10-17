<?php
/**
 * Admin View: Order invoice export pdf Meta box
 *
 * @author hungkv
 * @since 3.2.7.8
 * @version 1.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
if ( ! isset( $order ) ) {
	return;
}

$currency_symbol = learn_press_get_currency_symbol( $order->get_currency() );
?>

<?php global $post; ?>

<div class="submitbox" id="order-export">
	<div id="order-export__section">
		<div id="editor"></div>
		<button data-id="<?php echo esc_attr( $order->get_id() ); ?>" type="button" class="button" id="order-export__button"><?php echo esc_html__( 'PDF Invoice', 'learnpress' ); ?></button>
	</div>
</div>

<!-- The Modal -->
<div id="myModal" class="modal export-modal" style="display:none;">

	<!-- Modal content -->
	<div class="modal-content">
		<span class="close">&times;</span>

		<!-- Tab links -->
		<div class="tabs" id="order-export__tabs">
			<div class="preview-tab tab active" data-target="pdf-preview"><?php echo esc_html__( 'PDF Preview', 'learnpress' ); ?></div>
			<div class="options-tab tab" data-target="export-options"><?php echo esc_html__( 'Export Options', 'learnpress' ); ?></div>
		</div>
		<div id="panels">
			<div class="pdf-preview panel active">
				<!--Start print invoice-->
				<div id="lp-invoice">
					<div id="lp-invoice__content">
						<?php
						learn_press_admin_view(
							'meta-boxes/order/content-tab-preview-exports-invoice.php',
							array(
								'order'           => $order,
								'currency_symbol' => $currency_symbol,
							)
						);
						?>
					</div>
					<div id="lp-invoice__actions">
						<button type="button"
								id="lp-invoice__export"><?php echo esc_html__( 'Export to pdf', 'learnpress' ); ?></button>
					</div>
				</div>
				<!--End print invoice-->
			</div>
			<div class="export-options panel">
				<div class="export-options__content">
					<h4>Please select the fields you want to display</h4>
					<div class="export-options__select">
						<input type="checkbox" name="invoice-title" value="" checked="checked">
						<label for="order_date"> <?php echo esc_html__( 'Site Title', 'learnpress' ); ?></label><br>
						<input type="checkbox" name="invoice-date" value="" checked="checked">
						<label for="order_date"> <?php echo esc_html__( 'Order Date', 'learnpress' ); ?></label><br>
						<input type="checkbox" name="invoice-no" value="" checked="checked">
						<label for="order_date"> <?php echo esc_html__( 'Invoice No.', 'learnpress' ); ?></label><br>
						<input type="checkbox" name="invoice-customer" value="" checked="checked">
						<label for="order_date"> <?php echo esc_html__( 'Customer', 'learnpress' ); ?></label><br>
						<input type="checkbox" name="invoice-email" value="" checked="checked">
						<label for="order_date"> <?php echo esc_html__( 'Email', 'learnpress' ); ?></label><br>
						<input type="checkbox" name="invoice-method" value="" checked="checked">
						<label for="order_payment"> <?php echo esc_html__( 'Payment Medthod', 'learnpress' ); ?></label>
					</div>
					<div class="export-options__loading">
						<div class="spinner"></div>
					</div>
				</div>
				<div class="export-options__actions">
					<button type="button" data-id="<?php echo esc_attr( $order->get_id() ); ?>"
							id="lp-invoice__update"><?php echo esc_html__( 'Update', 'learnpress' ); ?></button>
				</div>
			</div>
		</div>

		<!--End print invoice-->
	</div>

</div>
