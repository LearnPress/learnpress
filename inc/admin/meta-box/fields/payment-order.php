<?php $gateways = LP_Gateways::instance()->get_gateways(); ?>

<table class="learn-press-payments<?php echo sizeof( $gateways ) > 1 ? ' sortable' : ''; ?>">
	<thead>
	<tr>
		<th class="order"></th>
		<th class="name"><?php esc_html_e( 'Payment', 'learnpress' ); ?></th>
		<th class="id"><?php esc_html_e( 'ID', 'learnpress' ); ?></th>
		<th class="description"><?php esc_html_e( 'Description', 'learnpress' ); ?></th>
		<th class="status"><?php esc_html_e( 'Enable/Disable', 'learnpress' ); ?></th>
	</tr>
	</thead>

	<tbody>
		<?php foreach ( $gateways as $gateway ) : ?>
			<tr id="payment-<?php echo esc_attr( $gateway->get_id() ); ?>" data-payment="<?php echo esc_attr( $gateway->get_id() ); ?>">
				<td class="order"><span class="dashicons dashicons-menu"></span></td>
				<td class="name">
					<a href="<?php echo esc_url_raw( admin_url( 'admin.php?page=learn-press-settings&tab=payments&section=' . $gateway->get_id() ) ); ?>">
						<?php echo esc_html( $gateway->get_method_title() ); ?>
					</a>
				</td>
				<td class="id"><?php echo esc_html( $gateway->get_id() ); ?></td>
				<td class="description"><?php echo wp_kses_post( $gateway->get_method_description() ); ?></td>
				<td class="status<?php echo esc_attr( $gateway->is_enabled() ? ' enabled' : '' ); ?>">
					<span class="dashicons dashicons-yes"></span>
					<input type="hidden" name="payment-order" value="<?php echo esc_attr( $gateway->get_id() ); ?>"/>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<div class="lp-payment-addons-promo">
	<p>
		<?php esc_html_e( 'Need more payment options?', 'learnpress' ); ?>
		<a href="https://thimpress.com/product-tag/payment/" target="_blank" rel="noopener noreferrer" class="lp-payment-addons-promo__link">
			<?php esc_html_e( 'Explore Addons', 'learnpress' ); ?>
			<span class="dashicons dashicons-external"></span>
		</a>
	</p>
</div>
