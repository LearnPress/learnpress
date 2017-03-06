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
<h3><?php _e( 'Update course', 'learnpress' ); ?></h3>
<p class="description">
	<?php _e( 'Send this email to users have purchased when the course is updated.', 'learnpress' ); ?>
</p>
<table class="form-table">
	<tbody>
	<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	<tr>
		<th scope="row">
			<label for="learn-press-emails-updated-course-subject"><?php _e( 'Subject', 'learnpress' ); ?></label></th>
		<td>
			<input id="learn-press-emails-updated-course-subject" class="regular-text" type="text" name="<?php echo $settings_class->get_field_name( 'emails_update_course[subject]' ); ?>" value="<?php echo $settings->get( 'emails_update_course.subject', $this->default_subject ); ?>" />

			<p class="description">
				<?php printf( __( 'Email subject , default: <code>%s</code>', 'learnpress' ), $this->default_subject ); ?>
			</p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="learn-press-emails-updated-course-heading"><?php _e( 'Heading', 'learnpress' ); ?></label></th>
		<td>
			<input id="learn-press-emails-updated-course-heading" class="regular-text" type="text" name="<?php echo $settings_class->get_field_name( 'emails_update_course[heading]' ); ?>" value="<?php echo $settings->get( 'emails_update_course.heading', $this->default_heading ); ?>" />

			<p class="description">
				<?php printf( __( 'Email heading, default: <code>%s</code>', 'learnpress' ), $this->default_heading ); ?>
			</p>
		</td>
	</tr>
	<?php
	$view = learn_press_get_admin_view( 'settings/emails/email-template.php' );
	include_once $view;
	?>
	<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	</tbody>
</table>