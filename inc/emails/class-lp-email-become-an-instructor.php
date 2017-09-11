<?php

/**
 * Class LP_Email_Enrolled_Course
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Email_Become_An_Instructor extends LP_Email {

	public function __construct() {

		$this->id          = 'become_an_instructor';
		$this->title       = __( 'Become an instructor', 'learnpress' );
		$this->description = __( 'Become an instructor email', 'learnpress' );

		$this->template_html  = 'emails/enrolled-course.php';
		$this->template_plain = 'emails/plain/enrolled-course.php';

		$this->default_subject = __( 'Become an teacher', 'learnpress' );
		$this->default_heading = __( 'Become an instructor', 'learnpress' );

		$this->email_text_message_description = sprintf( '%s [course_id], [course_title], [course_url], [user_email], [user_name], [user_profile_url]', __( 'Shortcodes', 'learnpress' ) );

		$this->support_variables = array(
			'{{site_url}}',
			'{{site_title}}',
			'{{login_url}}',
			'{{email_heading}}',
			'{{user_email}}',
			'{{user_nicename}}'
		);
		parent::__construct();
	}

	/**
	 * Trigger email.
	 *
	 * @param $user
	 *
	 * @return bool
	 */
	public function trigger( $user ) {
		if ( ! $this->enable ) {
			return false;
		}

		$user            = get_user_by( 'id', $user );
		$this->recipient = $user->user_email;
		$this->object    = $this->get_common_template_data(
			$this->email_format,
			array(
				'site_url'      => $user->ID,
				'login_url'     => wp_login_url(),
				'user_nicename' => $user->user_nincename,
				'user_email'    => $user->user_email,
				'email_heading' => $this->get_heading(),
				'footer_text'   => $this->get_footer_text(),
				'site_title'    => $this->get_blogname(),
				'plain_text'    => $this->email_format == 'plain',
			)
		);

		$this->variables = $this->data_to_variables( $this->object );

		$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		return $return;
	}

	/**
	 * Get email template.
	 *
	 * @param string $format
	 *
	 * @return mixed
	 */
	public function get_template_data( $format = 'plain' ) {
		return $this->object;
	}

	/**
	 * Admin settings.
	 */
	public function get_settings() {
		return apply_filters(
			'learn-press/email-settings/become-an-instructor/settings',
			array(
				array(
					'type'  => 'heading',
					'title' => $this->title,
					'desc'  => $this->description
				),
				array(
					'title'   => __( 'Enable', 'learnpress' ),
					'type'    => 'yes-no',
					'default' => 'no',
					'id'      => 'emails_become_an_instructor[enable]',
					'desc'    => __( 'Send notification to user when accept', 'learnpress' )
				),
				array(
					'title'      => __( 'Subject', 'learnpress' ),
					'type'       => 'text',
					'default'    => $this->default_subject,
					'id'         => 'emails_become_an_instructor[subject]',
					'desc'       => sprintf( __( 'Email subject, default: <code>%s</code>', 'learnpress' ), $this->default_subject ),
					'visibility' => array(
						'state'       => 'show',
						'conditional' => array(
							array(
								'field'   => 'emails_become_an_instructor[enable]',
								'compare' => '=',
								'value'   => 'yes'
							)
						)
					)
				),
				array(
					'title'      => __( 'Heading', 'learnpress' ),
					'type'       => 'text',
					'default'    => $this->default_heading,
					'id'         => 'emails_become_an_instructor[heading]',
					'desc'       => sprintf( __( 'Email heading, default: <code>%s</code>', 'learnpress' ), $this->default_heading ),
					'visibility' => array(
						'state'       => 'show',
						'conditional' => array(
							array(
								'field'   => 'emails_become_an_instructor[enable]',
								'compare' => '=',
								'value'   => 'yes'
							)
						)
					)
				),
				array(
					'title'                => __( 'Email content', 'learnpress' ),
					'type'                 => 'email-content',
					'default'              => '',
					'id'                   => 'emails_become_an_instructor[email_content]',
					'template_base'        => $this->template_base,
					'template_path'        => $this->template_path,//default learnpress
					'template_html'        => $this->template_html,
					'template_plain'       => $this->template_plain,
					'template_html_local'  => $this->get_theme_template_file( 'html', $this->template_path ),
					'template_plain_local' => $this->get_theme_template_file( 'plain', $this->template_path ),
					'support_variables'    => $this->get_variables_support(),
					'visibility'           => array(
						'state'       => 'show',
						'conditional' => array(
							array(
								'field'   => 'emails_become_an_instructor[enable]',
								'compare' => '=',
								'value'   => 'yes'
							)
						)
					)
				),
			)
		);
	}


}

return new LP_Email_Become_An_Instructor();