<?php
/**
 * Template for displaying description of lesson.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-lesson/description.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

/**
 * @var LP_Lesson $lesson
 */
if ( ! isset( $lesson ) ) {
	return;
}

$content = $lesson->get_content();

if ( ! $content ) {
	$message = esc_html__( 'The lesson content is empty.', 'learnpress' );

	if ( $lesson->current_user_can_edit() ) {
		$message .= sprintf( '<a href="%s" class="edit-content">%s</a>', esc_url_raw( $lesson->get_edit_link() ), esc_html__( 'Edit', 'learnpress' ) );
	}

	learn_press_display_message( $message, 'notice' );
	return;
}
?>

<div class="content-item-description lesson-description">
	<?php
	learn_press_echo_vuejs_write_on_php( $content );
	?>
</div>
