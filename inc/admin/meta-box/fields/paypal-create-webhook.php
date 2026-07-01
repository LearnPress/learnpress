<?php
/**
 * Field: button to create/reuse the PayPal subscription webhook.
 *
 * @version 1.0.0
 * @since 4.4.1
 */

if ( ! isset( $value ) ) {
	return;
}
?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label><?php echo wp_kses_post( $value['title'] ); ?></label>
	</th>
	<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
		<button type="button" id="lp-paypal-create-webhook" class="button">
			<?php esc_html_e( 'Create webhook', 'learnpress' ); ?>
		</button>
		<button type="button" id="lp-paypal-check-webhook-status" class="button">
			<?php esc_html_e( 'Check webhook status', 'learnpress' ); ?>
		</button>
		<span id="lp-paypal-webhook-status" style="margin-left:8px;"></span>
		<?php echo wp_kses_post( $description ); ?>
	</td>
</tr>
