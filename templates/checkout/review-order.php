<?php
/**
 * Template for displaying reviewing before placing order.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/review-order.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

/**
 * @var LP_Cart   $cart
 * @var LP_Course $_course
 */

$cart = learn_press_get_checkout_cart();
?>

<div id="checkout-order" class="lp-checkout-block right">

    <h4><?php _e( 'Your order', 'learnpress' ) ?></h4>

    <div class="lp-checkout-order__inner">
        <table>
            <tbody>

			<?php
			/**
			 * @deprecated
			 */
			do_action( 'learn_press_review_order_before_cart_contents' );

			/**
			 * @since 3.0.0
			 */

			do_action( 'learn-press/review-order/before-cart-contents' );

			if ( $items = $cart->get_items() ) {
				foreach ( $items as $cart_item_key => $cart_item ) {

					$cart_item = apply_filters( 'learn-press/review-order/cart-item', $cart_item );
					$item_id   = $cart_item['item_id'];
					$_course   = apply_filters( 'learn-press/review-order/cart-item-product', learn_press_get_course( $item_id ), $cart_item );

					if ( $_course && 0 < $cart_item['quantity'] ) {
						?>
                        <tr class="<?php echo esc_attr( apply_filters( 'learn-press/review-order/cart-item-class', 'cart-item', $cart_item, $cart_item_key ) ); ?>">
							<?php
							/**
							 * @deprecated
							 */
							do_action( 'learn_press_review_order_before_cart_item', $cart_item );

							/**
							 * @since 3.0.0
							 */
							do_action( 'learn-press/review-order/before-cart-item', $cart_item, $cart_item_key );
							?>
                            <td class="course-thumbnail">
								<?php echo $_course->get_image(); ?>
                            </td>
                            <td class="course-name">
                                <a href="<?php the_permalink( $item_id ); ?>">
									<?php echo apply_filters( 'learn-press/review-order/cart-item-name', $_course->get_title(), $cart_item, $cart_item_key ); ?>
                                </a>
								<?php
								if ( $cart_item['quantity'] > 1 ) {
									echo apply_filters( 'learn-press/review-order/cart-item-quantity', ' <strong class="course-quantity">' . sprintf( '&times; %s', $cart_item['quantity'] ) . '</strong>', $cart_item, $cart_item_key );
								}
								?>
                            </td>
                            <td class="course-total col-number">
								<?php echo apply_filters( 'learn-press/review-order/cart-item-subtotal', $cart->get_item_subtotal( $_course, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
                            </td>
							<?php
							/**
							 * @since 3.0.0
							 */
							do_action( 'learn-press/review-order/after-cart-item', $cart_item, $cart_item_key );

							/**
							 * @deprecated
							 */
							do_action( 'learn_press_review_order_after_cart_item', $cart_item );
							?>
                        </tr>
						<?php
					}
				}
			}

			/**
			 * @since 3.0.0
			 */

			do_action( 'learn-press/review-order/after-cart-contents' );

			/**
			 * @deprecated
			 */
			do_action( 'learn_press_review_order_after_cart_contents' );

			?>

            </tbody>

            <tfoot>

            <tr class="cart-subtotal">

				<?php
				/**
				 * @since 3.0.0
				 */
				do_action( 'learn-press/review-order/before-subtotal-row' );
				?>

                <th colspan="2"><?php _e( 'Subtotal', 'learnpress' ); ?></th>
                <td class="col-number"><?php echo $cart->get_subtotal(); ?></td>

				<?php
				/**
				 * @since 3.0.0
				 */
				do_action( 'learn-press/review-order/after-subtotal-row' );
				?>
            </tr>

			<?php
			/**
			 * @deprecated
			 */
			do_action( 'learn_press_review_order_before_order_total' );

			/**
			 * @since 3.0.0
			 */
			do_action( 'learn-press/review-order/before-order-total' );

			?>

            <tr class="order-total">
				<?php
				/**
				 * @since 3.0.0
				 */
				do_action( 'learn-press/review-order/before-total-row' );
				?>

                <th colspan="2"><?php _e( 'Total', 'learnpress' ); ?></th>
                <td class="col-number"><?php echo $cart->get_total(); ?></td>

				<?php
				/**
				 * @since 3.0.0
				 */
				do_action( 'learn-press/review-order/after-total-row' );
				?>
            </tr>

			<?php

			/**
			 * @since 3.0.0
			 */
			do_action( 'learn-press/review-order/after-order-total' );

			/**
			 * @deprecated
			 */
			do_action( 'learn_press_review_order_after_order_total' );

			?>

            </tfoot>

        </table>

    </div>
</div>