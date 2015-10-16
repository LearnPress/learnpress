<?php

/**
 * Class LP_Settings_Pages
 */
class LP_Settings_Pages extends LP_Settings_Base {
	function __construct() {
		$this->id   = 'pages';
		$this->text = __( 'Pages', 'learn_press' );

		parent::__construct();
	}

	function get_sections() {
		$sections = array(
			'general' => __( 'General', 'learn_press' )
		);
		return $sections = apply_filters( 'learn_press_settings_sections_' . $this->id, $sections );
	}


	function output_section_general() {
		$view = learn_press_get_admin_view( 'settings/pages.php' );
		require_once $view;
	}
}

new LP_Settings_Pages();