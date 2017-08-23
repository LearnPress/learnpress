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
