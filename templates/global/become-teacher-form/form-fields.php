<?php
/**
 * Template for displaying become teacher form.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$user = learn_press_get_current_user();
?>

<ul class="become-teacher-fields form-fields">

	<?php do_action( 'learnpress/become-a-teacher/before-form' ); ?>

		<li class="form-field">
			<label for="bat_name"><?php esc_html_e( 'Name', 'learnpress' ); ?></label>
			<input type="text" name="bat_name" required placeholder="<?php esc_attr_e( 'Your name', 'learnpress' ); ?>" value="<?php echo isset( $_POST['bat_name'] ) ? wp_unslash( $_POST['bat_name'] ) : $user->get_display_name(); ?>">
		</li>
		<li class="form-field">
			<label for="bat_email"><?php esc_html_e( 'Email', 'learnpress' ); ?></label>
			<input type="email" name="bat_email" required placeholder="<?php esc_attr_e( 'Your email address', 'learnpress' ); ?>" value="<?php echo isset( $_POST['bat_email'] ) ? wp_unslash( $_POST['bat_email'] ) : $user->get_email(); ?>">
		</li>
		<li class="form-field">
			<label for="bat_phone"><?php esc_html_e( 'Phone', 'learnpress' ); ?></label>
			<input type="text" name="bat_phone" placeholder="<?php esc_attr_e( 'Your phone number', 'learnpress' ); ?>" value="<?php echo isset( $_POST['bat_phone'] ) ? wp_unslash( $_POST['bat_phone'] ) : ''; ?>">
		</li>
		<li class="form-field">
			<label for="bat_message"><?php esc_html_e( 'Message', 'learnpress' ); ?></label>
			<textarea name="bat_message" placeholder="<?php esc_attr_e( 'Your message', 'learnpress' ); ?>"><?php echo isset( $_POST['bat_message'] ) ? wp_unslash( $_POST['bat_message'] ) : ''; ?></textarea>
		</li>

	<?php do_action( 'learnpress/become-a-teacher/after-form' ); ?>

</ul>

<input type="hidden" name="request-become-a-teacher-nonce" value="<?php echo wp_create_nonce( 'request-become-a-teacher' ); ?>">
