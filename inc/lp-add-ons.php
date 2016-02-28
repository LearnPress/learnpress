<?php

/**
 * This file contains the functions/classes as for addons
 */
class LP_Prerequisite_Addon {
	protected $course_prerequisites = array();

	function __construct() {
		$enable_prerequisites = 'no';
		if ( $enable_prerequisites == 'no' ) {
			return;
		}
		add_filter( 'learn_press_user_can_enroll_course', array( $this, 'check_required_course' ), 10, 3 );
		add_filter( 'learn_press_user_purchased_course_message', array( $this, 'course_message' ), 10, 3 );
	}

	function check_required_course( $enable, $user, $course_id ) {
		if ( $course_id == 920 ) {
			$enable                                 = false;
			$this->course_prerequisites[$course_id] = 815;
		}
		return $enable;
	}

	function course_message( $message, $course, $user ) {
		if ( !empty( $this->course_prerequisites[$course->id] ) ) {
			$message = sprintf( __( 'Please finish course <strong>%s</strong> before you can enroll this course', 'learnpress' ), get_the_title( $this->course_prerequisites[$course->id] ) );
		}
		return $message;
	}

	static function translations() {
		//die();
		$textdomain    = 'learnpress_prerequisites';
		$locale        = apply_filters( "plugin_locale", get_locale(), $textdomain );
		$lang_dir      = dirname( __FILE__ ) . '/lang/';
		$mofile        = sprintf( '%s.mo', $locale );
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;
		if ( file_exists( $mofile_global ) ) {
			load_textdomain( $textdomain, $mofile_global );
		} else {
			load_textdomain( $textdomain, $mofile_local );
		}
	}
}

add_action( 'plugins_loaded', array( 'LP_Prerequisite_Addon', 'translations' ) );
new LP_Prerequisite_Addon();
