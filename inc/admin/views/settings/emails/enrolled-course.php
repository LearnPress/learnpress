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

<table class="form-table">
	<tbody>
	<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	<tr>
		<th scope="row">
			<label for="learn-press-emails-enrolled-course-enable"><?php _e( 'Enable', 'learn_press' ); ?></label></th>
		<td>
			<input type="hidden" name="<?php echo $settings_class->get_field_name( 'emails_enrolled_course[enable]' ); ?>" value="no" />
			<input id="learn-press-emails-enrolled-course-enable" type="checkbox" name="<?php echo $settings_class->get_field_name( 'emails_enrolled_course[enable]' ); ?>" value="yes" <?php checked( $settings->get( 'emails_enrolled_course.enable', 'yes' ) == 'yes' ); ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="learn-press-emails-enrolled-course-subject"><?php _e( 'Subject', 'learn_press' ); ?></label></th>
		<td>
			<?php $default = __( '[{site_title}] New course for review ({course_name}) - {course_date}', 'learn_press' ); ?>
			<input id="learn-press-emails-enrolled-course-subject" class="regular-text" type="text" name="<?php echo $settings_class->get_field_name( 'emails_enrolled_course[subject]' ); ?>" value="<?php echo $settings->get( 'emails_enrolled_course.subject', $default ); ?>" />

			<p class="description">
				<?php printf( __( 'Email subject (separated by comma), default: <code>%s</code>', 'learn_press' ), $default ); ?>
			</p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="learn-press-emails-enrolled-course-heading"><?php _e( 'Heading', 'learn_press' ); ?></label></th>
		<td>
			<?php $default = __( 'New course', 'learn_press' ); ?>
			<input id="learn-press-emails-enrolled-course-heading" class="regular-text" type="text" name="<?php echo $settings_class->get_field_name( 'emails_enrolled_course[heading]' ); ?>" value="<?php echo $settings->get( 'emails_enrolled_course.heading', $default ); ?>" />

			<p class="description">
				<?php printf( __( 'Email subject, default: <code>%s</code>', 'learn_press' ), $default ); ?>
			</p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="learn-press-emails-enrolled-course-email-format"><?php _e( 'Email format', 'learn_press' ); ?></label>
		</th>
		<td>
			<?php learn_press_email_formats_dropdown( array( 'name' => $settings_class->get_field_name( 'emails_enrolled_course[email_format]' ), 'id' => 'learn_press_email_formats', 'selected' => $settings->get( 'emails_enrolled_course.email_format', $default ) ) ); ?>
		</td>
	</tr>
	<?php
	$view = learn_press_get_admin_view( 'settings/emails/email-template.php' );
	include_once $view;
	?>
	<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	</tbody>
</table>