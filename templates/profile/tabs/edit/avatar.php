<?php
/**
 * User avatar
 *
 * @package LearnPress/Templates
 * @author  ThimPress
 * @version 2.1.0
 */
$user         = learn_press_get_current_user();
$custom_img   = $user->get_upload_profile_src();
$gravatar_img = $user->get_profile_picture( 'gravatar' );

?>
<h3>
	<?php _e( 'Add a nice photo of yourself for your profile.', 'learnpress' ); ?>
</h3>
<script type="text/html" id="tmpl-crop-user-avatar">
	<div class="lp-avatar-crop-image" style="width: {{data.viewWidth}}px; height: {{data.viewHeight}}px;">
		<img src="{{data.url}}?r={{data.r}}" />
		<div class="lp-crop-controls">
			<div class="lp-zoom">
				<div />
			</div>
			<a href="" class="lp-cancel-upload dashicons dashicons-no-alt"></a>
		</div>
		<input type="hidden" name="lp-user-avatar-crop[name]" data-name="name" value="{{data.name}}" />
		<input type="hidden" name="lp-user-avatar-crop[width]" data-name="width" value="" />
		<input type="hidden" name="lp-user-avatar-crop[height]" data-name="height" value="" />
		<input type="hidden" name="lp-user-avatar-crop[points]" data-name="points" value="" />
		<input type="hidden" name="lp-user-avatar-custom" value="yes" />
	</div>
</script>
<div id="lp-user-edit-avatar" class="lp-edit-profile lp-edit-avatar">
	<div class="lp-avatar-preview">
		<div class="profile-picture profile-avatar-current">
			<?php if ( $custom_img ) { ?>
				<img src="<?php echo $custom_img; ?>" />
			<?php } else { ?>
				<?php echo $gravatar_img; ?>
			<?php } ?>
		</div>
		<?php if ( $custom_img ) { ?>
			<div class="profile-picture profile-avatar-hidden">
				<?php echo $gravatar_img; ?>
			</div>
		<?php } ?>

		<div class="lp-avatar-upload-progress">
			<div class="lp-avatar-upload-progress-value"></div>
		</div>

		<div class="lp-avatar-preview-actions">
			<a href="" id="lp-upload-photo"><?php _e( 'Upload', 'learnpress' ); ?></a>
			<?php if ( $user->profile_picture != '' ): ?>
				<a href="" id="lp-remove-upload-photo"><?php _e( 'Remove', 'learnpress' ); ?></a>
			<?php endif; ?>
		</div>

		<div class="lp-avatar-upload-error">
		</div>
	</div>
</div>
