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
<h3><?php _e( 'Email Options', 'learn_press' ); ?></h3>
<p class="description">
	<?php _e( 'The following options affect the sender (email address and name) used in LearnPress emails.', 'learn_press' );?>
</p>
<table class="form-table">
	<tbody>
	<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	<tr>
		<th scope="row"><label for="learn-press-emails-general-from-name"><?php _e( 'From Name', 'learn_press' ); ?></label></th>
		<td>
			<input class="regular-text" id="learn-press-emails-general-from-name" type="text" name="<?php echo $this->get_field_name('emails_general[from_name]');?>" value="<?php echo $settings->get( 'emails_general.from_name', get_option( 'blogname' ) ); ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="learn-press-emails-general-from-email"><?php _e( 'From Email', 'learn_press' ); ?></label></th>
		<td>
			<input id="learn-press-emails-general-from-email" class="regular-text" type="email" name="<?php echo $this->get_field_name( 'emails_general[from_email]');?>" value="<?php echo $settings->get( 'emails_general.from_email', get_option( 'admin_email' ) ); ?>" />
		</td>
	</tr>
	<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	</tbody>
</table>