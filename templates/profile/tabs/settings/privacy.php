<?php
/**
 * Template for displaying change password form in profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/settings/tabs/change-password.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$profile = LP_Profile::instance();
$privacy = $profile->get_privacy_settings();
?>

<form method="post" name="profile-privacy" enctype="multipart/form-data" class="learn-press-form">

	<?php do_action( 'learn-press/before-profile-privacy-fields', $profile ); ?>

	<ul class="form-fields">

		<?php
		do_action( 'learn-press/begin-profile-privacy-fields', $profile );

		foreach ( $privacy as $item ) {
			?>
			<li class="form-field">
				<label for="privacy-<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['name'] ); ?></label>
				<div class="form-field-input">
					<input type="hidden" name="privacy[<?php echo esc_attr( $item['id'] ); ?>]" value="no"/>
					<input type="checkbox" name="privacy[<?php echo esc_attr( $item['id'] ); ?>]" value="yes" id="privacy-<?php echo esc_attr( $item['id'] ); ?>" <?php checked( $profile->get_privacy( $item['id'] ), 'yes' ); ?>/>

					<?php if ( ! empty( $item['description'] ) ) : ?>
						<p class="description"><?php echo esc_html( $item['description'] ); ?></p>
					<?php endif; ?>
				</div>
			</li>
			<?php
		}
		?>

		<?php do_action( 'learn-press/end-profile-privacy-fields', $profile ); ?>

	</ul>
	<input type="hidden" name="save-profile-privacy" value="<?php echo wp_create_nonce( 'learn-press-save-profile-privacy' ); ?>"/>

	<?php do_action( 'learn-press/after-profile-privacy-fields', $profile ); ?>

	<button type="submit" name="submit" id="submit"><?php esc_html_e( 'Save changes', 'learnpress' ); ?></button>

</form>
