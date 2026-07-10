<?php
/**
 * Class LP_Settings_Mcp
 *
 * @package LearnPress/Admin/Classes/Settings
 * @version 1.0.0
 */

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Template;

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
	 * Render MCP unavailable notice.
	 *
	 * @return void
	 */
	protected function render_mcp_unavailable_notice(): void {
		Template::print_message(
			sprintf(
				'This feature requires the %s and WP from 6.9+. Please install and activate it to access the settings. %s',
				sprintf(
					'<a href="%s">%s</a>',
					esc_url_raw( 'https://github.com/wordpress/mcp-adapter/' ),
					esc_html__( 'MCP Adapter plugin', 'learnpress' )
				),
				sprintf(
					'<a href="%s">%s</a>',
					esc_url_raw( 'https://learnpresslms.com/docs/learnpress-developer-documentation/model-context-protocol-mcp-integration/' ),
					esc_html__( 'Learn more', 'learnpress' )
				)
			),
			'warning'
		);
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
		if ( ! learn_press_is_mcp_available() ) {
			self::render_mcp_unavailable_notice();
			return;
		}

		parent::admin_page_settings( $section, $tab );

		if ( 'yes' === LP_Settings::get_option( 'enable_mcp_integration', 'no' ) && class_exists( 'LP_Admin_MCP_API_Keys' ) ) {
			LP_Admin_MCP_API_Keys::instance()->render_page();
		}
	}

	/**
	 * Save MCP settings when MCP support is available.
	 *
	 * @param string $section
	 * @param string $tab
	 *
	 * @return void
	 */
	public function save_settings( $section = null, $tab = '' ) {
		parent::save_settings( $section, $tab );
	}
}

return new LP_Settings_Mcp();
