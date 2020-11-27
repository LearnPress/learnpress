<?php
/**
 * Class LP_Email_Published_Course
 *
 * Approved new course email.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Published_Course' ) ) {

	/**
	 * Class LP_Email_Published_Course
	 */
	class LP_Email_Published_Course extends LP_Email {
		/**
		 * LP_Email_Published_Course constructor.
		 */
		public function __construct() {
			$this->id          = 'published-course';
			$this->title       = __( 'Approved course', 'learnpress' );
			$this->description = __( 'Settings for email when a course is approved.', 'learnpress' );

			$this->template_html  = 'emails/published-course.php';
			$this->template_plain = 'emails/plain/published-course.php';

			$this->default_subject = __( '[{{site_title}}] Your course {{course_name}} has been approved', 'learnpress' );
			$this->default_heading = __( 'Course approved', 'learnpress' );

			$this->support_variables = array_merge( $this->general_variables, array(
				'{{course_id}}',
				'{{course_name}}',
				'{{course_url}}',
				'{{course_edit_url}}',
				'{{course_user_id}}',
				'{{course_user_name}}',
				'{{course_user_email}}',
			) );

			//$this->email_text_message_description = sprintf( '%s {{course_id}}, {{course_title}}, {{course_url}}, {{user_email}}, {{user_name}}, {{user_profile_url}}', __( 'Shortcodes', 'learnpress' ) );

			parent::__construct();
		}


		/**
		 * Trigger email.
		 *
		 * @param $course_id
		 * @param $user
		 *
		 * @return bool
		 */
		public function trigger( $course_id, $user ) {
			if ( ! $this->enable ) {
				return false;
			}

			$course = learn_press_get_course( $course_id );
			$user   = learn_press_get_course_user( $course_id );

			$this->object = $this->get_common_template_data(
				$this->email_format,
				array(
					'course_id'         => $course_id,
					'course_name'       => $course->get_title(),
					'course_user_id'    => $user->get_id(),
					'course_edit_url'   => admin_url( 'post.php?post=' . $course_id . '&action=edit' ),
					'course_user_name'  => learn_press_get_profile_display_name( $user ),
					'course_user_email' => $user->user_email,
					'course_url'        => get_the_permalink( $course_id )
				)
			);

			$this->variables = $this->data_to_variables( $this->object );

			$this->object['course']      = $course;
			$this->object['user_course'] = $user;

			$this->recipient = $user->user_email;

			if ( ! $this->get_recipient() ) {
				return false;
			}

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			return $return;
		}

		/**
		 * Get email template.
		 *
		 * @param string $format
		 *
		 * @return array|object
		 */
		public function get_template_data( $format = 'plain' ) {
			return $this->object;
		}

		/**
		 * Admin settings.
		 */
		public function get_settings() {
			return apply_filters(
				'learn-press/email-settings/published-course/settings',
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
						'id'      => $this->get_field_name( 'enable' )
					),
					array(
						'title'      => __( 'Subject', 'learnpress' ),
						'type'       => 'text',
						'default'    => $this->default_subject,
						'id'         => $this->get_field_name( 'subject' ),
						'desc'       => sprintf( __( 'Email subject, default: <code>%s</code>.', 'learnpress' ), $this->default_subject ),
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => $this->get_field_name( 'enable' ),
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
						'id'         => $this->get_field_name( 'heading' ),
						'desc'       => sprintf( __( 'Email heading, default: <code>%s</code>.', 'learnpress' ), $this->default_heading ),
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => $this->get_field_name( 'enable' ),
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
						'id'                   => $this->get_field_name( 'email_content' ),
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
									'field'   => $this->get_field_name( 'enable' ),
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
}

return new LP_Email_Published_Course();