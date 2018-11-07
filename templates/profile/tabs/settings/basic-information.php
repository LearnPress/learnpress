<?php
/**
 * Template for displaying editing basic information form of user in profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/settings/tabs/basic-information.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$profile = LP_Profile::instance();

if ( ! isset( $section ) ) {
	$section = 'basic-information';
}

$user = $profile->get_user();
?>

<form method="post" id="learn-press-profile-basic-information" name="profile-basic-information"
      enctype="multipart/form-data" class="learn-press-form">

	<?php
	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/before-profile-basic-information-fields', $profile );

	?>
    <ul class="form-fields">

		<?php
		/**
		 * @since 3.0.0
		 */
		do_action( 'learn-press/begin-profile-basic-information-fields', $profile );

		// @deprecated
		do_action( 'learn_press_before_' . $section . '_edit_fields' );
		?>

        <li class="form-field">
            <label for="description"><?php _e( 'Biographical Info', 'learnpress' ); ?></label>
            <div class="form-field-input">
                <textarea name="description" id="description" rows="5"
                          cols="30"><?php esc_html_e( $user->get_data( 'description' ) ); ?></textarea>
                <p class="description"><?php _e( 'Share a little biographical information to fill out your profile. This may be shown publicly.', 'learnpress' ); ?></p>
            </div>
        </li>
        <li class="form-field">
            <label for="first_name"><?php _e( 'First Name', 'learnpress' ); ?></label>
            <div class="form-field-input">
                <input type="text" name="first_name" id="first_name"
                       value="<?php echo esc_attr( $user->get_data( 'first_name' ) ); ?>"
                       class="regular-text">
            </div>
        </li>
        <li class="form-field">
            <label for="last_name"><?php _e( 'Last Name', 'learnpress' ); ?></label>
            <div class="form-field-input">
                <input type="text" name="last_name" id="last_name"
                       value="<?php echo esc_attr( $user->get_data( 'last_name' ) ); ?>"
                       class="regular-text">
            </div>
        </li>
        <li class="form-field">
            <label for="nickname"><?php _e( 'Nickname', 'learnpress' ); ?></label>
            <div class="form-field-input">
                <input type="text" name="nickname" id="nickname"
                       value="<?php echo esc_attr( $user->get_data( 'nickname' ) ) ?>"
                       class="regular-text"/>
            </div>
        </li>
        <li class="form-field">
            <label for="display_name"><?php _e( 'Display name publicly as', 'learnpress' ); ?></label>
            <div class="form-field-input">
				<?php learn_press_profile_list_display_names(); ?>
            </div>
        </li>

		<?php
		// @deprecated
		do_action( 'learn_press_after_' . $section . '_edit_fields' );

		/**
		 * @since 3.0.0
		 */
		do_action( 'learn-press/end-profile-basic-information-fields', $profile );

		?>
    </ul>

	<?php
	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/after-profile-basic-information-fields', $profile );
	?>

    <p>
        <input type="hidden" name="save-profile-basic-information"
               value="<?php echo wp_create_nonce( 'learn-press-save-profile-basic-information' ); ?>"/>
    </p>

    <button type="submit" name="submit"><?php _e( 'Save changes', 'learnpress' ); ?></button>

</form>