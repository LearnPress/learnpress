<?php

/**
 * Class LP_Settings_Emails
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Settings_Emails extends LP_Settings_Base {
	/**
	 * @var null
	 */
	protected static $_instance = null;

	/**
	 * Constructor
	 */
	public function __construct () {
		$this->id   = 'emails';
		$this->text = __( 'Emails', 'learnpress' );
		parent::__construct();
	}

	/**
	 * Sections
	 *
	 * @return mixed
	 */
	public function get_sections () {

		$emails = LP_Emails::instance()->emails;

		$sections = array(
			'general' => array( 'id' => 'general', 'title' => __( 'General options', 'learnpress' ) )
		);

		if ( $emails ) {
			foreach ( $emails as $email ) {
				$sections[ $email->id ] = array(
					'id'    => $email->id,
					'title' => $email->title
				);
			}
		}

		/* $sections = array(
		  'general'          => __( 'General options', 'learnpress' ),
		  'new_course'       => __( 'New course', 'learnpress' ),
		  'published_course' => __( 'Published course', 'learnpress' ),
		  'new_order'        => __( 'New order', 'learnpress' ),
		  'enrolled_course'  => __( 'Enrolled course', 'learnpress' ),
		  'passed_course'    => __( 'Passed course', 'learnpress' ),
		  ); */

		return $sections = apply_filters( 'learn_press_settings_sections_' . $this->id, $sections );
	}

	/**
	 * @param $default_message
	 */
	public function message_editor ( $default_message ) {
		$settings  = LP()->settings;
		$content   = stripslashes( $settings->get( $this->section['id'] . '.message', $default_message ) );
		$editor_id = 'email_message';
		wp_editor(
			stripslashes( $content ), $editor_id, array(
				'textarea_rows' => 10,
				'wpautop'       => false,
				'textarea_name' => "lpr_settings[$this->id][message]",
			)
		);
	}

	public function get_email_class ( $id ) {
		$emails = LP_Emails::instance()->emails;
		if ( $emails ) {
			foreach ( $emails as $email ) {
				if ( $email->id == $id ) {
					return $email;
				}
			}
		}

		return false;
	}

	/**
	 *
	 */
	public function output_section_general () {
		$view = learn_press_get_admin_view( 'settings/emails/general.php' );
		include_once $view;
	}

	public function output_section_new_course () {
		if ( $email = $this->get_email_class( 'new_course' ) ) {
			$email->admin_options( $this );
		}
	}

	public function output_section_user_order_completed () {
		if ( $email = $this->get_email_class( 'user_order_completed' ) ) {
			$email->admin_options( $this );
		}
	}

	public function output_section_user_order_changed_status () {
		if ( $email = $this->get_email_class( 'user_order_changed_status' ) ) {
			$email->admin_options( $this );
		}
	}

	public function output_section_rejected_course () {
		if ( $email = $this->get_email_class( 'rejected_course' ) ) {
			$email->admin_options( $this );
		}
	}

	public function output_section_new_order () {
		if ( $email = $this->get_email_class( 'new_order' ) ) {
			$email->admin_options( $this );
		}
	}

	public function output_section_new_order_customer () {
		if ( $email = $this->get_email_class( 'new_order_customer' ) ) {
			$email->admin_options( $this );
		}
	}

	public function output_section_published_course () {
		if ( $email = $this->get_email_class( 'published_course' ) ) {
			$email->admin_options( $this );
		}
	}

	public function output_section_enrolled_course () {
		if ( $email = $this->get_email_class( 'enrolled_course' ) ) {
			$email->admin_options( $this );
		}
	}

	public function output_section_enrolled_course_admin () {
		if ( $email = $this->get_email_class( 'enrolled_course_admin' ) ) {
			$email->admin_options( $this );
		}
	}

	public function output_section_finished_course () {
		if ( $email = $this->get_email_class( 'finished_course' ) ) {
			$email->admin_options( $this );
		}
	}

	public function output_section_update_course () {
		if ( $email = $this->get_email_class( 'update_course' ) ) {
			$email->admin_options( $this );
		}
	}

	public function output_section_become_an_instructor () {
		if ( $email = $this->get_email_class( 'become_an_instructor' ) ) {
			$email->admin_options( $this );
		}
	}

	public function get_settings () {
		return apply_filters(
			'learn_press_email_settings', array(
				array(
					'title'   => __( 'Profile page', 'learnpress' ),
					'id'      => $this->get_field_name( 'profile_page_id' ),
					'id'      => $this->get_field_name( 'profile_page_id' ),
					'default' => '',
					'type'    => 'pages-dropdown'
				)
			)
		);
	}

	public static function instance () {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

//
return LP_Settings_Emails::instance();
