<?php
/**
 * Template for displaying message in become teacher form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/become-teacher-form/message.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $messages ) ) {
	return;
}
?>

<?php
foreach ( $messages as $code => $message ) {
	learn_press_display_message( $message );
}
