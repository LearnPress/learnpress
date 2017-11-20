<?php
/**
 * Template for displaying order details tab in user profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/tabs/order-details.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php global $profile; ?>

<?php if ( false === ( $order = $profile->get_view_order() ) ) {
	return;
} ?>

<?php
learn_press_get_template( 'order/order-details.php', array( 'order' => $order ) );
learn_press_get_template( 'profile/tabs/orders/recover-my-order.php', array( 'order' => $order ) );
?>

<?php if ( $order->get_user_id() != get_current_user_id() ) { ?>

    <p><?php printf( __( 'This order is paid for %s', 'learnpress' ), $order->get_user_email() ); ?></p>

<?php } else { ?>

	<?php if ( ( $checkout_email = $order->get_checkout_email() ) && $checkout_email != $profile->get_user()->get_email() ) { ?>


        <p><?php printf( __( 'This order is paid by %s', 'learnpress' ), $order->get_checkout_email() ); ?></p>

		<?php
	}
}