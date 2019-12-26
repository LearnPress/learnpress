<?php
/**
 * Template for displaying payment form for checkout page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/term-conditions.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.10
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! $term_link = learn_press_get_page_link( 'term_conditions' ) ) {
	return;
}

if ( ! $term_text = learn_press_get_page_title( 'term_conditions' ) ) {
	$term_text = __( 'Terms of Service', 'learnpress' );
}

?>
<p class="lp-terms-and-conditions">
	<?php echo apply_filters( 'learn_press_content_item_protected_message',
		sprintf( __( 'By completing your purchase you agree to those <a href="%s" target="_blank">%s</a>.', 'learnpress' ), $term_link, $term_text ) ); ?>
</p>
