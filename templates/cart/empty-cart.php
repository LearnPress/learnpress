<?php
/**
 * Template for displaying empty cart page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/cart/empty-cart.php
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php learn_press_display_message( __( 'Your cart is currently empty.', 'learnpress' ), 'error' ); ?>

<?php $courses_link = learn_press_get_page_link( 'courses' ); ?>
<?php if ( ! $courses_link ) {
	return;
}
?>

<a href="<?php echo esc_url( $courses_link ); ?>"><?php _e( 'Back to class', 'learnpress' ); ?></a>