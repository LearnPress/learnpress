<?php

/**
 * Class LP_Settings_Profile
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes/Settings
 * @version 1.0
 */

use LearnPress\Helpers\Config;

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
		return Config::instance()->get( 'profile', 'settings' );
	}
}

return new LP_Settings_Profile();
