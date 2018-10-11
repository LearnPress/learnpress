<?php
/**
 * Template for displaying payment form for checkout page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/term-conditions.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.10
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>
<p class="learn-press-terms learn-press-terms-and-conditions">
	<input type="checkbox" class="input-checkbox" name="terms_conditions" id="terms_conditions">
	<label for="terms_conditions" class="checkbox"><?php echo apply_filters( 'learn_press_content_item_protected_message',
			sprintf( __( 'I’ve read and accept the <a href="%s">terms &amp; conditions.</a>', 'learnpress' ), $page_link ) );?> <span class="required">*</span></label>
	<input type="hidden" name="terms_conditions_field" value="1">
</p>
