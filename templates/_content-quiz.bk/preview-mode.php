

<?php
/**
 * Template for displaying message in preview
 *
 * @package LearnPress/Templates
 * @author  ThimPress
 * @version 1.0
 */

!defined( ABSPATH ) || exit();

if ( !is_preview() ) {
	return;
}
learn_press_display_message( __( 'You are currently viewing quiz in preview mode.', 'learnpress' ), 'error' );