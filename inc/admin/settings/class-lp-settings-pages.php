<?php

/**
 * Class LP_Settings_Pages
 *
 * Settings pages
 *
 * @since 3.0
 */
class LP_Settings_Pages extends LP_Abstract_Settings_Page {
	/**
	 * LP_Settings_Pages constructor.
	 */
	public function __construct() {
		$this->id   = 'pages';
		$this->text = __( 'Pages', 'learnpress' );

		parent::__construct();
	}

	/**
	 * Main sections
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'become_a_teacher' => __( 'Become a teacher', 'learnpress' )
		);

		return apply_filters( 'learn-press/admin/settings-pages/sections', $sections, $this );
	}

	public function get_settings_become_a_teacher() {
		return apply_filters(
			'learn-press/admin/settings-pages/become-a-teacher',
			array(
				array(
					'title'   => __( 'Become a teacher page', 'learnpress' ),
					'id'      => 'become_a_teacher_page_id',
					'default' => '',
					'type'    => 'pages-dropdown'
				),
				array(
					'title'   => __( 'Instructors registration', 'learnpress' ),
					'desc'    => __( 'Create option for instructors registration.', 'learnpress' ),
					'id' => 'instructor_registration',
					'default' => 'no',
					'type'    => 'yes-no'
				)
			),
			$this
		);
	}

	/**
	 * Setting fields.
	 *
	 * @param string $section
	 *
	 * @return array
	 */
	public function _get_settings( $section ) {
		$settings = $this->get_settings();
		$get      = false;
		$return   = array();
		foreach ( $settings as $k => $v ) {
			if ( ! empty( $v['section'] ) ) {
				if ( $get ) {
					break;
				}
				if ( $v['section'] == $section ) {
					$get = true;
					continue;
				}
			}
			if ( $get ) {
				$return[] = $v;
			}
		}

		return $return;
	}

	public function output_section_profile() {
		$view = learn_press_get_admin_view( 'settings/pages/profile.php' );
		require_once $view;
	}

	public function output_section_quiz() {
		$view = learn_press_get_admin_view( 'settings/pages/quiz.php' );
		require_once $view;
	}

	public function output_section_become_a_teacher() {
		$view = learn_press_get_admin_view( 'settings/pages/become-a-teacher.php' );
		require_once $view;
	}
}

return new LP_Settings_Pages();