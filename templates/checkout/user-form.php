<<<<<<< HEAD
<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( is_user_logged_in() ) {
	return;
}
?>

<div id="learn-press-checkout-user-form">

	<?php do_action( 'learn_press_checkout_user_form' ); ?>

	<div class="clearfix"></div>
</div>
=======
<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( is_user_logged_in() ) {
	return;
}
?>

<div id="learn-press-checkout-user-form">

	<?php do_action( 'learn_press_checkout_user_form' ); ?>

	<div class="clearfix"></div>
</div>
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
