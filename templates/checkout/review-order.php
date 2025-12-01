<?php
/**
 * Template for displaying reviewing before placing order.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/review-order.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.3
 */

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

defined( 'ABSPATH' ) || exit();
/**
 * @var LP_Cart $cart
 */
if ( ! isset( $cart ) || ! $cart ) {
	return;
}

$singleCourseTemplate = SingleCourseTemplate::instance();
?>

<div id="checkout-order" class="lp-checkout-block right">

	<h4><?php esc_html_e( 'Your order', 'learnpress' ); ?></h4>

	<div class="lp-checkout-order__inner">
		<table>
			<tbody>

			<?php
			do_action( 'learn_press_review_order_before_cart_contents' );
			do_action( 'learn-press/review-order/before-cart-contents' );

			$items = $cart->get_items();

			if ( $items ) {
				foreach ( $items as $cart_item_key => $cart_item ) {
					$cart_item = apply_filters( 'learn-press/review-order/cart-item', $cart_item );
					$item_id   = $cart_item['item_id'];

					$itemModel = apply_filters(
						'learn-press/review-order/item',
						CourseModel::find( $item_id, true ),
						$cart_item
					);

					if ( has_filter( 'learn-press/review-order/cart-item-product' ) ) {
						$itemModel = apply_filters( 'learn-press/review-order/cart-item-product', learn_press_get_course( $item_id ), $cart_item );
					}

					if ( $itemModel instanceof LP_Course ) {
						$itemModel = CourseModel::find( $itemModel->get_id(), true );
					}

					if ( $itemModel instanceof CourseModel ) {
						?>
						<tr class="cart-item">
							<td class="course-thumbnail">
								<?php
								echo $singleCourseTemplate->html_image( $itemModel )
								?>
							</td>
							<td class="course-name">
								<?php
								echo sprintf(
									'<a href="%s" class="course-name">%s</a>',
									esc_url_raw(
										apply_filters(
											'learn-press/review-order/cart-item-link',
											$itemModel->get_permalink(),
											$cart_item
										)
									),
									wp_kses_post(
										apply_filters(
											'learn-press/review-order/cart-item-name',
											$singleCourseTemplate->html_title( $itemModel ),
											$cart_item,
											$cart_item_key
										)
									)
								)
								?>

								<?php
								if ( $cart_item['quantity'] > 1 ) {
									echo wp_kses_post(
										apply_filters(
											'learn-press/review-order/cart-item-quantity',
											sprintf(
												'<strong class="course-quantity"> &times; %s</strong>',
												$cart_item['quantity']
											),
											$cart_item,
											$cart_item_key
										)
									);
								}
								?>
							</td>
							<td class="course-total col-number">
								<?php
								echo apply_filters(
									'learn-press/review-order/cart-item-subtotal',
									$cart->get_item_subtotal( $itemModel, $cart_item['quantity'] ),
									$cart_item,
									$cart_item_key
								);
								?>
							</td>
						</tr>
						<?php
					} else {
						?>
						<tr class="cart-item">
							<?php do_action( 'learn-press/checkout/cart-item', $itemModel, $cart_item ); ?>
						</tr>
						<?php
					}
				}
			}

			do_action( 'learn-press/review-order/after-cart-contents' );
			do_action( 'learn_press_review_order_after_cart_contents' );
			?>

			</tbody>

			<tfoot>
			<tr class="cart-subtotal">

				<?php do_action( 'learn-press/review-order/before-subtotal-row' ); ?>

				<th colspan="2"><?php _e( 'Subtotal', 'learnpress' ); ?></th>
				<td class="col-number"><?php echo esc_html( $cart->get_subtotal() ); ?></td>

				<?php do_action( 'learn-press/review-order/after-subtotal-row' ); ?>
			</tr>

			<?php
			do_action( 'learn_press_review_order_before_order_total' );
			do_action( 'learn-press/review-order/before-order-total' );
			?>

			<tr class="order-total">
				<?php do_action( 'learn-press/review-order/before-total-row' ); ?>

				<th colspan="2"><?php esc_html_e( 'Total', 'learnpress' ); ?></th>
				<td class="col-number"><?php echo esc_html( $cart->get_total() ); ?></td>

				<?php do_action( 'learn-press/review-order/after-total-row' ); ?>
			</tr>

			<?php
			do_action( 'learn-press/review-order/after-order-total' );
			do_action( 'learn_press_review_order_after_order_total' );
			?>

			</tfoot>
		</table>
	</div>
</div>
