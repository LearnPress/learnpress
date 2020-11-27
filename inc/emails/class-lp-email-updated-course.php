<?php
/**
 * Class LP_Email_Updated_Course
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Updated_Course' ) ) {

	/**
	 * Class LP_Email_Updated_Course
	 */
	class LP_Email_Updated_Course extends LP_Email {

		/**
		 * @var
		 */
		public $object;

		/**
		 * LP_Email_Updated_Course constructor.
		 */
		public function __construct() {
			$this->id          = 'updated-course';
			$this->title       = __( 'Updated course', 'learnpress' );
			$this->description = __( 'Send this email to users have purchased when the course is updated.', 'learnpress' );

			$this->template_html  = 'emails/update-course.php';
			$this->template_plain = 'emails/plain/update-course.php';

			$this->default_subject = __( '[{{site_title}}]  The course ({{course_name}}) has just been updated.', 'learnpress' );
			$this->default_heading = __( 'Update course', 'learnpress' );

			$this->support_variables = array_merge( $this->general_variables, array(
//				'{{site_admin_email}}',
//				'{{site_admin_name}}',
				'{{course_id}}',
				'{{course_name}}',
				'{{course_url}}',
				'{{user_id}}',
				'{{user_name}}',
				'{{user_email}}',
				'{{user_profile_url}}'
			) );

			add_action( 'post_updated', array( $this, 'update_course' ), 10, 2 );

			parent::__construct();

		}

		/**
		 * Update course.
		 *
		 * @param $post_id
		 * @param $post
		 */
		public function update_course( $post_id, $post ) {

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

				if ( ! empty( $user ) && ! empty( $user->meta_value ) ) {
					$this->trigger( $post_id, $user->meta_value );
				}
			}
		}


		/**
		 * Trigger email.
		 *
		 * @param $course_id
		 * @param $user_id
		 *
		 * @return bool
		 */
		public function trigger( $course_id, $user_id ) {

			if ( empty( $course_id ) || empty( $user_id ) ) {
				return false;
			}

			$course       = learn_press_get_course( $course_id );
			$user         = learn_press_get_user( $user_id );
			$is_send_mail = apply_filters( 'learn_press_has_send_mail_update_course', $course->send_mail_update_course, $course, $user );

			if ( empty( $is_send_mail ) || $is_send_mail !== 'yes' ) {
				return false;
			}
			$this->object = $this->get_common_template_data(
				$this->email_format,
				array(
					'course_id'        => $course_id,
					'course_name'      => $course->get_title(),
					'course_url'       => get_the_permalink( $course_id ),
					'user_id'          => $user_id,
					'user_name'        => learn_press_get_profile_display_name( $user ),
					'user_email'       => $user->user_email,
					'user_profile_url' => learn_press_user_profile_link( $user->get_id() )
				)
			);

			$this->variables        = $this->data_to_variables( $this->object );
			$this->object['course'] = $course;
			$this->object['user']   = $user;
			$this->recipient        = $user->user_email;
			$return                 = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

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
				'learn-press/email-settings/update-course/settings',
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

return new LP_Email_Updated_Course();