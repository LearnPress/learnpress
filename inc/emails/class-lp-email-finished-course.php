<?php

/**
 * Class LP_Email_Finished_Course
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();
if ( !class_exists( 'LP_Email_Finished_Course' ) ) {
	class LP_Email_Finished_Course extends LP_Email {
		/**
		 * LP_Email_Finished_Course constructor.
		 */
		public function __construct() {
			$this->id    = 'finished_course';
			$this->title = __( 'Finished course', 'learnpress' );

			$this->template_html  = 'emails/finished-course.php';
			$this->template_plain = 'emails/plain/finished-course.php';

			$this->default_subject = __( '[{{site_title}}] You have finished this course ({{course_name}})', 'learnpress' );
			$this->default_heading = __( 'Finished course', 'learnpress' );

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

			parent::__construct();
		}

		public function admin_options( $settings_class ) {
			$view = learn_press_get_admin_view( 'settings/emails/finished-course.php' );
			include_once $view;
		}

		public function trigger( $course_id, $user_id, $result ) {

			if ( !$this->enable || !( $user = learn_press_get_user( $user_id ) ) ) {
				return;
			}

			$format = $this->email_format == 'plain_text' ? 'plain' : 'html';
			$course = learn_press_get_course( $course_id );
			remove_filter( 'the_title', 'wptexturize' );
			$course_name = $course->get_title();
			add_filter( 'the_title', 'wptexturize' );

			$this->object = $this->get_common_template_data(
				$format,
				array(
					'course_id'        => $course_id,
					'course_name'      => $course_name,
					'course_url'       => get_the_permalink( $course_id ),
					'user_id'          => $user_id,
					'user_name'        => learn_press_get_profile_display_name( $user ),
					'user_email'       => $user->user_email,
					'user_profile_url' => learn_press_user_profile_link( $user->id )
				)
			);

			$this->variables = $this->data_to_variables( $this->object );

			$this->object['course'] = $course;
			$this->object['user']   = $user;

			$this->recipient = $user->user_email;

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			return $return;
		}

		public function get_recipient() {
			if ( !empty( $this->object['user'] ) ) {
				$this->recipient = $this->object['user']->user_email;
			}
			return parent::get_recipient();
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
		public function get_template_data( $content_type = 'plain' ) {
			return $this->object;
			return array(
				'email_heading' => $this->get_heading(),
				'footer_text'   => $this->get_footer_text(),
				'site_title'    => $this->get_blogname(),
				'course_id'     => $this->object['course']->id,
				'profile_url'   => learn_press_user_profile_link( $this->object['user']->id ),
				'plain_text'    => $content_type == 'plain'
			);
		}
	}
}

return new LP_Email_Finished_Course();