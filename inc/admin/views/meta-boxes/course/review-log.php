<?php
global $post;
if( get_post_type() != 'lp_course' ){
	return;
}
if ( !learn_press_course_is_required_review( $post->ID, get_current_user_id() ) ) {
	//return;
}
$user        = learn_press_get_current_user();
$course_user = learn_press_get_user( get_post_field( 'post_author', $post->ID ) );
$required_review       = LP()->settings->get( 'required_review' ) == 'yes';
$enable_edit_published = LP()->settings->get( 'enable_edit_published' ) == 'yes';
?>
<input type="hidden" id="learn-press-course-status" value="<?php echo get_post_status();?>">
<?php
if ( $user->is_instructor() && ( ( get_post() != 'publish' ) ) ) {
	?>
	<div id="learn-press-review-message">
		<h4><?php _e( 'Review message to Reviewer', 'learnpress' ); ?></h4>
		<p>
			<label>
				<input type="checkbox" id="learn-press-notice-check" name="learn_press_submit_course_notice_reviewer" value="yes" />
				<?php _e( 'Message to Reviewer', 'learnpress' ); ?>
			</label>
		</p>
		<div class="hide-if-js">
			<textarea class="widefat" rows="5" disabled="disabled" name="review_message" resize="none" placeholder="<?php _e( 'Enter some information here for reviewer', 'learnpress' ); ?>"></textarea>

		</div>
		<?php if( $required_review && !$enable_edit_published ){?>
		<p class="description submitdelete">
			<?php _e( 'Warning! Your course will become Pending Review for admins to review before it can be published when you update.' ); ?>
		</p>
		<?php } ?>
	</div>
	<?php ob_start(); ?>
	<script type="text/javascript">
		jQuery('#post').submit(function (e) {
			var $review = $('textarea[name="review_message"]');
			if (!($review.val() + '').length && $('#learn-press-notice-check').is(':checked')) {
				alert('<?php _e( 'Please write your message to the Reviewer', 'learnpress' );?>');
				$review.focus();
				return false;
			}
		});
		jQuery('#learn-press-notice-check').change(function(){
			var checked = this.checked,
				$review = jQuery('textarea[name="review_message"]').prop('disabled', !checked),
				$parent = $review.parent();
			$parent[checked ? 'slideDown' : 'slideUp'](function(){
				checked && $review.focus();
			});
		});
	</script>
	<?php learn_press_enqueue_script( strip_tags( ob_get_clean() ) ); ?>
	<?php
} else if ( $user->is_admin() && !$course_user->is_admin() ) {
	?>
	<div id="learn-press-review-message">
		<h4><?php _e( 'Review message to Instructor', 'learnpress' ); ?></h4>
		<p>
			<label>
				<input type="checkbox" id="learn-press-notice-check" name="learn_press_submit_course_notice_instructor" value="yes"/>
				<?php _e( 'Message to Instructor', 'learnpress' ); ?>
			</label>
		</p>
		<div class="hide-if-js">
		<textarea class="widefat" rows="5" disabled="disabled" name="review_message" resize="none" placeholder="<?php _e( 'Enter some information here for instructors. E.g: the reason why the course is rejected etc...', 'learnpress' ); ?>"></textarea>
		</div>
	</div>
	<?php ob_start(); ?>
	<script type="text/javascript">
		jQuery('#post').submit(function (e) {
			var $review = $('textarea[name="review_message"]', this),
				status = $('select#post_status', this).val(),
				current_status = $('#learn-press-course-status').val(),
				clicked = $(':focus', this).attr('name');

			if ( ( ( clicked == 'save' || clicked == 'publish' ) && ( status != current_status ) || ( clicked == 'publish' ) && ( status == 'pending' ) )&& !($review.val() + '').length) {
				alert('<?php _e( 'Please write your message to the Instructor', 'learnpress' );?>');
				var $check = $('input[name="learn_press_submit_course_notice_instructor"]').prop('checked', true);
				$check.trigger('change');
				return false;
			}else{
			}
		});
		jQuery('#learn-press-notice-check').change(function(){
			var that = this,
				$review = jQuery('textarea[name="review_message"]').prop('disabled', !this.checked),
				$parent = $review.parent();
			$parent[this.checked ? 'slideDown' : 'slideUp'](function(){
				that.checked && $review.focus();
			});
		});
	</script>
	<?php learn_press_enqueue_script( strip_tags( ob_get_clean() ) ); ?>
	<?php
}