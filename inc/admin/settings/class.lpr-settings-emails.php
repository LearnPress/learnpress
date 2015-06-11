<?php

/**
 * Class LPR_Settings_Emails
 */
class LPR_Settings_Emails extends LPR_Settings_Base {
	function __construct() {
		$this->id   = 'emails';
		$this->text = __( 'Emails', 'learn_press' );

		if ( $sections = $this->get_sections() ) foreach ( $sections as $id => $text ) {
			add_action( 'learn_press_section_' . $this->id . '_' . $id, array( $this, 'output_section_' . $id ) );
		}
		parent::__construct();
	}

	function get_sections() {
		$sections = array(
			'general'          => __( 'General options', 'learn_press' ),
			'published_course' => __( 'Published course', 'learn_press' ),
			'enrolled_course'  => __( 'Enrolled course', 'learn_press' ),
			'passed_course'    => __( 'Passed course', 'learn_press' )
		);
		return $sections = apply_filters( 'learn_press_settings_sections_' . $this->id, $sections );
	}

	function message_editor( $default_message ) {
		$settings  = LPR_Admin_Settings::instance( 'emails' );
		$content   = stripslashes( $settings->get( $this->section['id'] . '.message', $default_message ) );
		$editor_id = 'email_message';
		wp_editor(
			stripslashes( $content ),
			$editor_id,
			array(
				'textarea_rows' => 10,
				'wpautop'       => false,
				'textarea_name' => "lpr_settings[$this->id][message]",
			)
		);

	}

	function output() {
		$section = $this->section;
		do_action( 'learn_press_section_' . $this->id . '_' . $this->section['id'] );
	}

	function output_section_general() {
		$settings = LPR_Admin_Settings::instance( 'emails' );

		?>
		<h3><?php _e( 'Email Options', 'learn_press' ); ?></h3>
		<table class="form-table">
			<tbody>
			<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
			<tr>
				<th scope="row"><label for="lpr_from_name"><?php _e( 'From Name', 'learn_press' ); ?></label></th>
				<td>
					<input id="lpr_from_name" class="regular-text" type="text" name="lpr_settings[<?php echo $this->id; ?>][from_name]" value="<?php echo $settings->get( 'general.from_name', get_option( 'blogname' ) ); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lpr_from_email"><?php _e( 'From Email', 'learn_press' ); ?></label></th>
				<td>
					<input id="lpr_from_email" class="regular-text" type="email" name="lpr_settings[<?php echo $this->id; ?>][from_email]" value="<?php echo $settings->get( 'general.from_email', get_option( 'admin_email' ) ); ?>" />
				</td>
			</tr>
			<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
			</tbody>
		</table>
	<?php
	}

	function output_section_published_course() {
		$settings        = LPR_Admin_Settings::instance( 'emails' );
		$default_subject = 'Approved Course';
		$default_message = '<strong>Dear {user_name}</strong>,

<p>Congratulation! The course you created (<a href="{course_link}">{course_name}</a>) is available now.</p>
<p>Visit our website at {site_link}.</p>

<p>Best regards,</p>
<em>Administration</em>';
		?>

		<table class="form-table">
			<tbody>
			<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
			<tr>
				<th scope="row"><label for="lpr_email_enable"><?php _e( 'Enable', 'learn_press' ); ?></label></th>
				<td>
					<input id="lpr_email_enable" type="checkbox" name="lpr_settings[<?php echo $this->id; ?>][enable]" value="1" <?php checked( $settings->get( 'published_course.enable' ), 1 ); ?> />

					<p class="description"><?php _e( 'Send notification for instructors when their course was approved', 'learn_press' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lpr_email_subject"><?php _e( 'Subject', 'learn_press' ); ?></label></th>
				<td>
					<input id="lpr_email_subject" class="regular-text" type="text" name="lpr_settings[<?php echo $this->id; ?>][subject]" value="<?php echo $settings->get( 'published_course.subject', $default_subject ); ?>" />

					<p class="description"><?php _e( 'Email subject', 'learn_press' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="<?php echo $this->section['id'] . '_message'; ?>"><?php _e( 'Message', 'learn_press' ); ?></label>
				</th>
				<td>
					<?php $this->message_editor( $default_message ); ?>
					<p class="description"><?php _e( 'Placeholders', 'learn_press' ); ?>: <?php echo apply_filters( 'learn_press_placeholders_' . $this->section['id'], '{site_link}, {user_name}, {course_name}, {course_link}' ) ?></p>
				</td>
			</tr>
			<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
			</tbody>
		</table>
	<?php
	}

	function output_section_enrolled_course() {
		$settings        = LPR_Admin_Settings::instance( 'emails' );
		$default_subject = 'Course Registration';
		$default_message = '<strong>Dear {user_name}</strong>,

<p>You have been enrolled in <a href="{course_link}">{course_name}</a>.</p>
<p>Visit our website at {site_link}.</p>

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
					<p class="description"><?php _e( 'Placeholders', 'learn_press' ); ?>: <?php echo apply_filters( 'learn_press_placeholders_' . $this->section['id'], '{site_link}, {user_name}, {course_name}, {course_link}' ) ?></p>

				</td>
			</tr>
			<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
			</tbody>
		</table>
	<?php
	}

	function output_section_passed_course() {
		$settings        = LPR_Admin_Settings::instance( 'emails' );
		$default_subject = 'Course Achievement';
		$default_message = '<strong>Dear {user_name}</strong>,

<p>You have been finished in <a href="{course_link}">{course_name}</a> with {course_result}</p>
<p>Visit our website at {site_link}.</p>

<p>Best regards,</p>
<em>Administration</em>';

		?>

		<table class="form-table">
			<tbody>
			<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
			<tr>
				<th scope="row"><label for="lpr_email_enable"><?php _e( 'Enable', 'learn_press' ); ?></label></th>
				<td>
					<input id="lpr_email_enable" type="checkbox" name="lpr_settings[<?php echo $this->id; ?>][enable]" value="1" <?php checked( $settings->get( 'passed_course.enable' ), 1 ); ?> />

					<p class="description"><?php _e( 'Send notification for users when they finished a course', 'learn_press' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lpr_email_subject"><?php _e( 'Subject', 'learn_press' ); ?></label></th>
				<td>
					<input id="lpr_email_subject" class="regular-text" type="text" name="lpr_settings[<?php echo $this->id; ?>][subject]" value="<?php echo $settings->get( 'passed_course.subject', $default_subject ); ?>" />

					<p class="description"><?php _e( 'Email subject', 'learn_press' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php _e( 'Message', 'learn_press' ); ?></label></th>
				<td>
					<?php $this->message_editor( $default_message ); ?>
					<p class="description"><?php _e( 'Placeholders', 'learn_press' ); ?>: <?php echo apply_filters( 'learn_press_placeholders_' . $this->section['id'], '{site_link}, {user_name}, {course_name}, {course_link}, {course_result}' ) ?></p>
				</td>
			</tr>
			<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
			</tbody>
		</table>
	<?php
	}

	function save() {
		$settings  = LPR_Admin_Settings::instance( 'emails' );
		$section   = $this->section['id'];
		$post_data = $_POST['lpr_settings'][$this->id];

		$settings->set( $section, $post_data );
		$settings->update();
	}
}

new LPR_Settings_Emails();