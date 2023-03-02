<?php
/**
 * Template for displaying global message.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/lp-message.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  1.0.0
 * @since  4.2.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $message_data ) || ! isset( $message_data['content'] ) || ! isset( $message_data['status'] ) ) {
	return;
}

$classes = array( 'learn-press-message', $message_data['status'], 'lp-content-area' );
?>
<div
	class="<?php echo esc_attr( join( ' ', $classes ) ); ?>">
	<i class="fa"></i><?php echo wp_kses_post( $message_data['content'] ); ?>
</div>
