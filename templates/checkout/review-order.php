<?php
/**
 * Review order table
 *
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

$review_order_heading = apply_filters( 'learn_press_checkout_review_order_heading', __( 'Your order', 'learnpress' ) );
$cart                 = learn_press_get_checkout_cart();

?>

<?php if ( $review_order_heading ) { ?>
	<h3><?php echo $review_order_heading; ?></h3>
<?php } ?>

<table class="learn-press-checkout-review-order-table">
	<thead>
	<tr>
		<th class="course-name"><?php _e( 'Course', 'learnpress' ); ?></th>
		<th class="course-total"><?php _e( 'Total', 'learnpress' ); ?></th>
	</tr>
	</thead>
	<tbody>

	<?php do_action( 'learn_press_review_order_before_cart_contents' ); ?>

	<?php if ( $items = $cart->get_items() ) foreach ( $items as $item_id => $cart_item ) {
		$cart_item = apply_filters( 'learn_press_cart_item', $cart_item );
		$_course   = learn_press_get_course( $item_id );
		if ( $_course && $cart_item['quantity'] > 0 ) {
			?>
			<tr class="<?php echo esc_attr( apply_filters( 'learn_press_cart_item_class', 'cart-item', $cart_item ) ); ?>">
				<?php do_action( 'learn_press_review_order_before_cart_item', $cart_item ); ?>
				<td class="course-name">
                    <a href="<?php the_permalink($item_id) ?>" style="box-shadow: none"><?php echo apply_filters( 'learn_press_cart_item_name', $_course->get_title(), $cart_item ) . '&nbsp;'; ?></a>
					<?php echo apply_filters( 'learn_press_cart_item_quantity', ' <strong class="course-quantity">' . sprintf( '&times; %s', $cart_item['quantity'] ) . '</strong>', $cart_item ); ?>
				</td>
				<td class="course-total">
					<?php echo apply_filters( 'learn_press_cart_item_subtotal', $cart->get_item_subtotal( $_course, $cart_item['quantity'] ), $cart_item ); ?>
				</td>
				<?php do_action( 'learn_press_review_order_after_cart_item', $cart_item ); ?>
			</tr>
			<?php
		}
	} ?>

	<?php do_action( 'learn_press_review_order_after_cart_contents' ); ?>

	</tbody>

	<tfoot>

	<tr class="cart-subtotal">
		<th><?php _e( 'Subtotal', 'learnpress' ); ?></th>
		<td><?php echo $cart->get_subtotal(); ?></td>
	</tr>

	<?php do_action( 'learn_press_review_order_before_order_total' ); ?>

	<tr class="order-total">
		<th><?php _e( 'Total', 'learnpress' ); ?></th>
		<td><?php echo $cart->get_total(); ?></td>
	</tr>

	<?php do_action( 'learn_press_review_order_after_order_total' ); ?>

	</tfoot>

</table>
