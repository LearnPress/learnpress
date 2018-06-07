<?php
/**
 * Template for displaying user avatar editor for changing avatar in user profile.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/settings/tabs/avatar.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$profile      = LP_Profile::instance();
$user         = $profile->get_user();
$custom_img   = $user->get_upload_profile_src();
$gravatar_img = $user->get_profile_picture( 'gravatar' );
$thumb_size   = learn_press_get_avatar_thumb_size();
?>

<form name="profile-avatar" method="post" enctype="multipart/form-data">

	<?php
	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/before-profile-avatar-fields', $profile );
	?>

    <div id="lp-user-edit-avatar" class="lp-edit-profile lp-edit-avatar">
        <div class="lp-avatar-preview"
             style="width: <?php echo $thumb_size['width']; ?>px;height: <?php echo $thumb_size['height']; ?>px;">
            <div class="profile-picture profile-avatar-current">
				<?php if ( $custom_img ) { ?>
                    <img src="<?php echo $custom_img; ?>"/>
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

            <div class="lp-avatar-upload-error">
            </div>
        </div>
        <div class="clearfix"></div>
        <p id="lp-avatar-actions">
            <button id="lp-upload-photo" type="button"><?php _e( 'Upload', 'learnpress' ); ?></button>
			<?php if ( $custom_img != '' ): ?>
                <button id="lp-remove-upload-photo"><?php _e( 'Remove', 'learnpress' ); ?></button>
			<?php endif; ?>
        </p>
    </div>

	<?php
	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/after-profile-avatar-fields', $profile );
	?>

    <p>
        <input type="hidden" name="save-profile-avatar"
               value="<?php echo wp_create_nonce( 'learn-press-save-profile-avatar' ); ?>">
    </p>
    <button type="submit" id="submit" name="submit"><?php _e( 'Save changes', 'learnpress' ); ?></button>

</form>

<script type="text/html" id="tmpl-crop-user-avatar">
    <div class="lp-avatar-crop-image" style="width: {{data.viewWidth}}px; height: {{data.viewHeight}}px;">
        <img src="{{data.url}}?r={{data.r}}"/>
        <div class="lp-crop-controls">
            <div class="lp-zoom">
                <div></div>
            </div>
            <a href="" class="lp-cancel-upload dashicons dashicons-no-alt"></a>
        </div>
        <input type="hidden" name="lp-user-avatar-crop[name]" data-name="name" value="{{data.name}}"/>
        <input type="hidden" name="lp-user-avatar-crop[width]" data-name="width" value=""/>
        <input type="hidden" name="lp-user-avatar-crop[height]" data-name="height" value=""/>
        <input type="hidden" name="lp-user-avatar-crop[points]" data-name="points" value=""/>
        <input type="hidden" name="lp-user-avatar-custom" value="yes"/>
    </div>
</script>