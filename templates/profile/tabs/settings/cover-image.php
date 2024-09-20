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
?>
<!-- <link href="https://unpkg.com/cropperjs/dist/cropper.min.css" rel="stylesheet"> -->
<div id="lp-cover-image-upload">
	<div class="lp-cover-image-wrapper">
		<img src="" id="lp-cover-image" />
	</div>
	<div class="lp-upload-button-wrapper">
		<input id="lp-cover-image-file" type="file" name="lp-cover-image" hidden />
		<button id="lp-upload-cover-image" class="lp-button"><?php esc_html_e( 'Upload', 'learnpress' ); ?></button>
	</div>
</div>