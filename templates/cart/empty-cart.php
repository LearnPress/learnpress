<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

learn_press_add_notice( __( 'Your cart is currently empty.', 'learn_press' ), 'error' );

learn_press_print_notices();
?>
<a href="<?php echo learn_press_get_page_link( 'courses' ); ?>"><?php _e( 'Back to class', 'learn_press' ); ?></a>