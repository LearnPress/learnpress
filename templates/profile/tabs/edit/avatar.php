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
		<input type="text" name="lp-user-avatar-crop[name]" data-name="name" value="{{data.name}}" />
		<input type="text" name="lp-user-avatar-crop[width]" data-name="width" value="" />
		<input type="text" name="lp-user-avatar-crop[height]" data-name="height" value="" />
		<input type="text" name="lp-user-avatar-crop[points]" data-name="points" value="" />

	</div>
</script>
<div id="lp-user-edit-avatar" class="lp-edit-profile lp-edit-avatar">
	<div class="lp-avatar-preview">
		<div class="profile-picture profile-avatar-current">
			<?php if ( $custom_img ) { ?>
				<img src="<?php echo $custom_img; ?>" />
				<input type="hidden" name="lp-user-avatar-custom" value="yes" />
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
	<!-- <?php /*
	<div class="user-profile-picture info-field">
		<p class="profile-field-name"><?php _e( 'Profile Picture', 'learnpress' ); ?></p>
		<div id="profile-picture-wrap">
			<div class="profile-picture profile-avatar-current <?php echo $profile_picture_type == 'gravatar' ? 'avatar-picture' : 'avatar-gravatar'; ?>">
				<?php echo $user->get_profile_picture( $profile_picture_type == 'gravatar' ? 'gravatar' : 'picture' ); ?>
			</div>
			<div class="profile-picture profile-avatar-hidden hide-if-js <?php echo $profile_picture_type != 'gravatar' ? 'avatar-picture' : 'avatar-gravatar'; ?>">
				<?php echo $user->get_profile_picture( $profile_picture_type == 'gravatar' ? 'picture' : 'gravatar' ); ?>
			</div>
			<div class="clear"></div>
			<ul id="lp-menu-change-picture">
				<li class="dropdown">
					<span class="lp-label-change-picture"><?php _e( 'Change Picture', 'learnpress' ); ?></span>
					<select name="profile_picture_type" id="lp-profile_picture_type" class="hidden">
						<option value="gravatar" <?php selected( 'gravatar', $profile_picture_type ) ?>><?php _e( 'Gravatar', 'learnpress' ); ?></option>
						<option value="picture" <?php selected( 'picture', $profile_picture_type ) ?>><?php _e( 'Picture', 'learnpress' ); ?></option>
					</select>
					<ul class="dropdown-menu" role="menu">
						<li class="menu-item-use-gravatar<?php echo esc_attr( $class_gravatar_selected ); ?>">
							<span><?php _e( 'Use Gravatar', 'learnpress' ); ?></span></li>
						<li class="menu-item-use-picture<?php echo esc_attr( $class_picture_selected ); ?>">
							<span><?php _e( 'Use Picture', 'learnpress' ); ?></span></li>
						<li class="menu-item-upload-picture">
							<span><?php _e( 'Upload Picture', 'learnpress' ); ?></span></li>
					</ul>

				</li>
			</ul>
		</div>
		<div id="lpbox-upload-crop-profile-picture">
			<input type="hidden" id="lp-user-profile-picture-data" data-current="<?php echo esc_attr( $profile_picture ); ?>" name="profile_picture_data" />
			<div class="lpbox-title"><?php _e( 'Upload Picture', 'learnpress' ); ?></div>
			<p class="description">
				<small><?php _e( 'Please use an image that\'s at least 250px in width, 250px in height and under 2MB in size', 'learnpress' ); ?></small>
			</p>
			<div id="image-editor-wrap">
				<div class="image-editor image-editor-sidebar-left">
					<div class="cropit-preview"></div>
					<div class="image-editor-btn">

						<input type="range" class="cropit-image-zoom-input">
					</div>
				</div>
				<div class="image-editor-sidebar-right">
					<a href="#" id="lp-button-choose-file"><span class="dashicons dashicons-format-image"></span><?php _e( 'Choose File', 'learnpress' ); ?>
					</a>
					<a href="#" id="lp-button-apply-changes"><span class="dashicons dashicons-yes"></span>&nbsp;<?php _e( 'Apply Changes', 'learnpress' ); ?>
					</a>
					<a href="#" id="lp-button-cancel-changes"><span class="dashicons dashicons-no"></span><?php _e( 'Cancel', 'learnpress' ); ?>
					</a>
					<div id="lp-ocupload-picture"></div>
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div> */ ?>-->
</div>
