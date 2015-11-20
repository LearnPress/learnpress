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

//learn_press_debug($_REQUEST);
//$a = parse_str( 'mc_gross=26.90&invoice=981&protection_eligibility=Ineligible&address_status=confirmed&item_number1=&tax=0.00&item_number2=&payer_id=JZH37HUFZZX2E&address_street=1 Main St&payment_date=22:48:44 Nov 17, 2015 PST&payment_status=Pending&charset=windows-1252&address_zip=95131&mc_shipping=0.00&mc_handling=0.00&first_name=Test&mc_fee=1.08&address_country_code=US&address_name=Test Buyer&notify_version=3.8&custom={"order_id":981,"order_key":"order564c1ef55ece4"}&payer_status=verified&business=tunnhn-facilitator@gmail.com&address_country=United States&num_cart_items=2&mc_handling1=0.00&mc_handling2=0.00&address_city=San Jose&verify_sign=AhKjPyUAFwaeM5vZHwjU.0caZhHtAPU5P.jXj.li7vVco512IV1lKQv3&payer_email=tunnhn-buyer@gmail.com&mc_shipping1=0.00&mc_shipping2=0.00&tax1=0.00&tax2=0.00&txn_id=16B96957PX9483450&payment_type=instant&last_name=Buyer&address_state=CA&item_name1=How to submit a new course fgdfg dfgd&receiver_email=tunnhn-facilitator@gmail.com&item_name2=What is LearnPress?&payment_fee=1.08&quantity1=1&quantity2=1&receiver_id=9QLNG2KT79DZ4&pending_reason=paymentreview&txn_type=cart&mc_gross_1=25.00&mc_currency=USD&mc_gross_2=1.90&residence_country=US&test_ipn=1&transaction_subject={"order_id":981,"order_key":"order564c1ef55ece4"}&payment_gross=26.90&ipn_track_id=46fce6e9bbb31', $arr);
//learn_press_debug( $arr );

do_action( 'learn_press_before_checkout_form', $checkout );

$checkout_url = apply_filters( 'learn_press_get_checkout_url', LP()->cart->get_checkout_url() );
?>

<form method="post" name="lp-checkout" class="lp-checkout" action="<?php echo esc_url( $checkout_url ); ?>" enctype="multipart/form-data">

	<?php do_action( 'learn_press_checkout_before_order_review' ); ?>

	<div id="order_review" class="learn-press-checkout-review-order">
		<?php do_action( 'learn_press_checkout_order_review' ); ?>
	</div>

	<?php do_action( 'learn_press_checkout_after_order_review' ); ?>

</form>

<?php do_action( 'learn_press_after_checkout_form', $checkout ); ?>