<?php


use LearnPress\Helpers\Config;

/**
 * Class LP_Settings_OpenAi
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Settings
 * @since 4.1.7.3.2
 * @version 1.0.1
 */
class LP_Settings_OpenAi extends LP_Abstract_Settings_Page {
	/**
	 * Construct
	 */
	public function __construct() {
		$this->id   = 'open-ai';
		$this->text = esc_html__( 'LearnPress AI', 'learnpress' );

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
		return Config::instance()->get( 'open-ai-admin', 'settings' );
	}
}

return new LP_Settings_OpenAi();
