<?php
/**
 * Template for displaying email order items table.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/emails/order-items-table.php
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.1
 * @editor tungnx
 * @modify 4.1.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
/**
 * @var $order LP_Order
 */
if ( ! isset( $order ) ) {
	error_log( 'Invalid params on ' . __FILE__ );
	return;
}

$items = $order->get_items();

if ( ! $items ) {
	return;
}

$email_content = '';

if ( $order->is_manual() ) {
	$user_ids = $order->get_user_id();
	if ( is_array( $user_ids ) ) {
		$email_arr = [];
		foreach ( $user_ids as $user_id ) {
			$user = get_user_by( 'ID', $user_id );
			if ( $user ) {
				$email_arr[] = $user->user_email;
			}
		}

		$email_content = implode( ',', $email_arr );
	} else {
		$email_content = $order->get_user_email( $user_ids );
	}
} else {
	$email_content = $order->get_user_email( $order->get_user_id() );
}
?>

<h3 class="order-table-items-heading">
	<?php _e( 'Order summary', 'learnpress' ); ?>
</h3>

<table class="order-details">
	<tr>
		<th><?php _e( 'Order Number', 'learnpress' ); ?></th>
		<td><?php echo $order->get_order_number(); ?></td>
	</tr>
	<tr>
		<th><?php _e( 'Purchase Date', 'learnpress' ); ?></th>
		<td><?php echo date_i18n( get_option( 'date_format' ), $order->get_order_date( 'timestamp' ) ); ?></td>
	</tr>
	<tr>
		<th><?php _e( 'Payment Method', 'learnpress' ); ?></th>
		<td><?php echo $order->get_payment_method_title(); ?></td>
	</tr>
	<tr>
		<th><?php _e( 'Status', 'learnpress' ); ?></th>
		<td><?php echo $order->get_order_status_html(); ?></td>
	</tr>
	<tr>
		<th><?php _e( 'User Email', 'learnpress' ); ?></th>
		<td><?php echo $email_content; ?></td>
	</tr>
</table>

<table class="order-table-items" cellspacing="0" cellpadding="5">
	<thead>
	<tr>
		<?php do_action( 'learn-press/before-email-order-item-heading', $order ); ?>
		<th class="column-name"><?php _e( 'Course', 'learnpress' ); ?></th>
		<th class="column-quantity"><?php _e( 'Quantity', 'learnpress' ); ?></th>
		<th class="column-number"><?php _e( 'Price', 'learnpress' ); ?></th>
		<?php do_action( 'learn-press/after-email-order-item-heading', $order ); ?>
	</tr>
	</thead>
	<tbody>
	<?php
	foreach ( $items as $item_id => $item ) :
		if ( ! isset( $item['course_id'] ) ) {
			continue;
		}
		$course = apply_filters( 'learn-press/email-order-item-course', learn_press_get_course( $item['course_id'] ), $item );

		?>
		<tr>
			<?php do_action( 'learn-press/before-email-order-item', $item_id, $item, $order ); ?>
			<td class="column-name">
				<?php echo apply_filters( 'learn-press/email-order-item-name', $item['name'], $item ); ?>
			</td>
			<td class="column-quantity">
				<?php echo apply_filters( 'learn-press/email-order-item-quantity', $item['quantity'], $item ); ?>
			</td>
			<td class="column-number">
				<?php echo apply_filters( 'learn-press/email-order-item-cost', learn_press_format_price( $item['total'], learn_press_get_currency_symbol( $order->get_currency() ) ), $item ); ?>
			</td>
			<?php do_action( 'learn-press/after-email-order-item', $item_id, $item, $order ); ?>
		</tr>

	<?php endforeach; ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="2" class="column-number"><?php _e( 'Total', 'learnpress' ); ?></td>
		<td class="column-number">
			<?php echo $order->get_formatted_order_total(); ?>
		</td>
	</tr>
	</tfoot>
</table>
