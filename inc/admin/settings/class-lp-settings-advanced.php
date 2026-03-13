<?php
/**
 * Class LP_Settings_Profile
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes/Settings
 * @version 4.0.0
 */

use LearnPress\Helpers\Config;

defined( 'ABSPATH' ) || exit;

class LP_Settings_Advanced extends LP_Abstract_Settings_Page {

	public function __construct() {
		$this->id   = 'advanced';
		$this->text = esc_html__( 'Advanced', 'learnpress' );

		parent::__construct();
	}

	public function get_settings( $section = '', $tab = '' ) {
		if ( 'mcp-keys' === $section ) {
			return array();
		}

		return Config::instance()->get( 'advanced', 'settings' );
	}

	/**
	 * Get sections for advanced settings.
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

return new LP_Settings_Advanced();
