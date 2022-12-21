<?php
/**
 * Template for displaying user form.
 *
 * Note: Use only LP3. In LP4 will remove, show don't use in LP4.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( is_user_logged_in() ) {
	return;
}
?>

<div id="learn-press-checkout-user-form">
	<?php do_action( 'learn_press_checkout_user_form' ); ?>
	<div class="clearfix"></div>
</div>
