<?php
/**
 * LP_Email_Enrolled_Course_Admin.
 *
 * @author  ThimPress
 * @package Learnpress/Classes
 * @extends LP_Email
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Enrolled_Course_Admin' ) ) {

	/**
	 * Class LP_Email_Enrolled_Course_Admin
	 */
	class LP_Email_Enrolled_Course_Admin extends LP_Email {

		/**
		 * LP_Email_Enrolled_Course_Admin constructor.
		 */
		public function __construct() {
			$this->id          = 'enrolled-course-admin';
			$this->title       = __( 'Admin', 'learnpress' );
			$this->description = __( 'Send this email to admin when user has enrolled course.', 'learnpress' );

			$this->template_html  = 'emails/enrolled-course-admin.php';
			$this->template_plain = 'emails/plain/enrolled-course-admin.php';

			$this->default_subject = __( '[{{site_title}}]  ({{course_name}}) has been enrolled by {{custom_name}}', 'learnpress' );
			$this->default_heading = __( 'Course has been enrolled', 'learnpress' );

			$this->support_variables = array_merge( $this->general_variables, array(
				'{{course_id}}',
				'{{course_name}}',
				'{{course_url}}',
				'{{user_id}}',
				'{{user_name}}',
				'{{user_email}}',
				'{{user_profile_url}}',
				'{{custom_name}}',
				'{{custom_profile_url}}',
				'{{custom_slug}}',
				'{{start_time}}'
			) );

			//$this->email_text_message_description = sprintf( '%s {{course_id}}, {{course_title}}, {{course_url}}, {{user_email}}, {{user_name}}, {{user_profile_url}}', __( 'Shortcodes', 'learnpress' ) );

			add_action( 'learn_press_user_enrolled_course_notification', array( $this, 'get_users_to_send' ), 99, 3 );

			parent::__construct();
		}


		/**
		 * Get users to send.
		 *
		 * @param $course_id
		 * @param $user_id
		 * @param $user_course_id
		 */
		public function get_users_to_send( $course_id, $user_id, $user_course_id ) {

			if ( empty( $course_id ) || empty( $user_id ) || empty( $user_course_id ) ) {
				return;
			}

			$all_users_id = array();

			if ( LP()->settings->get( 'emails_enrolled_course_admin.enable' ) === 'yes' ) {

				// Get all users with role admin
				$admins = get_users( array(
					'role' => 'administrator'
				) );

				foreach ( $admins as $admin ) {
					$all_users_id[] = $admin->ID;
				}
			}

			if ( ! in_array( $user_id, $all_users_id ) ) {
				$all_users_id[] = $user_id;
			}

			$all_users_id = apply_filters( 'learn_press_user_admin_send_mail_enrolled_course', $all_users_id, $course_id, $user_id, $user_course_id );
			foreach ( $all_users_id as $user ) {
				$this->trigger( $course_id, $user, $user_course_id );
			}

		}

		/**
		 * Trigger email.
		 *
		 * @param $course_id
		 * @param $user_id
		 * @param $user_course_id
		 *
		 * @return bool
		 */
		public function trigger( $course_id, $user_id, $user_course_id ) {

			if ( ! $this->enable ) {
				return false;
			}

			global $wpdb;

			$user_course_data = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}learnpress_user_items WHERE user_item_id = %d", $user_course_id )
			);
			$start_time       = '';
			$customer         = array(
				'name'        => '',
				'slug'        => '',
				'profile_url' => '',
			);

			if ( ! $user_course_data ) {
				return false;
			}
			if ( ! empty( $user_course_data->start_time ) ) {
				$start_time = $user_course_data->start_time;

				// Get data customer
				$data = get_userdata( $user_course_data->user_id );
				if ( ! empty( $data ) && ! empty( $data->data ) ) {
					$customer['name']        = $data->data->display_name;
					$customer['slug']        = $data->data->user_login;
					$customer['profile_url'] = learn_press_user_profile_link( $data->ID );
				}
			}

			$course = learn_press_get_course( $course_id );
			$user   = learn_press_get_user( $user_id );

			$this->object = $this->get_common_template_data(
				$this->email_format,
				array(
					'course_id'          => $course_id,
					'course_name'        => $course->get_title(),
					'course_url'         => get_the_permalink( $course_id ),
					'user_id'            => $user_id,
					'user_name'          => learn_press_get_profile_display_name( $user ),
					'user_email'         => $user->user_email,
					'user_profile_url'   => learn_press_user_profile_link( $user->get_id() ),
					'start_time'         => $start_time,
					'custom_name'        => $customer['name'],
					'custom_profile_url' => $customer['custom_profile_url'],
					'custom_slug'        => $customer['slug']
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
				'learn-press/email-settings/enrolled-course-admin/settings',
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
						'desc'       => sprintf( __( 'Email subject, default: <code>%s</code>', 'learnpress' ), $this->default_subject ),
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
						'desc'       => sprintf( __( 'Email heading, default: <code>%s</code>', 'learnpress' ), $this->default_heading ),
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
return new LP_Email_Enrolled_Course_Admin();