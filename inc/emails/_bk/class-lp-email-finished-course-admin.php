<?php
/**
 * Class LP_Email_Finished_Course_Admin
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Finished_Course_Admin' ) ) {

	/**
	 * Class LP_Email_Finished_Course_Admin
	 */
	class LP_Email_Finished_Course_Admin extends LP_Email_Type_Finished_Course {
		/**
		 * LP_Email_Finished_Course constructor.
		 */
		public function __construct() {
			$this->id          = 'finished-course-admin';
			$this->title       = __( 'Admin', 'learnpress' );
			$this->description = __( 'Send this email to user when a user finished a course.', 'learnpress' );


			$this->default_subject = __( '[{{site_title}}] You have finished this course ({{course_name}})', 'learnpress' );
			$this->default_heading = __( 'Finished course', 'learnpress' );

			$this->support_variables = array_merge( $this->general_variables, array(
				'{{course_id}}',
				'{{course_name}}',
				'{{course_url}}',
				'{{user_id}}',
				'{{user_name}}',
				'{{user_email}}',
				'{{user_profile_url}}'
			) );

			//$this->email_text_message_description = sprintf( '%s {{course_id}}, {{course_title}}, {{course_url}}, {{user_email}}, {{user_name}}, {{user_profile_url}}', __( 'Shortcodes', 'learnpress' ) );

			parent::__construct();
		}

		/**
		 * Trigger email.
		 *
		 * @param $course_id
		 * @param $user_id
		 * @param $result
		 *
		 * @return bool
		 */
		public function trigger( $course_id, $user_id, $result ) {

			if ( ! $this->enable || ! ( $user = learn_press_get_user( $user_id ) ) ) {
				return false;
			}

			$course = learn_press_get_course( $course_id );
			remove_filter( 'the_title', 'wptexturize' );
			$course_name = $course->get_title();
			add_filter( 'the_title', 'wptexturize' );

			$this->object = $this->get_common_template_data(
				$this->email_format,
				array(
					'course_id'        => $course_id,
					'course_name'      => $course_name,
					'course_url'       => get_the_permalink( $course_id ),
					'user_id'          => $user_id,
					'user_name'        => learn_press_get_profile_display_name( $user ),
					'user_email'       => $user->user_email,
					'user_profile_url' => learn_press_user_profile_link( $user->get_id() )
				)
			);

			$this->variables = $this->data_to_variables( $this->object );

			$this->object['course'] = $course;
			$this->object['user']   = $user;

			$this->recipient = $user->user_email;

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			return $return;
		}


		/**
		 * Get recipient.
		 *
		 * @return mixed|void
		 */
		public function get_recipient() {
			if ( ! empty( $this->object['user'] ) ) {
				$this->recipient = $this->object['user']->user_email;
			}

			parent::get_recipient();
		}

		/**
		 * Get email template.
		 *
		 * @param string $content_type
		 *
		 * @return array|object
		 */
		public function get_template_data( $content_type = 'plain' ) {
			return $this->object;
		}

		/**
		 * Admin settings.
		 */
		public function get_settings() {
			return apply_filters(
				'learn-press/email-settings/finished-course/settings',
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

return new LP_Email_Finished_Course_Admin();