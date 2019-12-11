<?php
/**
 * Template for displaying user form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/user-form.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php
if ( is_user_logged_in() ) {
	return;
}
?>

<div id="learn-press-checkout-user-form">

	<?php do_action( 'learn_press_checkout_user_form' ); ?>

    <div class="clearfix"></div>

</div>
