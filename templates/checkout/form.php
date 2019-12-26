<?php
/**
 * Template for displaying checkout form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/form.php.
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
 * @var LP_Checkout $checkout
 */
$checkout = LP()->checkout();

// Prints message
learn_press_print_messages();

/**
 * @deprecated
 */
//do_action( 'learn_press_before_checkout_form', $checkout );

/**
 * @since 3.0.0
 *
 * @see   learn_press_checkout_form_login()
 * @see   learn_press_checkout_form_register()
 */
//do_action( 'learn-press/before-checkout-form' );
?>

    <form method="post" id="learn-press-checkout-form" name="learn-press-checkout-form"
          class="lp-checkout-form"
          tabindex="0"
          action="<?php echo esc_url( learn_press_get_checkout_url() ); ?>" enctype="multipart/form-data">

		<?php

		if ( has_action( 'learn-press/before-checkout-form' ) ) {
			?>
            <div class="lp-checkout-form__before">
				<?php
				/**
				 * LP Hook
				 *
				 * @since 4.0.0
				 */
				do_action( 'learn-press/before-checkout-form' );
				?>
            </div>
			<?php
		}
		/**
		 * LP Hook
		 *
		 * @since 4.0.0
		 */
		do_action( 'learn-press/checkout-form' );

		if ( has_action( 'learn-press/after-checkout-form' ) ) {
			?>
            <div class="lp-checkout-form__after">
				<?php
				/**
				 * LP Hook
				 *
				 * @since 4.0.0
				 */
				do_action( 'learn-press/after-checkout-form' );
				?>
            </div>
			<?php
		}
		?>
    </form>

<?php

// @since 3.0.0
//do_action( 'learn-press/after-checkout-form' );

/**
 * @deprecated
 */
//do_action( 'learn_press_after_checkout_form', $checkout );

get_footer();