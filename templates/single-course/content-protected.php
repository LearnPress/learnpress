<?php
/**
 * Template for displaying message for course content protected.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content-protected.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( !isset( $can_view_item ) ) {
	return;
}
?>

<div class="learn-press-content-protected-message">

	<span class="icon"></span>

	<?php
	if ( ! is_user_logged_in() ) {
		echo apply_filters( 'learn_press_content_item_protected_message',
			sprintf( __( 'This content is protected, please <a href="%s">login</a> and enroll course to view this content!', 'learnpress' ), learn_press_get_login_url( learn_press_get_current_url() ) ) );
	} elseif ( $can_view_item == 'is_blocked' ) {
		echo apply_filters( 'learn_press_content_item_locked_message',
			__( 'This lesson has been locked', 'learnpress' ) );
	} elseif ( ! $can_view_item ) {
		echo apply_filters( 'learn_press_content_item_protected_message',
			__( 'This content is protected, please enroll course to view this content!', 'learnpress' ) );
		learn_press_course_enroll_button();
	}
	?>
</div>