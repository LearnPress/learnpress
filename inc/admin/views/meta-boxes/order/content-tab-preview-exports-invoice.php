<?php
/**
 * Admin View: Content tab preview export invoice
 * @author hungkv
 * @since 3.2.7.8
 * @version 1.0.2
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
/**
 * @var $order LP_Order
 */
if ( ! isset( $order ) || ! isset( $currency_symbol ) ) {
	return;
}

$order_date = new LP_Datetime( $order->get_data( 'order_date' ) );
?>

<div class="lp-invoice__header">
	<div class="lp-invoice__hleft">
		<h1><?php echo esc_html__( 'Invoice', 'learnpress' ); ?></h1>
	</div>
	<div class="lp-invoice__hright">
		<?php
		echo wp_sprintf( '<p class="invoice-field invoice-title">%s</p>', esc_html( get_bloginfo( 'name' ) ) );
		echo wp_sprintf(
			'<p class="invoice-field invoice-date">%s: %s</p>',
			esc_html__( 'Order Date', 'learnpress' ),
			$order_date->format( LP_Datetime::I18N_FORMAT_HAS_TIME )
		);
		echo wp_sprintf( '<p class="invoice-field invoice-no">%s: %s</p>', esc_html__( 'Invoice No.', 'learnpress' ), esc_attr( $order->get_order_number() ) );
		echo wp_sprintf( '<p class="invoice-field invoice-customer">%s: %s</p>', esc_html__( 'Customer', 'learnpress' ), esc_html( $order->get_customer_name() ) );
		echo wp_sprintf( '<p class="invoice-field invoice-email">%s: %s</p>', esc_html__( 'Email', 'learnpress' ), esc_html( $order->get_user( 'email' ) ) );
		?>
		<p class="invoice-field invoice-method">
			<?php
			$method_title = $order->get_payment_method_title();
			$user_ip      = $order->get_user_ip_address();
			if ( $method_title && $user_ip ) {
				printf( 'Pay via <strong>%s</strong> at <strong>%s</strong>', apply_filters( 'learn-press/order-payment-method-title', $method_title, $order ), $user_ip );
			} elseif ( $method_title ) {
				printf( 'Pay via <strong>%s</strong>', apply_filters( 'learn-press/order-payment-method-title', $method_title, $order ) );
			} elseif ( $user_ip ) {
				printf( 'User IP <strong>%s</strong>', $user_ip );
			}
			?>
		</p>
	</div>
</div>
<div class="lp-invoice__body">
	<h4 class="order-data-heading"><?php echo esc_html__( 'Order details', 'learnpress' ); ?></h4>
	<div class="order-items">
		<table id="tab_customers" class="table table-striped list-order-items">
			<colgroup>
				<col>
				<col>
				<col>
				<col>
			</colgroup>
			<thead>
			<tr class='warning'>
				<th class="column-name"><?php _e( 'Item', 'learnpress' ); ?></th>
				<th class="column-price"><?php _e( 'Cost', 'learnpress' ); ?></th>
				<th class="column-quantity"><?php _e( 'Quantity', 'learnpress' ); ?></th>
				<th class="column-total align-right"><?php _e( 'Amount', 'learnpress' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			$filter = array(
				'p'     => 0,
				'limit' => '9999',
			);

			$items = $order->get_items_filter( $filter );
			if ( $items ) :
				?>
				<?php foreach ( $items as $item ) : ?>
				<tr>
					<td><?php echo esc_html( $item['name'] ); ?></td>
					<td><?php echo learn_press_format_price( $item['total'] ?? 0, $currency_symbol ?? '$' ); ?></td>
					<td>x <?php echo $item['quantity'] ?? 0; ?></td>
					<td><?php echo learn_press_format_price( $item['total'] ?? 0, $currency_symbol ?? '$' ); ?></td>
				</tr>
			<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
			<tfoot>
			<tr>
				<td></td>
				<td></td>
				<td><?php echo esc_html__( 'Sub Total', 'learnpress' ); ?></td>
				<td><?php echo learn_press_format_price( $order->get_data( 'order_subtotal' ), $currency_symbol ); ?></td>
			</tr>
			<tr>
				<td></td>
				<td></td>
				<td><?php _e( 'Total', 'learnpress' ); ?></td>
				<td><?php echo learn_press_format_price( $order->get_data( 'order_total' ), $currency_symbol ); ?></td>
			</tr>
			</tfoot>
		</table>
	</div>
</div>
