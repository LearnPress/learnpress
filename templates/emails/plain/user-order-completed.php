<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php echo "= " . $email_heading . " =\n\n"; ?>

<?php

do_action( 'learn_press_email_new_order_before_table', $order, $plain_text );

learn_press_get_template( 'emails/plain/order-items-table.php', array( 'order' => $order ) );

echo "\n" . sprintf( __( 'View order: %s', 'learnpress' ), learn_press_user_profile_link( $order->user_id, 'orders' ) ) . "\n";

do_action( 'learn_press_email_new_order_after_table', $order, $plain_text );

?>

<?php echo $footer_text . "\n\n"; ?>