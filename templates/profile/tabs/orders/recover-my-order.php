<?php
/**
 * Template for displaying form allow user get back their order by the key
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();

if ( isset( $order ) && is_a( $order, 'LP_Order' ) ) {
	if ( $order->is_guest() ) {
		?>
        <div class="profile-recover-order">

            <p><?php _e( 'This order was checked out by you but there is no user is assigned to.' ); ?></p>
            <p><?php _e( 'If the order is made for another one, you can send the code below for them.' ); ?></p>
            <p><?php _e( 'If the order is made for yourself, you can assign back to you here.' ); ?></p>
			<?php learn_press_get_template( 'order/recover-form.php', array( 'order' => $order ) ); ?>
        </div>
	<?php }
}

