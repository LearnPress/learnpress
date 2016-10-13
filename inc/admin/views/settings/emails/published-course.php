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
<h3><?php echo $this->title;?></h3>
<table class="form-table">
	<tbody>
	<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	<tr>
		<th scope="row">
			<label for="learn-press-emails-published-course-enable"><?php _e( 'Enable', 'learnpress' ); ?></label></th>
		<td>
			<input type="hidden" name="<?php echo $settings_class->get_field_name( 'emails_published_course[enable]' ); ?>" value="no" />
			<input id="learn-press-emails-published-course-enable" type="checkbox" name="<?php echo $settings_class->get_field_name( 'emails_published_course[enable]' ); ?>" value="yes" <?php checked( $settings->get( 'emails_published_course.enable', 'yes' ) == 'yes' ); ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="learn-press-emails-published-course-subject"><?php _e( 'Subject', 'learnpress' ); ?></label></th>
		<td>
			<input id="learn-press-emails-published-course-subject" class="regular-text" type="text" name="<?php echo $settings_class->get_field_name( 'emails_published_course[subject]' ); ?>" value="<?php echo $settings->get( 'emails_published_course.subject', $this->default_subject ); ?>" />

			<p class="description">
				<?php printf( __( 'Email subject, default: <code>%s</code>', 'learnpress' ), $this->default_subject ); ?>
			</p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="learn-press-emails-published-course-heading"><?php _e( 'Heading', 'learnpress' ); ?></label></th>
		<td>
			<input id="learn-press-emails-published-course-heading" class="regular-text" type="text" name="<?php echo $settings_class->get_field_name( 'emails_published_course[heading]' ); ?>" value="<?php echo $settings->get( 'emails_published_course.heading', $this->default_heading ); ?>" />

			<p class="description">
				<?php printf( __( 'Email heading, default: <code>%s</code>', 'learnpress' ), $this->default_heading ); ?>
			</p>
		</td>
	</tr>
	<!--<tr>
		<th scope="row">
			<label for="learn-press-emails-published-course-email-format"><?php _e( 'Email format', 'learnpress' ); ?></label>
		</th>
		<td>
			<?php learn_press_email_formats_dropdown( array( 'name' => $settings_class->get_field_name( 'emails_published_course[email_format]' ), 'id' => 'learn_press_email_formats', 'selected' => $settings->get( 'emails_published_course.email_format' ) ) ); ?>
		</td>
	</tr>-->
	<?php
	$view = learn_press_get_admin_view( 'settings/emails/email-template.php' );
	include_once $view;
	?>
	<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	</tbody>
</table>