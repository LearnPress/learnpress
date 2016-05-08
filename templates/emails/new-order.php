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

<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */
?>
<?php do_action( 'learn_press_email_header', $email_heading ); ?>

	<p><?php printf( __( 'New order placed by <strong>%s</strong>', 'learnpress' ), $order->get_user_name() ); ?>

		<?php do_action( 'learn_press_email_user_order_completed_before_table', $order, $plain_text ); ?>

		<?php learn_press_get_template( 'emails/order-items-table.php', array( 'order' => $order ) ); ?>

	<p><?php echo "\n" . sprintf( __( 'View order: <a href="%s">%s</a>', 'learnpress' ), admin_url( 'post.php?post=' . $order->id . '&action=edit' ), $order->get_order_number() ); ?></p>

<?php do_action( 'learn_press_email_user_order_completed_after_table', $order, $plain_text ); ?>

<?php do_action( 'learn_press_email_footer', $footer_text );