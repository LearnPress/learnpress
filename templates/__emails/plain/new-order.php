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

    printf( __( 'New order placed by %s', 'learnpress' ), $order->get_user_name() );
    echo "\n\n";

    do_action( 'learn_press_email_new_order_before_table', $order, $plain_text );

    learn_press_get_template( 'emails/plain/order-items-table.php', array( 'order' => $order ) );

    echo "\n" . sprintf( __( 'View order: %s', 'learnpress' ), admin_url( 'post.php?post=' . $order->id . '&action=edit' ) ) . "\n";

    do_action( 'learn_press_email_new_order_after_table', $order, $plain_text );
?>

<?php echo $footer_text . "\n\n"; ?>