<?php
global $post;
if ( get_post_type() != 'lp_course' ) {
	return;
}
if ( !learn_press_course_is_required_review( $post->ID, get_current_user_id() ) ) {
	//return;
}
$user                  = learn_press_get_current_user();
$course_user           = learn_press_get_user( get_post_field( 'post_author', $post->ID ) );
$required_review       = LP()->settings->get( 'required_review' ) == 'yes';
$enable_edit_published = LP()->settings->get( 'enable_edit_published' ) == 'yes';
$pending_review        = 'yes' == get_post_meta( $post->ID, '_lp_submit_for_reviewer', true );
?>
<div id="learn-press-review-message">
	<input type="hidden" id="learn-press-course-status" value="<?php echo get_post_status(); ?>" />
	<input type="hidden" name="learn-press-course-pending-review" value="<?php echo $pending_review ? 'yes' : 'no'; ?>" />
	<?php if ( $user->is_instructor() ): ?>
		<?php if ( $pending_review ) { ?>
			<p class="lp-pending-review-message"><?php _e( 'Your course is pending for reviewing', 'learnpress' ); ?></p>
		<?php } else { ?>
			<textarea class="widefat" rows="5" disabled="disabled" name="review-message" resize="none" placeholder="<?php _e( 'Enter some information here to Reviewer', 'learnpress' ); ?>"></textarea>
			<?php if ( $required_review && !$enable_edit_published ) { ?>
				<p class="description submitdelete">
					<?php _e( 'Warning! Your course will become Pending Review for admins to review before it can be published when you update.' ); ?>
				</p>
			<?php } ?>

		<?php } ?>

	<?php elseif ( $user->is_admin() && !$course_user->is_admin() ): ?>

		<?php if ( $pending_review ) { ?>
			<p class="lp-pending-review-message"><?php _e( 'This course is pending for reviewing', 'learnpress' ); ?></p>
		<?php } ?>
		<!--
				<p>
			<label>
				<input type="checkbox" id="learn-press-notice-check" name="learn_press_submit_course_notice_instructor" value="yes" />
				<?php _e( 'Message to Instructor', 'learnpress' ); ?>
			</label>
		</p>-->
		<textarea class="widefat" rows="5" name="review-message" resize="none" placeholder="<?php _e( 'Enter some information here for instructors. E.g: the reason why the course is rejected etc...', 'learnpress' ); ?>"></textarea>
	<?php endif; ?>
</div>

