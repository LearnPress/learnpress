<?php

/**
 * Class LP_Email_Enrolled_Course
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

if ( !class_exists( 'LP_Email_Enrolled_Course' ) ) {
	class LP_Email_Enrolled_Course extends LP_Email {
		/**
		 * LP_Email_Enrolled_Course constructor.
		 */
		public function __construct() {
			$this->id    = 'enrolled_course';
			$this->title = __( 'Enrolled course', 'learnpress' );

			$this->template_html  = 'emails/enrolled-course.php';
			$this->template_plain = 'emails/plain/enrolled-course.php';

			$this->default_subject = __( '[{{site_title}}]  You have enrolled in this course ({{course_name}})', 'learnpress' );
			$this->default_heading = __( 'Enrolled course', 'learnpress' );

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
				'{{user_profile_url}}'
			);

			//$this->email_text_message_description = sprintf( '%s {{course_id}}, {{course_title}}, {{course_url}}, {{user_email}}, {{user_name}}, {{user_profile_url}}', __( 'Shortcodes', 'learnpress' ) );

			add_action( 'learn_press_user_enrolled_course_notification', array( $this, 'trigger' ), 99, 3 );

			parent::__construct();
		}

		public function admin_options( $settings_class ) {
			$view = learn_press_get_admin_view( 'settings/emails/enrolled-course.php' );
			include_once $view;
		}

		public function trigger( $course_id, $user_id, $user_course_id ) {
			if ( !$this->enable ) {
				return;
			}

			global $wpdb;

			$user_course_data = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}learnpress_user_items WHERE user_item_id = %d", $user_course_id )
			);

			if ( !$user_course_data ) {
				// TODO: ...
				return;
			}

			$format = $this->email_format == 'plain_text' ? 'plain' : 'html';
			$course = learn_press_get_course( $course_id );
			$user   = learn_press_get_user( $user_id );

			$this->object = $this->get_common_template_data(
				$format,
				array(
					'course_id'        => $course_id,
					'course_name'      => $course->get_title(),
					'course_url'       => get_the_permalink( $course_id ),
					'user_id'          => $user_id,
					'user_name'        => learn_press_get_profile_display_name( $user ),
					'user_email'       => $user->user_email,
					'user_profile_url' => learn_press_user_profile_link($user->id)
				)
			);

			$this->variables = $this->data_to_variables( $this->object );

			$this->object['course'] = $course;
			$this->object['user']   = $user;

			$this->recipient = $user->user_email;

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			return $return;
		}

		/*
				public function get_content_html() {
					ob_start();
					learn_press_get_template( $this->template_html, $this->get_template_data( 'html' ) );
					return ob_get_clean();
				}

				public function get_content_plain() {
					ob_start();
					learn_press_get_template( $this->template_plain, $this->get_template_data( 'plain' ) );
					return ob_get_clean();
				}

				public function _prepare_content_text_message() {
					$course = isset( $this->object['course'] ) ? $this->object['course'] : null;
					$user   = isset( $this->object['user'] ) ? $this->object['user'] : null;
					if ( $course && $user ) {
						$this->text_search  = array(
							"/\{\{course\_id\}\}/",
							"/\{\{course\_title\}\}/",
							"/\{\{course\_url\}\}/",
							"/\{\{user\_email\}\}/",
							"/\{\{user\_name\}\}/",
							"/\{\{user\_profile\_url\}\}/",
						);
						$this->text_replace = array(
							$course->id,
							get_the_title( $course->id ),
							get_the_permalink( $course->id ),
							$user->user_email,
							$user->user_nicename,
							learn_press_user_profile_link( $user->id )
						);
					}
				}
		*/
		public function get_template_data( $format = 'plain' ) {
			return $this->object;
			return array(
				'email_heading' => $this->get_heading(),
				'footer_text'   => $this->get_footer_text(),
				'site_title'    => $this->get_blogname(),
				'course'        => $this->object['course'],
				'user'          => $this->object['user'],
				'login_url'     => learn_press_get_login_url(),
				'plain_text'    => $format == 'plain'
			);
		}
	}
}

return new LP_Email_Enrolled_Course();