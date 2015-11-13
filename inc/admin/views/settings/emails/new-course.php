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
<h3><?php _e( 'New course for review', 'learn_press' ); ?></h3>
<p class="description">
	<?php _e( 'Settings for email when a new course submit for review', 'learn_press' );?>
</p>
<table class="form-table">
	<tbody>
	<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	<tr>
		<th scope="row"><label for="learn-press-emails-new-course-enable"><?php _e( 'Enable', 'learn_press' ); ?></label></th>
		<td>
			<input type="hidden" name="<?php echo $this->get_field_name('emails_new_course[enable]');?>" value="no" />
			<input id="learn-press-emails-new-course-enable" type="checkbox" name="<?php echo $this->get_field_name('emails_new_course[enable]');?>" value="yes" <?php checked( $settings->get( 'emails_new_course.enable', 'yes' ) == 'yes' ); ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="learn-press-emails-new-course-recipients"><?php _e( 'Recipient(s)', 'learn_press' ); ?></label></th>
		<td>
			<?php $default = get_option( 'admin_email' );?>
			<input id="learn-press-emails-new-course-recipients" class="regular-text" type="text" name="<?php echo $this->get_field_name( 'emails_new_course[recipients]');?>" value="<?php echo $settings->get( 'emails_new_course.recipients', $default ); ?>" />
			<p class="description">
				<?php printf( __( 'Email recipient(s), default: <code>%s</code>', 'learn_press' ),  $default );?>
			</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="learn-press-emails-new-course-subject"><?php _e( 'Subject', 'learn_press' ); ?></label></th>
		<td>
			<?php $default = __( '[{site_title}] New course for review ({course_name}) - {course_date}', 'learn_press' );?>
			<input id="learn-press-emails-new-course-subject" class="regular-text" type="text" name="<?php echo $this->get_field_name( 'emails_new_course[subject]');?>" value="<?php echo $settings->get( 'emails_new_course.subject', $default ); ?>" />
			<p class="description">
				<?php printf( __( 'Email subject (separated by comma), default: <code>%s</code>', 'learn_press' ), $default );?>
			</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="learn-press-emails-new-course-heading"><?php _e( 'Heading', 'learn_press' ); ?></label></th>
		<td>
			<?php $default = __( 'New course', 'learn_press' );?>
			<input id="learn-press-emails-new-course-heading" class="regular-text" type="text" name="<?php echo $this->get_field_name( 'emails_new_course[heading]');?>" value="<?php echo $settings->get( 'emails_new_course.heading', $default ); ?>" />
			<p class="description">
				<?php printf( __( 'Email subject, default: <code>%s</code>', 'learn_press' ), $default );?>
			</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="learn-press-emails-new-course-email-format"><?php _e( 'Email format', 'learn_press' ); ?></label></th>
		<td>
			<?php learn_press_email_formats_dropdown( array( 'name' => $this->get_field_name( 'emails_new_course[email_format]'), 'id' => '', 'selected' => $settings->get( 'emails_new_course.email_format', $default ) ) );?>
		</td>
	</tr>
	<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	</tbody>
</table>