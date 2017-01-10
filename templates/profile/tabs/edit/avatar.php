<?php
/**
 * User avatar
 *
 * @package LearnPress/Templates
 * @author  ThimPress
 * @version 2.1.1
 */
$user         = learn_press_get_current_user();
$custom_img   = $user->get_upload_profile_src();
$gravatar_img = $user->get_profile_picture( 'gravatar' );
$thumb_size   = LP()->settings->get( 'profile_picture_thumbnail_size' );
if ( !is_array( $thumb_size ) ) {
	$thumb_size = array( 'width' => 200, 'height' => 200 );
}
?>
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
	<ul class="lp-form-field-wrap">
		<li class="lp-form-field">
			<div class="lp-form-field-input">
				<div class="lp-avatar-preview" style="width: <?php echo $thumb_size['width']; ?>px;height: <?php echo $thumb_size['height']; ?>px;">
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
						<?php if ( $custom_img != '' ): ?>
							<a href="" id="lp-remove-upload-photo"><?php _e( 'Remove', 'learnpress' ); ?></a>
						<?php endif; ?>
					</div>

					<div class="lp-avatar-upload-error">
					</div>
				</div>
				<div class="clearfix"></div>
				<p class="description"><?php _e( 'Hover on your current avatar to upload new photo', 'learnpress' ); ?></p>
			</div>
		</li>
	</ul>
</div>
