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

	/**
	 * Return settings fields by section.
	 *
	 * @param string $section
	 * @param string $tab
	 *
	 * @return array
	 */
	public function get_settings( $section = '', $tab = '' ) {
		if ( 'mcp-keys' === $section ) {
			return array();
		}

		return Config::instance()->get( 'mcp', 'settings' );
	}

	/**
	 * Get sections for MCP settings.
	 *
	 * @return array<string, string>
	 */
	public function get_sections() {
		return array(
			'general'  => esc_html__( 'General', 'learnpress' ),
			'mcp-keys' => esc_html__( 'MCP API Keys', 'learnpress' ),
		);
	}

	/**
	 * Render section content.
	 *
	 * @param string $section
	 * @param string $tab
	 *
	 * @return void
	 */
	public function admin_page_settings( $section = null, $tab = '' ) {
		if ( 'mcp-keys' === $section && class_exists( 'LP_Admin_MCP_API_Keys' ) ) {
			LP_Admin_MCP_API_Keys::instance()->render_page();
			return;
		}

		parent::admin_page_settings( $section, $tab );
	}
}

return new LP_Settings_Mcp();
