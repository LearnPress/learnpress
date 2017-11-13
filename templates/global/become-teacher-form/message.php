<?php
/**
 * Template for displaying messages in become-teacher form.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! isset( $messages ) ) {
	return;
}

foreach ( $messages as $code => $message ) {
	learn_press_display_message( $message );
}
