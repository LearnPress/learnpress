<?php
/**
 * Template for displaying checkout form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/form.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.6
 */

defined( 'ABSPATH' ) || exit();

$checkout = LearnPress::instance()->checkout();
?>
<?php
if ( ! is_user_logged_in() ) {
	$enable_login_on_page_checkout = LP_Settings::get_option( 'enable_login_checkout', 'yes' ) === 'yes';
	$login_text = sprintf(
		'<a class="lp-link-login" href="%s">%s</a>',
		learn_press_get_login_url( LP_Helper::getUrlCurrent() ),
		__( 'login', 'learnpress' )
	);

	if ( $enable_login_on_page_checkout ) {
		$login_text = __( 'login', 'learnpress' );
	}
	?>
	<div class="learn-press-message error">
		<?php
		printf(
			__( 'Please %s in to enroll in the course!', 'learnpress' ),
			$login_text
		);
		?>
	</div>
	<?php
}

learn_press_show_message();
?>
	<form method="post" id="learn-press-checkout-form" name="learn-press-checkout-form" class="lp-checkout-form"
		  tabindex="0" action="<?php echo esc_url_raw( learn_press_get_checkout_url() ); ?>"
		  enctype="multipart/form-data">
		<?php
		if ( has_action( 'learn-press/before-checkout-form' ) ) {
			?>
			<div class="lp-checkout-form__before">
				<?php do_action( 'learn-press/before-checkout-form' ); ?>
			</div>
			<?php
		}

		do_action( 'learn-press/checkout-form' );

		if ( has_action( 'learn-press/after-checkout-form' ) ) {
			?>
			<div class="lp-checkout-form__after">
				<?php do_action( 'learn-press/after-checkout-form' ); ?>
			</div>
			<?php
		}

		wp_nonce_field( 'learn-press-checkout', 'learn-press-checkout-nonce', false );
		?>
	</form>
<?php
