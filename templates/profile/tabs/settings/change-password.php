<?php
/**
 * Template for displaying change password form in profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/settings/tabs/change-password.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$profile = LP_Profile::instance();
if ( ! isset( $section ) ) {
	$section = 'change-password';
} ?>

<form method="post" name="profile-change-password" enctype="multipart/form-data" class="learn-press-form">

	<?php
	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/before-profile-change-password-fields', $profile ); ?>

    <ul class="form-fields">

		<?php
		// @deprecated
		do_action( 'learn_press_before_' . $section . '_edit_fields' );

		/**
		 * @since 3.0.0
		 */
		do_action( 'learn-press/begin-profile-change-password-fields', $profile );
		?>

        <li class="form-field">
            <label for="pass0"><?php _e( 'Old password', 'learnpress' ); ?></label>
            <div class="form-field-input">
                <input type="password" id="pass0" name="pass0" autocomplete="off" class="regular-text"/>
            </div>
        </li>
        <li class="form-field">
            <label for="pass1"><?php _e( 'New password', 'learnpress' ); ?></label>
            <div class="form-field-input">
                <input type="password" name="pass1" id="pass1" class="regular-text" value=""/>
            </div>
        </li>
        <li class="form-field">
            <label for="pass2"><?php _e( 'Confirmation password', 'learnpress' ); ?></label>
            <div class="form-field-input">
                <input name="pass2" type="password" id="pass2" class="regular-text" value=""/>
                <p id="lp-password-not-match"
                   class="description lp-field-error-message hide-if-js"><?php _e( 'New password does not match!', 'learnpress' ); ?></p>
            </div>
        </li>

		<?php
		/**
		 * @since 3.0.0
		 */
		do_action( 'learn-press/end-profile-change-password-fields', $profile );

		// @deprecated
		do_action( 'learn_press_after_' . $section . '_edit_fields' );
		?>

    </ul>

	<?php
	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/after-profile-change-password-fields', $profile );
	?>

    <p>
        <input type="hidden" name="save-profile-password"
               value="<?php echo wp_create_nonce( 'learn-press-save-profile-password' ); ?>">
    </p>

    <button type="submit" name="submit" id="submit"><?php _e( 'Save changes', 'learnpress' ); ?></button>

</form>