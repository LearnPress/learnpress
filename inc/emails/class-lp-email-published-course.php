<?php

/**
 * Class LP_Email_Published_Course
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

if ( !class_exists( 'LP_Email_Published_Course' ) ) {

	class LP_Email_Published_Course extends LP_Email {
		/**
		 * LP_Email_Published_Course constructor.
		 */
		public function __construct() {
			$this->id    = 'published_course';
			$this->title = __( 'Approved course', 'learnpress' );

			$this->template_html  = 'emails/published-course.php';
			$this->template_plain = 'emails/plain/published-course.php';

			$this->default_subject = __( '[{{site_title}}] Your course {{course_name}} has been approved', 'learnpress' );
			$this->default_heading = __( 'Course approved', 'learnpress' );

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
				'{{course_edit_url}}',
				'{{course_user_id}}',
				'{{course_user_name}}',
				'{{course_user_email}}',
			);

			//$this->email_text_message_description = sprintf( '%s {{course_id}}, {{course_title}}, {{course_url}}, {{user_email}}, {{user_name}}, {{user_profile_url}}', __( 'Shortcodes', 'learnpress' ) );

			parent::__construct();
		}

		public function admin_options( $settings_class ) {
			$view = learn_press_get_admin_view( 'settings/emails/published-course.php' );
			include_once $view;
		}

		public function trigger( $course_id, $user ) {
			if ( !$this->enable ) {
				return;
			}

			$format = $this->email_format == 'plain_text' ? 'plain' : 'html';
			$course = learn_press_get_course( $course_id );
			$user   = learn_press_get_course_user( $course_id );

			$this->object = $this->get_common_template_data(
				$format,
				array(
					'course_id'         => $course_id,
					'course_name'       => $course->get_title(),
					'course_user_id'    => $user->id,
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

			if ( !$this->get_recipient() ) {
				return;
			}

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
					$user = learn_press_get_course_user( $course->id );
					if ( $course ) {
						$this->text_search = array(
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
				'login_url'     => learn_press_get_login_url(),
				'plain_text'    => $format == 'plain'
			);
		}
	}
}

return new LP_Email_Published_Course();