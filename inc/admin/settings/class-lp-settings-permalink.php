<?php

/**
 * Class LP_Settings_Permalink
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Settings
 * @since 4.1.7.3.2
 * @version 1.0.0
 */
class LP_Settings_Permalink extends LP_Abstract_Settings_Page {
	/**
	 * Construct
	 */
	public function __construct() {
		$this->id   = 'permalink';
		$this->text = esc_html__( 'Permalinks', 'learnpress' );

		parent::__construct();
	}

	/**
	 * Return fields for settings page.
	 *
	 * @param string $section
	 * @param string $tab
	 *
	 * @return mixed
	 */
	public function get_settings( $section = '', $tab = '' ) {
		return require_once LP_PLUGIN_PATH . 'config/settings/permalink.php';
	}

}
return new LP_Settings_Permalink();
