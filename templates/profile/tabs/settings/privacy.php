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
$privacy = $profile->get_privacy_settings();
?>

<form method="post" name="profile-privacy" enctype="multipart/form-data" class="learn-press-form">

	<?php
	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/before-profile-privacy-fields', $profile ); ?>

    <ul class="form-fields">

		<?php
		/**
		 * @since 3.0.0
		 */
		do_action( 'learn-press/begin-profile-privacy-fields', $profile );

		foreach ( $privacy as $item ) {
			?>
            <li class="form-field">
                <label for="privacy-<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['name'] ); ?></label>
                <div class="form-field-input">
                    <input type="hidden" name="privacy[<?php echo esc_attr( $item['id'] ); ?>]" value="no"/>
                    <input type="checkbox" name="privacy[<?php echo esc_attr( $item['id'] ); ?>]" value="yes"
                           id="privacy-<?php echo esc_attr( $item['id'] ); ?>" <?php checked( $profile->get_privacy( $item['id'] ), 'yes' ); ?>/>

					<?php if ( ! empty( $item['description'] ) ) { ?>
                        <p class="description"><?php echo esc_html( $item['description'] ); ?></p>
					<?php } ?>
                </div>
            </li>
			<?php
		}
		?>

        <!--        <li class="form-field">-->
        <!--            <label for="my-courses">--><?php //_e( 'My courses', 'learnpress' ); ?><!--</label>-->
        <!--            <div class="form-field-input">-->
        <!--                <input type="checkbox" name="privacy[courses]" value="yes"-->
        <!--                       id="my-courses" -->
		<?php //checked( $profile->get_privacy( 'courses' ), 'yes' ); ?><!--/>-->
        <!--                <p class="description">-->
		<?php //_e( 'Public your profile courses', 'learnpress' ); ?><!--</p>-->
        <!--            </div>-->
        <!--        </li>-->
        <!---->
        <!--        <li class="form-field">-->
        <!--            <label for="my-quizzes">--><?php //_e( 'My quizzes', 'learnpress' ); ?><!--</label>-->
        <!--            <div class="form-field-input">-->
        <!--                <input name="privacy[quizzes]" value="yes" type="checkbox"-->
        <!--                       id="my-quizzes" -->
		<?php //checked( $profile->get_privacy( 'quizzes' ), 'yes' ); ?><!--/>-->
        <!--                <p class="description">-->
		<?php //_e( 'Public your profile quizzes', 'learnpress' ); ?><!--</p>-->
        <!--            </div>-->
        <!--        </li>-->

		<?php
		/**
		 * @since 3.0.0
		 */
		do_action( 'learn-press/end-profile-privacy-fields', $profile );

		?>

    </ul>
    <input type="hidden" name="save-profile-privacy"
           value="<?php echo wp_create_nonce( 'learn-press-save-profile-privacy' ); ?>"/>
	<?php
	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/after-profile-privacy-fields', $profile );
	?>

    <button type="submit" name="submit" id="submit"><?php _e( 'Save changes', 'learnpress' ); ?></button>

</form>