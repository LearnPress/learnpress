<?php

/**
 * Class LP_Settings_Profile
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes/Settings
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'LP_Settings_Profile', false ) ) {
	return new LP_Settings_Profile();
}

class LP_Settings_Profile extends LP_Abstract_Settings_Page {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id   = 'profile';
		$this->text = esc_html__( 'Profile', 'learnpress' );

		parent::__construct();
	}

	/**
	 * @param string $section
	 * @param string $tab
	 *
	 * @return array
	 */
	public function get_settings( $section = null, $tab = null ) {
		return require_once LP_PLUGIN_PATH . 'config/settings/profile.php';
	}
}

return new LP_Settings_Profile();
