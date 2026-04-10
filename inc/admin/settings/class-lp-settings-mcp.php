<?php
/**
 * Class LP_Settings_Mcp
 *
 * @package LearnPress/Admin/Classes/Settings
 * @version 1.0.0
 */

use LearnPress\Helpers\Config;

defined( 'ABSPATH' ) || exit;

class LP_Settings_Mcp extends LP_Abstract_Settings_Page {
	/**
	 * Construct.
	 */
	public function __construct() {
		$this->id   = 'mcp';
		$this->text = esc_html__( 'MCP', 'learnpress' );

		parent::__construct();
	}

	public function get_settings( $section = '', $tab = '' ) {
		return Config::instance()->get( 'mcp', 'settings' );
	}

	/**
	 * Render MCP settings and API keys on one page (no sub-tabs).
	 *
	 * @param string $section
	 * @param string $tab
	 *
	 * @return void
	 */
	public function admin_page_settings( $section = null, $tab = '' ) {
		parent::admin_page_settings( $section, $tab );

		if ( class_exists( 'LP_Admin_MCP_API_Keys' ) ) {
			LP_Admin_MCP_API_Keys::instance()->render_page();
		}
	}
}

return new LP_Settings_Mcp();
