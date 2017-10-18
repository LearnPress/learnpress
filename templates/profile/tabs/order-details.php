<?php
/**
 * Display order details in user profile.
 *
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       3.x.x
 */


defined( 'ABSPATH' ) || exit();

global $profile;

if ( false === ( $order = $profile->get_view_order() ) ) {
	return;
}
learn_press_get_template( 'order/order-details.php', array( 'order' => $order ) );
learn_press_get_template( 'profile/tabs/orders/recover-my-order.php', array( 'order' => $order ) );

if ( $order->get_user_id() != get_current_user_id() ) {
	?>
    <p><?php printf( __( 'This order is paid for %s', 'learnpress' ), $order->get_user_email() ); ?></p>
	<?php
} else {
	if ( ( $checkout_email = $order->get_checkout_email() ) && $checkout_email != $profile->get_user()->get_email() ) {
		?>
        <p><?php printf( __( 'This order is paid by %s', 'learnpress' ), $order->get_checkout_email() ); ?></p>
		<?php
	}
}