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

$default_subject = 'Request to become a teacher';
$default_message = '<strong>Dear Administrator</strong>,

<p>An user want to be a teacher and has requested. Below is the information about requester</p>
';
?>
<table class="form-table">
	<tbody>
	<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	<tr>
		<th scope="row"><label for="lpr_email_enable"><?php _e( 'Enable', 'learnpress' ); ?></label></th>
		<td>
			<input id="lpr_email_enable" type="checkbox" name="lpr_settings[<?php echo $this->id; ?>][enable]" value="1" <?php checked( $settings->get( 'become_a_teacher_request.enable' ), 1 ); ?> />

			<p class="description"><?php _e( 'Send notification when a user want to be a teacher', 'learnpress' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="lpr_email_subject"><?php _e( 'Subject', 'learnpress' ); ?></label></th>
		<td>
			<input id="lpr_email_subject" class="regular-text" type="text" name="lpr_settings[<?php echo $this->id; ?>][subject]" value="<?php echo $settings->get( 'become_a_teacher_request.subject', $default_subject ); ?>" />

			<p class="description"><?php _e( 'Email subject', 'learnpress' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label><?php _e( 'Message', 'learnpress' ); ?></label></th>
		<td>
			<?php $this->message_editor( $default_message ); ?>
		</td>
	</tr>
	<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	</tbody>
</table>