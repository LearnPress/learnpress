<?php
/**
 * Template for displaying global message.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/lp-message.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  1.0.1
 * @since  4.2.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $customer_message ) || ! isset( $customer_message['content'] ) || ! isset( $customer_message['status'] ) ) {
	return;
}

$classes = array( 'learn-press-message', $customer_message['status'], 'lp-content-area' );
?>
<div
	class="<?php echo esc_attr( join( ' ', $classes ) ); ?>">
	<i class="fa"></i><?php echo wp_kses_post( $customer_message['content'] ); ?>
</div>
