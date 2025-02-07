<?php
/**
 * Template for displaying complete button in content lesson.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-lesson/button-complete.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.5
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $item ) || ! isset( $user ) || ! isset( $course ) ) {
	return;
}

if ( $item->is_preview() && ! $user->has_enrolled_course( $course->get_id() ) ) {
	return;
}

$message_confirm_complete_item = sprintf(
	'%s "%s"?',
	__( 'Do you want to complete the lesson', 'learnpress' ),
	$item->get_title()
);
$completed                     = $user->has_completed_item( $item->get_id(), $course->get_id() );

if ( $completed ) :
	$user_item_data = $user->get_item_data( $item->get_id(), $course->get_id() );
	if ( empty( $user_item_data ) ) {
		return;
	}
	?>
	<div class="learn-press-message success">
		<?php
		echo sprintf(
			'%s %s',
			esc_html__( 'You have completed this lesson at ', 'learnpress' ),
			$user_item_data->get_end_time()->format( LP_Datetime::I18N_FORMAT_HAS_TIME )
		)
		?>
	</div>
	<button class="lp-button completed" disabled>
		<i class="lp-icon-check"></i><?php esc_html_e( 'Completed', 'learnpress' ); ?>
	</button>
	<?php
else :
	$item_id_next = $course->get_next_item();
	/**
	 * @use LessonAjax::user_complete_lesson
	 */
	?>
	<form method="post" name="learn-press-form-complete-lesson"
		action="<?php echo add_query_arg( [ 'complete-lesson' => '' ], LP_Settings::url_handle_lp_ajax() ); ?>"
		class="learn-press-form form-button <?php echo esc_attr( $completed ) ? 'completed' : ''; ?>"
		data-title="<?php echo esc_attr( __( 'Complete lesson', 'learnpress' ) ); ?>"
		data-confirm="<?php echo esc_attr( $message_confirm_complete_item ); ?>">

		<?php do_action( 'learn-press/lesson/before-complete-button' ); ?>

		<input type="hidden" name="lesson_id" value="<?php echo esc_attr( $item->get_id() ); ?>"/>
		<input type="hidden" name="course_id" value="<?php echo esc_attr( $course->get_id() ); ?>"/>
		<input type="hidden" name="nonce"
			value="<?php echo wp_create_nonce( 'wp_rest' ); ?>"/>
		<input type="hidden" name="lp-load-ajax" value="user_complete_lesson"/>
		<button class="lp-button button-complete-lesson lp-btn-complete-item"
			type="submit">
			<?php echo esc_html__( 'Complete', 'learnpress' ); ?>
		</button>

		<?php do_action( 'learn-press/lesson/after-complete-button' ); ?>

	</form>
<?php endif; ?>
