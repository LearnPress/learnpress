<?php
/**
 * Admin View: Content tab preview export invoice
 * @author hungkv
 * @since 3.2.7.8
 * @version 1.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
if ( ! isset( $order ) || ! isset( $currency_symbol ) ) {
	return;
}

// get script learnpress js pdf
wp_enqueue_script( 'learnpress-jspdf' );
?>

<div class="lp-invoice__header">
    <div class="lp-invoice__hleft">
        <h1><?php echo esc_html__( 'Invoice', 'learnpress' ); ?></h1>
    </div>
    <div class="lp-invoice__hright">
		<?php
		if ( ! isset( $_POST['site_title'] ) || ( isset( $_POST['site_title'] ) && LP_Helper::sanitize_params_submitted( $_POST['site_title'] ) === 'check' ) ) {
			echo wp_sprintf( '<p>%s</p>', esc_html( get_bloginfo( 'name' ) ) );
		}
		if ( ! isset( $_POST['order_date'] ) || ( isset( $_POST['order_date'] ) && LP_Helper::sanitize_params_submitted( $_POST['order_date'] ) === 'check' ) ) {
			echo wp_sprintf( '<p class="date">%s: %s</p>', esc_html__( 'Order Date', 'learnpress' ), date( 'd-m-Y h:i:s', esc_attr( $order->get_order_date( 'timestamp' ) ) ) );
		}
		if ( ! isset( $_POST['invoice_no'] ) || ( isset( $_POST['invoice_no'] ) && LP_Helper::sanitize_params_submitted( $_POST['invoice_no'] ) === 'check' ) ) {
			echo wp_sprintf( '<p class="invoice-no">%s: %s</p>', esc_html__( 'Invoice No.', 'learnpress' ), esc_attr( $order->get_order_number() ) );
		}
		if ( ! isset( $_POST['order_customer'] ) || ( isset( $_POST['order_customer'] ) && LP_Helper::sanitize_params_submitted( $_POST['order_customer'] ) === 'check' ) ) {
			echo wp_sprintf( '<p class="invoice-customer">%s: %s</p>', esc_html__( 'Customer', 'learnpress' ), esc_html( $order->get_customer_name() ) );
		}
		if ( ! isset( $_POST['order_email'] ) || ( isset( $_POST['order_email'] ) && LP_Helper::sanitize_params_submitted( $_POST['order_email'] ) === 'check' ) ) {
			echo wp_sprintf( '<p class="invoice-email">%s: %s</p>', esc_html__( 'Email', 'learnpress' ), esc_html( $order->get_user( 'email' ) ) );
		}
		?>
		<?php if ( ! isset( $_POST['order_payment'] ) || ( isset( $_POST['order_payment'] ) && LP_Helper::sanitize_params_submitted( $_POST['order_payment'] ) === 'check' ) ): ?>
            <p class="invoice-method">
				<?php
				$method_title = $order->get_payment_method_title();
				$user_ip      = $order->get_user_ip_address();
				if ( $method_title && $user_ip ) {
					printf( 'Pay via <strong>%s</strong> at <strong>%s</strong>', apply_filters( 'learn-press/order-payment-method-title', $method_title, $order ), $user_ip );
				} elseif ( $method_title ) {
					printf( 'Pay via <strong>%s</strong>', apply_filters( 'learn-press/order-payment-method-title', $method_title, $order ) );
				} elseif ( $user_ip ) {
					printf( 'User IP <strong>%s</strong>', $user_ip );
				} ?>
            </p>
		<?php endif; ?>
    </div>
</div>
<div class="lp-invoice__body">
    <h4 class="order-data-heading"><?php echo esc_html__( 'Order details', 'learnpress' ); ?></h4>
    <div class="order-items">
        <table id="tab_customers" class="table table-striped list-order-items">
            <colgroup>
                <col width="40%">
                <col width="20%">
                <col width="20%">
                <col width="20%">
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
				'limit' => '9999'
			);
			if ( $items = $order->get_items_filter( $filter ) ): ?>
				<?php foreach ( $items as $item ) : ?>
                    <tr>
                        <td><?php echo esc_html( $item['name'] ); ?></td>
                        <td><?php echo learn_press_format_price( isset( $item['total'] ) ? $item['total'] : 0, isset( $currency_symbol ) ? $currency_symbol : '$' ); ?></td>
                        <td>x <?php echo isset( $item['quantity'] ) ? $item['quantity'] : 0; ?></td>
                        <td><?php echo learn_press_format_price( isset( $item['total'] ) ? $item['total'] : 0, isset( $currency_symbol ) ? $currency_symbol : '$' ); ?></td>
                    </tr>
				<?php endforeach; ?>
			<?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td><?php echo esc_html__( 'Sub Total', 'learnpress' ); ?></td>
                <td><?php echo learn_press_format_price( $order->order_subtotal, $currency_symbol ); ?></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td><?php _e( 'Total', 'learnpress' ); ?></td>
                <td><?php echo learn_press_format_price( $order->order_total, $currency_symbol ); ?></td>
            </tr>
            </tfoot>
        </table>
    </div>
</div>