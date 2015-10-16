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

$default_subject = 'Course Registration';
$default_message = '<strong>Dear {user_name}</strong>,

<p>You have been enrolled in <a href="{course_link}">{course_name}</a>.</p>
<p>Visit our website at {log_in}.</p>

<p>Best regards,</p>
<em>Administration</em>';
?>

<table class="form-table">
	<tbody>
	<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	<tr>
		<th scope="row"><label for="lpr_email_enable"><?php _e( 'Enable', 'learn_press' ); ?></label></th>
		<td>
			<input id="lpr_email_enable" type="checkbox" name="lpr_settings[<?php echo $this->id; ?>][enable]" value="1" <?php checked( $settings->get( 'enrolled_course.enable' ), 1 ); ?> />

			<p class="description"><?php _e( 'Send notification for users when they enrolled a course', 'learn_press' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="lpr_email_subject"><?php _e( 'Subject', 'learn_press' ); ?></label></th>
		<td>
			<input id="lpr_email_subject" class="regular-text" type="text" name="lpr_settings[<?php echo $this->id; ?>][subject]" value="<?php echo $settings->get( 'enrolled_course.subject', $default_subject ); ?>" />

			<p class="description"><?php _e( 'Email subject', 'learn_press' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label><?php _e( 'Message', 'learn_press' ); ?></label></th>
		<td>
			<?php $this->message_editor( $default_message ); ?>
			<p class="description"><?php _e( 'Placeholders', 'learn_press' ); ?>: <?php echo apply_filters( 'learn_press_placeholders_' . $this->section['id'], '{log_in}, {user_name}, {course_name}, {course_link}' ) ?></p>

		</td>
	</tr>
	<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	</tbody>
</table>
<?php