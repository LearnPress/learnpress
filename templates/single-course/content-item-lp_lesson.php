<?php
/**
 * Template for display content of lesson
 *
 * @author  ThimPress
 * @version 2.0.7
 */
global $lp_query, $wp_query;
$user          = learn_press_get_current_user();
$course        = LP()->global['course'];
$item          = LP()->global['course-item'];
$security      = wp_create_nonce( sprintf( 'complete-item-%d-%d-%d', $user->id, $course->id, $item->ID ) );
$can_view_item = $user->can( 'view-item', $item->id, $course->id );

$block_option = get_post_meta( $course->id, '_lp_block_lesson_content', true );
$duration     = $course->get_user_duration_html( $user->id, true );

if ( ! $duration && ( isset( $block_option ) && $block_option == 'yes' ) ) {
	learn_press_get_template( 'content-lesson/block-content.php' );
} else {
	?>
    <h2 class="learn-press-content-item-title">
        <a href="" class="lp-expand dashicons-editor-expand dashicons"></a>
		<?php echo $item->get_title(); ?>
    </h2>
    <div class="learn-press-content-item-summary">

		<?php learn_press_get_template( 'content-lesson/description.php' ); ?>

		<?php if ( $user->has_completed_lesson( $item->ID, $course->id ) ) { ?>
            <button class="" disabled="disabled"> <?php _e( 'Completed', 'learnpress' ); ?></button>
			<?php
			// Auto redirect the next item (lesson or quiz) after completed current lesson
			$auto_next = LP()->settings->get( 'auto_redirect_next_lesson' );
			$message   = LP()->settings->get( 'auto_redirect_message' );
			$time      = 0;

			if ( $auto_next === 'yes' ) {
				if ( LP()->settings->get( 'auto_redirect_time' ) ) {
					$time = LP()->settings->get( 'auto_redirect_time' );
					$time = absint( $time );
				}
				?>
                <div class="learn-press-auto-redirect-next-item"
                     data-time-redirect="<?php echo esc_attr( $time ); ?>">
					<?php
					if ( ! empty( $message ) ) {
						?>
                        <p class="learn-press-message">
							<?php echo wp_kses_post( $message ); ?>
                            <span class="learn-press-countdown"><?php echo wp_kses_post( $time ); ?></span>
                            <span class="learnpress-dismiss-notice"></span>
                        </p>
						<?php
					}
					?>
                </div>
				<?php
			}
			?>
		<?php } else if ( ! $user->has( 'finished-course', $course->id ) && ! in_array( $can_view_item, array(
				'preview',
				'no-required-enroll'
			) )
		) { ?>

            <form method="post" name="learn-press-form-complete-lesson" class="learn-press-form">
                <input type="hidden" name="id" value="<?php echo $item->id; ?>"/>
                <input type="hidden" name="course_id" value="<?php echo $course->id; ?>"/>
                <input type="hidden" name="security" value="<?php echo esc_attr( $security ); ?>"/>
                <input type="hidden" name="type" value="lp_lesson"/>
                <input type="hidden" name="lp-ajax" value="complete-item"/>
                <button class="button-complete-item button-complete-lesson"><?php echo __( 'Complete', 'learnpress' ); ?></button>

            </form>
		<?php } ?>

    </div>
<?php } ?>

<?php LP_Assets::enqueue_script( 'learn-press-course-lesson' ); ?>

<?php LP_Assets::add_var( 'LP_Lesson_Params', wp_json_encode( $item->get_settings( $user->id, $course->id ) ), '__all' ); ?>
