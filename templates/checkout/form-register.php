<?php
/**
 * Output register form
 *
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

$heading              = apply_filters( 'learn_press_checkout_register_heading', __( 'New Customer', 'learnpress' ) );
$subheading           = apply_filters( 'learn_press_checkout_register_subheading', __( 'Register Account', 'learnpress' ) );
$register_url         = learn_press_get_register_url();
$register_button_text = apply_filters( 'learn_press_checkout_register_button_text', __( 'Continue', 'learnpress' ) );
$content              = sprintf( __( 'By creating an account you will be able to keep track of the course\'s progress you have previously enrolled.<a href="%s">%s</a>', 'learnpress' ), $register_url, $register_button_text );
$content              = apply_filters( 'learn_press_checkout_register_content', $content );

?>

<div id="learn-press-checkout-user-register" class="learn-press-user-form">

	<?php do_action( 'learn_press_checkout_before_user_register_form' ); ?>

	<?php if ( $heading ) { ?>
		<h3 class="form-heading"><?php echo $heading; ?></h3>
	<?php } ?>

	<?php if ( $subheading ) { ?>
		<p class="form-subheading"><?php echo $subheading; ?></p>
	<?php } ?>

	<?php if ( $content ) { ?>
		<div class="form-content">
			<?php echo $content; ?>
		</div>
	<?php } ?>

	<?php do_action( 'learn_press_checkout_after_user_register_form' ); ?>

</div>