<?php
/**
 * Template for displaying user avatar editor for changing avatar in user profile.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/settings/tabs/avatar.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$profile      = LP_Profile::instance();
$user         = $profile->get_user();
$custom_img   = $user->get_upload_profile_src();
$gravatar_img = $user->get_profile_picture( 'gravatar' );
$thumb_size   = learn_press_get_avatar_thumb_size();
?>

<form name="profile-avatar" method="post" enctype="multipart/form-data">

	<?php do_action( 'learn-press/before-profile-avatar-fields', $profile ); ?>

	<div id="lp-user-edit-avatar" class="lp-edit-profile lp-edit-avatar" data-custom="<?php echo $custom_img ? 'yes' : 'no'; ?>">
		<div id="profile-avatar-uploader" style="height: <?php echo $thumb_size['height']; ?>px;">
			<div class="lp-avatar-preview" style="width: <?php echo $thumb_size['width']; ?>px; height: <?php echo $thumb_size['height']; ?>px;">

				<div class="profile-picture profile-avatar-current">
					<?php if ( $custom_img ) : ?>
						<img src="<?php echo $custom_img; ?>"/>
					<?php else : ?>
						<?php echo $gravatar_img; ?>
					<?php endif; ?>
				</div>

				<div class="profile-picture">
					<?php echo $gravatar_img; ?>
				</div>

				<div class="lp-avatar-upload-progress">
					<div class="lp-avatar-upload-progress-value"></div>
				</div>

				<div class="lp-avatar-upload-error"> </div>

				<p id="lp-avatar-actions">
					<a href="" id="lp-upload-photo"><i class="fas fa-upload"></i><?php esc_html_e( 'Upload', 'learnpress' ); ?></a>
					<a href="" id="lp-remove-upload-photo"><i class="fas fa-times"></i><?php esc_html_e( 'Remove', 'learnpress' ); ?></a>
				</p>
			</div>
		</div>
		<div class="clearfix"></div>

	</div>

	<?php do_action( 'learn-press/after-profile-avatar-fields', $profile ); ?>

	<p>
		<input type="hidden" name="save-profile-avatar" value="<?php echo wp_create_nonce( 'learn-press-save-profile-avatar' ); ?>">
	</p>

</form>

<script type="text/html" id="tmpl-crop-user-avatar">
	<div class="lp-avatar-crop-image">
		<div class="crop-container">
			<img src="{{data.url}}?r={{data.r}}"/>
		</div>

		<div class="lp-crop-area" style="width: {{data.viewWidth}}px; height: {{data.viewHeight}}px;"></div>

		<div class="lp-crop-controls">
			<div class="lp-zoom">
				<div></div>
			</div>
			<a href="" class="lp-save-upload">
				<i class="fas fa-check"></i>
			</a>
			<a href="" class="lp-cancel-upload">
				<i class="fas fa-times"></i>
			</a>
		</div>
		<input type="hidden" name="lp-user-avatar-crop[name]" data-name="name" value="{{data.name}}"/>
		<input type="hidden" name="lp-user-avatar-crop[width]" data-name="width" value=""/>
		<input type="hidden" name="lp-user-avatar-crop[height]" data-name="height" value=""/>
		<input type="hidden" name="lp-user-avatar-crop[points]" data-name="points" value=""/>
		<input type="hidden" name="lp-user-avatar-crop[nonce]" value="<?php echo wp_create_nonce( 'save-uploaded-profile-' . $user->get_id() ); ?>"/>
		<input type="hidden" name="lp-user-avatar-custom" value="yes"/>
	</div>
</script>
