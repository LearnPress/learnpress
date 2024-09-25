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
$profile   = LP_Profile::instance();
$image_url = $profile->get_cover_image_src();
?>
<div id="lp-cover-image-upload" class="learnpress-page">
	<div class="lp-cover-image-wrapper">
		<img src="<?php esc_attr_e( $image_url ); ?>" id="lp-cover-image" />
	</div>
	<div class="lp-upload-button-wrapper">
		<input id="lp-cover-image-file" type="file" name="lp-cover-image" accept="image/png, image/jpeg, image/webp" hidden />
		<button id="lp-upload-cover-image" class="lp-button"><?php esc_html_e( 'Upload', 'learnpress' ); ?></button>
		<button id="lp-save-cover-image" class="lp-button"><?php esc_html_e( 'Save', 'learnpress' ); ?></button>
	</div>
</div>