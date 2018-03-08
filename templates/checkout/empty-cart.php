<?php
/**
 * Template for displaying notice empty cart form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/empty-cart.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php learn_press_display_message( __( 'Your cart is currently empty.', 'learnpress' ), 'error' );

$courses_link = learn_press_get_page_link( 'courses' );
if ( ! $courses_link ) {
	return;
}
?>

<a href="<?php echo learn_press_get_page_link( 'courses' ); ?>"><?php _e( 'Back to class', 'learnpress' ); ?></a>