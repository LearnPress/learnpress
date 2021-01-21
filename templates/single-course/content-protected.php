<?php
/**
 * Template for displaying message for course content protected.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content-protected.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! isset( $can_view_item ) ) {
	return;
}

if ( ! isset( $block_by_check ) ) {
	return;
}
?>

<div class="learn-press-content-protected-message">

    <span class="icon"></span>

	<?php
	if ( ! is_user_logged_in() ) {
		echo sprintf(
			wp_kses_post(
				'This content is protected, please <a class="lp-link-login" href="%s">login</a> and enroll course to view this content!',
				'learnpress'
			),
			learn_press_get_login_url( learn_press_get_current_url() )
		);
	} elseif ( $can_view_item == 'is_blocked' ) {
		if ( $block_by_check === 'by_duration_expires' ) {
			echo esc_html__( 'The course duration has run out. You cannot access the content of this course more.',
				'learnpress' );
		} else {
			echo apply_filters( 'learn_press_content_item_locked_message',
				__( 'This lesson has been locked', 'learnpress' ) );
		}
	} elseif ( ! $can_view_item ) {
		echo esc_html__( 'This content is protected, please enroll course to view this content!', 'learnpress' );
		learn_press_course_enroll_button();
	}
	?>
</div>
