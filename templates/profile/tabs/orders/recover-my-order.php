<?php
/**
 * Template for displaying form allow user get back their order by the key in user profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/orders/recover-my-order.php.
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

<?php if ( isset( $order ) && is_a( $order, 'LP_Order' ) ) { ?>

	<?php if ( $order->is_guest() ) { ?>

        <div class="profile-recover-order">
            <p><?php _e( 'This order was checked out by you but there is no user was assigned to.' ); ?></p>
            <p><?php _e( 'If the order is made for another one, you can send the code below to them.' ); ?></p>
            <p><?php _e( 'If the order is made for yourself, you can assign it to you here.' ); ?></p>
			<?php learn_press_get_template( 'order/recover-form.php', array( 'order' => $order ) ); ?>
        </div>

	<?php }
}

