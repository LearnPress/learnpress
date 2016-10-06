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
<?php do_action( 'learn_press_email_header', $email_heading ); ?>

<p><?php printf( __( 'Order placed in <strong>%s</strong>', 'learnpress' ), get_option( 'blogname' ) ); ?>

    <?php do_action( 'learn_press_email_new_order_customer_before_table', $order, $plain_text ); ?>

    <?php learn_press_get_template( 'emails/order-items-table.php', array( 'order' => $order ) ); ?>

<p><?php echo "\n" . sprintf( __( 'View order: <a href="%s">%s</a>', 'learnpress' ), $order->get_view_order_url(), $order->get_order_number() ); ?></p>

<?php do_action( 'learn_press_email_new_order_customer_after_table', $order, $plain_text ); ?>

<?php
do_action( 'learn_press_email_footer', $footer_text );
