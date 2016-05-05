<?php
/**
 * Template for displaying the button let user can finish a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */
defined( 'ABSPATH' ) || exit();
$can_finish = LP()->user->can( 'finish-course', get_the_ID() );
$nonce      = wp_create_nonce( sprintf( 'learn-press-finish-course-%d-%d', get_the_ID(), get_current_user_id() ) );
?>

<?php if ( $can_finish && ( $message = apply_filters( 'learn_press_finish_course_message', __( 'Congrats! You can finish this course right now.', 'learnpress' ) ) ) !== false ): ?>

	<?php learn_press_display_message( $message ); ?>

<?php endif; ?>
<button id="learn-press-finish-course" class="finish-course<?php echo $can_finish ? '' : ' hide-if-js'; ?>" data-id="<?php echo esc_attr( get_the_ID() ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
	<?php _e( 'Finish course', 'learnpress' ); ?>
</button>