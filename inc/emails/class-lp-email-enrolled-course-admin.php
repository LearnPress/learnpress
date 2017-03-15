<?php
/**
 * Class LP_Email_Enrolled_Course_Admin
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Enrolled_Course_Admin' ) ) {

	class LP_Email_Enrolled_Course_Admin extends LP_Email {


		/**
		 * LP_Email_Enrolled_Course_Admin constructor.
		 */
		public function __construct () {
			$this->id    = 'enrolled_course_admin';
			$this->title = __( 'Enrolled course admin', 'learnpress' );

			$this->template_html  = 'emails/enrolled-course-admin.php';
			$this->template_plain = 'emails/plain/enrolled-course-admin.php';

			$this->default_subject = __( '[{{site_title}}]  ({{course_name}}) has been enrolled by {{custom_name}}', 'learnpress' );
			$this->default_heading = __( 'Course has been enrolled', 'learnpress' );

			$this->support_variables = array(
				'{{site_url}}',
				'{{site_title}}',
				'{{site_admin_email}}',
				'{{site_admin_name}}',
				'{{login_url}}',
				'{{header}}',
				'{{footer}}',
				'{{email_heading}}',
				'{{footer_text}}',
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
			);

			//$this->email_text_message_description = sprintf( '%s {{course_id}}, {{course_title}}, {{course_url}}, {{user_email}}, {{user_name}}, {{user_profile_url}}', __( 'Shortcodes', 'learnpress' ) );

			add_action( 'learn_press_user_enrolled_course_notification', array( $this, 'get_users_to_send' ), 99, 3 );

			parent::__construct();
		}

		public function admin_options ( $settings_class ) {
			$view = learn_press_get_admin_view( 'settings/emails/enrolled-course-admin.php' );
			include_once $view;
		}

		public function get_users_to_send ( $course_id, $user_id, $user_course_id ) {

			if ( empty( $course_id ) || empty( $user_id ) || empty( $user_course_id ) ) {
				return;
			}

			$all_users_id = array();

			if ( LP()->settings->get( 'emails_enrolled_course_admin.send_admins' ) === 'yes' ) {

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

		public function trigger ( $course_id, $user_id, $user_course_id ) {

			if ( ! $this->enable ) {
				return;
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
				return;
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


			$format = $this->email_format == 'plain_text' ? 'plain' : 'html';
			$course = learn_press_get_course( $course_id );
			$user   = learn_press_get_user( $user_id );

			$this->object = $this->get_common_template_data(
				$format,
				array(
					'course_id'          => $course_id,
					'course_name'        => $course->get_title(),
					'course_url'         => get_the_permalink( $course_id ),
					'user_id'            => $user_id,
					'user_name'          => learn_press_get_profile_display_name( $user ),
					'user_email'         => $user->user_email,
					'user_profile_url'   => learn_press_user_profile_link( $user->id ),
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

		public function get_template_data ( $format = 'plain' ) {
			return $this->object;
		}


	}

}
return new LP_Email_Enrolled_Course_Admin();