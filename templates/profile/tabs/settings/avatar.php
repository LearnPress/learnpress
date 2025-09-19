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
if ( ! $profile ) {
	return;
}
$image_url = $profile->get_upload_profile_src();
?>

<div id="learnpress-avatar-upload">
	<div class="learnpress_avatar">
		<div class="learnpress_avatar__cropper <?php echo esc_attr( $image_url ? '' : 'lp-hidden' ); ?>">
			<img class="learnpress-avatar-image" src="<?php echo esc_attr( $image_url ); ?>" alt="">
			<div>
				<button class="learnpress_avatar__button lp-button learnpress_avatar__button--replace"><?php esc_html_e( 'Replace', 'learnpress' ); ?></button>
				<button class="learnpress_avatar__button lp-button learnpress_avatar__button--remove "><?php esc_html_e( 'Remove', 'learnpress' ); ?></button>
				<button class="learnpress_avatar__button lp-button learnpress_avatar__button--save <?php echo esc_attr( $image_url ? 'lp-hidden' : '' ); ?>"><?php esc_html_e( 'Save', 'learnpress' ); ?></button>
				<button class="learnpress_avatar__button lp-button learnpress_avatar__button--cancel <?php echo esc_attr( $image_url ? 'lp-hidden' : '' ); ?>"><?php esc_html_e( 'Cancel', 'learnpress' ); ?></button>
			</div>
		</div>
		<form class="learnpress-avatar-form <?php echo esc_attr( $image_url ? 'lp-hidden' : '' ); ?>">
			<div class="learnpress_avatar__form">
				<div class="learnpress_avatar__form-group">
					<label for="avatar-file">
						<div class="learnpress_avatar__form__upload">
							<div><span><svg viewBox="64 64 896 896" focusable="false" data-icon="plus" width="1em" height="1em" fill="currentColor" aria-hidden="true">
								<path d="M482 152h60q8 0 8 8v704q0 8-8 8h-60q-8 0-8-8V160q0-8 8-8z"></path>
								<path d="M176 474h672q8 0 8 8v60q0 8-8 8H176q-8 0-8-8v-60q0-8 8-8z"></path>
							</svg></span>
							<div><?php esc_html_e( 'Upload', 'learnpress' ); ?></div>
						</div>
					</div>
					<input type="file" id="avatar-file" accept="image/*">
				</label>
			</div>
			</div>
		</form>
	</div>
</div>
