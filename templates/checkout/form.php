<?php
/**
 * Template for displaying checkout form
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

learn_press_print_notices();

do_action( 'learn_press_before_checkout_form', $checkout );

$checkout_url = apply_filters( 'learn_press_get_checkout_url', LP()->get_checkout_cart()->get_checkout_url() );
?>

<form method="post" id="learn-press-checkout" name="lp-checkout" class="lp-checkout" action="<?php echo esc_url( $checkout_url ); ?>" enctype="multipart/form-data">

	<?php do_action( 'learn_press_checkout_before_order_review' ); ?>

	<div id="order_review" class="learn-press-checkout-review-order">
		<?php do_action( 'learn_press_checkout_order_review' ); ?>
	</div>

	<?php do_action( 'learn_press_checkout_after_order_review' ); ?>

</form>

<?php do_action( 'learn_press_after_checkout_form', $checkout ); ?>