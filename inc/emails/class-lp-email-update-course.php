<?php
/**
 * Class LP_Email_Update_Course
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Update_Course' ) ) {

	class LP_Email_Update_Course extends LP_Email {

		public $object;

		public function __construct () {

			$this->id    = 'update_course';
			$this->title = __( 'Update course', 'learnpress' );

			$this->template_html  = 'emails/update-course.php';
			$this->template_plain = 'emails/plain/update-course.php';

			$this->default_subject = __( '[{{site_title}}]  The course ({{course_name}}) has just been updated.', 'learnpress' );
			$this->default_heading = __( 'Update course', 'learnpress' );

			$this->support_variables = array(
				'{{site_url}}',
				'{{site_title}}',
//				'{{site_admin_email}}',
//				'{{site_admin_name}}',
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

			add_action( 'post_updated', array( $this, 'update_course' ), 10, 2 );

			parent::__construct();

		}

		public function admin_options ( $settings_class ) {
			$view = learn_press_get_admin_view( 'settings/emails/update-course.php' );
			include_once $view;
		}

		public function update_course ( $post_id, $post ) {

			if ( empty( $post_id ) || empty( $post ) ) {
				return;
			}
			$post_type = $post->post_type;
			if ( empty( $post_type ) || $post_type != 'lp_course' ) {
				return;
			}
			global $wpdb;

			$query = $wpdb->prepare( "
				SELECT pmt.meta_value
				FROM {$wpdb->posts} o
				INNER JOIN {$wpdb->learnpress_order_items} oi ON oi.order_id = o.ID
				INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oim.learnpress_order_item_id = oi.order_item_id
				INNER JOIN {$wpdb->prefix}postmeta pmt ON pmt.meta_key = %s and pmt.post_id = oi.order_id
				AND oim.meta_key = %s AND oim.meta_value = %d
				WHERE o.post_status = %s
			", '_user_id', '_course_id', $post_id, 'lp-completed' );

			$users_enrolled = $wpdb->get_results( $query );
			foreach ( $users_enrolled as $user ) {

				if ( ! empty( $user ) && !empty($user->meta_value) ) {
					$this->trigger( $post_id, $user->meta_value );
				}
			}
		}

		public function trigger ( $course_id, $user_id ) {

			if (empty($course_id) || empty($user_id)) {
				return;
			}
			$format       = $this->email_format == 'plain_text' ? 'plain' : 'html';
			$course       = learn_press_get_course( $course_id );
			$user         = learn_press_get_user( $user_id );
			$is_send_mail = apply_filters( 'learn_press_has_send_mail_update_course', $course->send_mail_update_course, $course, $user );

			if (empty($is_send_mail) || $is_send_mail !== 'yes') {
				return;
			}
			$this->object = $this->get_common_template_data(
				$format,
				array(
					'course_id'        => $course_id,
					'course_name'      => $course->get_title(),
					'course_url'       => get_the_permalink( $course_id ),
					'user_id'          => $user_id,
					'user_name'        => learn_press_get_profile_display_name( $user ),
					'user_email'       => $user->user_email,
					'user_profile_url' => learn_press_user_profile_link( $user->id )
				)
			);

			$this->variables        = $this->data_to_variables( $this->object );
			$this->object['course'] = $course;
			$this->object['user']   = $user;
			$this->recipient        = $user->user_email;
			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			return $return;
		}

		public function get_template_data ( $format = 'plain' ) {
			return $this->object;
		}
	}
}

return new LP_Email_Update_Course();