<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'LP_Profile' ) ) {
    /**
     * Class LP_Profile
     */
	class LP_Profile {
		/**
		 *  Constructor
		 */
		public function __construct() {
			add_filter( 'learn_press_profile_methods', array( $this, 'learn_press_profile_method' ) );
//			add_action( 'wp_loaded', array( $this, 'learn_press_process_profile' ) );
			add_action( 'learn_press_before_profile_content', array( $this, 'learn_press_add_tabs_scripts' ) );
			add_action( 'learn_press_add_profile_tab', array( $this, 'learn_press_add_profile_tab' ) );
			add_filter( 'learn_press_user_info_tab_content', array( $this, 'learn_press_user_info_tab_content' ), 10, 2 );
			add_filter( 'learn_press_user_courses_tab_content', array( $this, 'learn_press_user_courses_tab_content' ), 10, 2 );
			add_filter( 'learn_press_user_quizzes_tab_content', array( $this, 'learn_press_user_quizzes_tab_content' ), 10, 2 );
			add_action( 'learn_press_enrolled_course_after_title', array( $this, 'end_title_content' ), 10, 2 );
		}
	
		/**
		 * Process profile
		 */
		public function learn_press_process_profile() {
			if ( learn_press_has_profile_method() ) {
				if ( learn_press_get_profile_page_id() == 0 ) {
					$profile         = array(
						'post_title'   => 'Profile',
						'post_content' => '[learn_press_profile]',
						'post_type'    => 'page',
						'post_status'  => 'publish',
					);
					$profile_page_id = wp_insert_post( $profile );
					update_post_meta( $profile_page_id, '_lpr_is_profile_page', 1 );
				}
			} else {
				wp_delete_post( learn_press_get_profile_page_id(), true );
			}
		}

		/*
		 * Profile methods
		 */
		public function learn_press_profile_method( $methods ) {
			$methods['lpr_profile'] = __( 'LearnPress Profile', 'learnpress' );

			return $methods;
		}

		/*
		 * Enqueue jquery ui scripts
		 */
		public function learn_press_add_tabs_scripts() {
			/*wp_enqueue_style( 'lpr-jquery-ui-css', LP_CSS_URL . 'jquery-ui.css' );
			wp_enqueue_script( 'lpr-jquery-ui-js', LP_JS_URL . 'jquery-ui.js', array( 'jquery' ), '', false );*/
		}

		/*
		 * Add profile tab
		 */
		public function learn_press_add_profile_tab( $user ) {
			$content = '';
			$tabs    = apply_filters(
				'learn_press_profile_tabs',
				array(
					10 => array(
						'tab_id'      => 'user_info',
						'tab_name'    => __( 'User Information', 'learnpress' ),
						'tab_content' => apply_filters( 'learn_press_user_info_tab_content', $content, $user )
					),
					20 => array(
						'tab_id'      => 'user_courses',
						'tab_name'    => __( 'Courses', 'learnpress' ),
						'tab_content' => apply_filters( 'learn_press_user_courses_tab_content', $content, $user )
					)/*,
					30 => array(
						'tab_id'      => 'user_quizzes',
						'tab_name'    => __( 'Quiz Results', 'learnpress' ),
						'tab_content' => apply_filters( 'learn_press_user_quizzes_tab_content', $content, $user )
					)*/
				),
				$user
			);
			ksort( $tabs );
			echo '<ul>';
			foreach ( $tabs as $tab ) {
				echo '<li><a href="#' . $tab['tab_id'] . '">' . $tab['tab_name'] . '</a></li>';
			}
			echo '</ul>';
			foreach ( $tabs as $tab ) {
				echo '<div id="' . $tab['tab_id'] . '">' . $tab['tab_content'] . '</div>';
			}
		}

		/*
		 * Add content for user information tab
		 */
		public function learn_press_user_info_tab_content( $content, $user ) {
			ob_start();
			learn_press_get_template( 'profile/user-info.php', array( 'user' => $user ) );
			$content .= ob_get_clean();
			return $content;
		}

		/*
		 * Add content for user courses tab
		 */
		public function learn_press_user_courses_tab_content( $content, $user ) {
			ob_start();
			learn_press_get_template( 'profile/user-courses.php', array( 'user' => $user ) );
			$content .= ob_get_clean();
			return $content;
		}


		/*
		 * Add content for user quiz results tab
		 */
		public function learn_press_user_quizzes_tab_content( $content, $user ) {
			ob_start();
			learn_press_get_template( 'profile/user-quizzes.php', array( 'content' => $content, 'user' => $user ) );
			$content .= ob_get_clean();
			return $content;
		}

		public function end_title_content( $course, $user ) {
			if ( learn_press_user_has_passed_course( $course->ID, $user->ID ) ) {
				_e( '<span class="course-status passed">Passed</span>', 'learnpress' );
			} else {

			}
		}
	}
	new LP_Profile;
}
