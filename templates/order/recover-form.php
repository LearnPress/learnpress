<?php
/**
 * Template for displaying recover order form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/order/recover-form.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$order_key = isset( $order ) && is_a( $order, 'LP_Order' ) ? $order->get_order_key() : '';
?>

<form class="lp-order-recover" method="post" action="">
	<input type="text" name="order-key" value="<?php echo esc_attr( $order_key ); ?>" placeholder="<?php esc_attr_e( 'Order key', 'learnpress' ); ?>">
	<input type="hidden" name="recover-order-nonce" value="<?php echo wp_create_nonce( 'recover-order' ); ?>">
	<input type="hidden" name="lp-ajax" value="recover-order">
	<button type="submit" class="lp-button button-recover-order"><?php esc_html_e( 'Recover', 'learnpress' ); ?></button>
</form>
