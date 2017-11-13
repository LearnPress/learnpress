<?php
/**
 * Display general settings for emails
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views/Emails
 * @version 1.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$settings = LP()->settings;
?>
<h3><?php _e( 'Email Options', 'learnpress' ); ?></h3>
<p class="description">
	<?php _e( 'The following options affect the sender (email address and name) used in LearnPress emails.', 'learnpress' );?>
</p>
<table class="form-table">
	<tbody>
	<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	<tr>
		<th scope="row"><label for="learn-press-emails-general-from-name"><?php _e( 'From Name', 'learnpress' ); ?></label></th>
		<td>
			<input class="regular-text" id="learn-press-emails-general-from-name" type="text" name="<?php echo $this->get_field_name('emails_general[from_name]');?>" value="<?php echo $settings->get( 'emails_general.from_name', get_option( 'blogname' ) ); ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="learn-press-emails-general-from-email"><?php _e( 'From Email', 'learnpress' ); ?></label></th>
		<td>
			<input id="learn-press-emails-general-from-email" class="regular-text" type="email" name="<?php echo $this->get_field_name( 'emails_general[from_email]');?>" value="<?php echo $settings->get( 'emails_general.from_email', get_option( 'admin_email' ) ); ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row" colspan="2">
			<h3><?php _e( 'Email Template', 'learnpress' );?></h3>
		</th>
	</tr>
	<tr>
		<th scope="row"><label for="learn-press-emails-general-header-image"><?php _e( 'Header image', 'learnpress' ); ?></label></th>
		<td>
			<input id="learn-press-emails-general-header-image" class="regular-text" type="text" name="<?php echo $this->get_field_name( 'emails_general[header_image]');?>" value="<?php echo $settings->get( 'emails_general.header_image' ); ?>" />
			<p class="description"><?php _e( 'The image will be displayed in the top of the email.', 'learnpress' );?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="learn-press-emails-general-footer-text"><?php _e( 'Footer text', 'learnpress' ); ?></label></th>
		<td>
			<textarea id="learn-press-emails-general-footer-text" name="<?php echo $this->get_field_name( 'emails_general[footer_text]');?>" style="height: 100px; width: 100%;"><?php echo $settings->get( 'emails_general.footer_text' ); ?></textarea>
			<p class="description"><?php _e( 'The text display in the bottom of email', 'learnpress' );?></p>
		</td>
	</tr>
	<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	</tbody>
</table>