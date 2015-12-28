<?php
global $post;
if ( !learn_press_course_is_required_review( $post->ID, get_current_user_id() ) ) {
//return;
}
$user        = learn_press_get_current_user();
$course_user = learn_press_get_user( get_post_field( 'post_author', $post->ID ) );
if ( $user->is_instructor() ) {
	?>
	<div id="learn-press-review-message">
		<h4><?php _e( 'Your message to Reviewer', 'learn_press' ); ?></h4>
		<textarea disabled="disabled" name="review_message" resize="none" placeholder="<?php _e( 'Enter some information here for reviewer', 'learn_press' ); ?>"></textarea>
		<p>
			<label>
				<input type="checkbox" id="learn-press-notice-check" />
				<?php _e( 'Notice to the admin for reviewing', 'learn_press' ); ?>
			</label>
		</p>
		<p class="description submitdelete">
			<?php _e( 'Warning! Your course will become to Pending Review for admin reviews before it can be published when you update' ); ?>
		</p>
	</div>
	<?php ob_start(); ?>
	<script type="text/javascript">
		jQuery('#post').submit(function (e) {
			var $review = $('textarea[name="review_message"]');
			if (!($review.val() + '').length) {
				alert('<?php _e( 'Please write your message to Reviewer', 'learn_press' );?>');
				$review.focus();
				return false;
			}
		});
		jQuery('#learn-press-notice-check').click(function(){
			var $review = jQuery('textarea[name="review_message"]').prop('disabled', !this.checked);
			this.checked && $review.focus();
		});
	</script>
	<?php learn_press_enqueue_script( strip_tags( ob_get_clean() ) ); ?>
	<?php
} else if ( $user->is_admin() && !$course_user->is_admin() ) {
	?>
	<div id="learn-press-review-message">
		<h4><?php _e( 'Your message to Instructor', 'learn_press' ); ?></h4>
		<textarea disabled="disabled" name="review_message" resize="none" placeholder="<?php _e( 'Enter some information here for instructor. E.g: for reason why the course is rejected etc...', 'learn_press' ); ?>"></textarea>
		<p>
			<label>
				<input type="checkbox" id="learn-press-notice-check" />
				<?php _e( 'Notice to the instructor for changing', 'learn_press' ); ?>
			</label>
		</p>
	</div>
	<?php ob_start(); ?>
	<script type="text/javascript">
		jQuery('#post').submit(function (e) {
			var $review = $('textarea[name="review_message"]', this),
				$status = $('select#post_status', this),
				clicked = $(':focus', this).attr('name');
			if (clicked == 'save' && $status.val() != 'publish' && !($review.val() + '').length) {
				alert('<?php _e( 'Please write your message to Instructor', 'learn_press' );?>');
				$review.focus();
				return false;
			}
		});
		jQuery('#learn-press-notice-check').click(function(){
			var $review = jQuery('textarea[name="review_message"]').prop('disabled', !this.checked);
			this.checked && $review.focus();
		});
	</script>
	<?php learn_press_enqueue_script( strip_tags( ob_get_clean() ) ); ?>
	<?php
}