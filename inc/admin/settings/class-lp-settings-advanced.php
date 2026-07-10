<?php
/**
 * Class LP_Settings_Advanced
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes/Settings
 * @version 4.0.0
 */

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Template;

defined( 'ABSPATH' ) || exit;

class LP_Settings_Advanced extends LP_Abstract_Settings_Page {

	public function __construct() {
		$this->id   = 'advanced';
		$this->text = esc_html__( 'Advanced', 'learnpress' );

		parent::__construct();
	}

	public function get_settings( $section = '', $tab = '' ) {

		return parent::get_settings( $section, $tab );
	}

	/**
	 * Settings fields for the general section.
	 *
	 * @return mixed
	 */
	public function get_settings_general() {

		return Config::instance()->get( 'advanced', 'settings' );
	}

	/**
	 * Settings fields for the MCP section.
	 *
	 * @return mixed
	 */
	public function get_settings_mcp() {

		if ( ! self::is_mcp_available() ) {
			return array();
		}
		return Config::instance()->get( 'mcp', 'settings' );
	}

	/**
	 * Settings fields for the Webhook section.
	 *
	 * @return mixed
	 */
	public function get_settings_webhook() {

		return Config::instance()->get( 'webhook', 'settings' );
	}

	/**
	 * Check whether MCP capabilities are available in current WordPress runtime.
	 *
	 * @return bool
	 */
	public static function is_mcp_available(): bool {

		return learn_press_is_mcp_available();
	}

	/**
	 * Backward-compatible alias for MCP availability checks.
	 *
	 * @return bool
	 */
	public static function is_mcp_adapter_active(): bool {

		return self::is_mcp_available();
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
	 * Render Advanced settings sections.
	 *
	 * @param string $section
	 * @param string $tab
	 *
	 * @return void
	 */
	public function admin_page_settings( $section = null, $tab = '' ) {
		parent::admin_page_settings( $section, $tab );

		if ( 'mcp' === $section ) {
			if ( ! self::is_mcp_available() ) {
				$this->render_mcp_unavailable_notice();
				return;
			}
			//parent::admin_page_settings( $section, $tab );

			if ( 'yes' === LP_Settings::get_option( 'enable_mcp_integration', 'no' ) && class_exists( 'LP_Admin_MCP_API_Keys' ) ) {
				LP_Admin_MCP_API_Keys::instance()->render_page();
			}
			return;
		}

		if ( 'webhook' === $section && 'yes' === LP_Settings::get_option( 'enable_webhook_integration', 'no' ) ) {
			LP_Admin_Webhooks::instance()->render_page();
		}
	}

	/**
	 * Save Advanced settings sections.
	 *
	 * @param string $section
	 * @param string $tab
	 *
	 * @return void
	 */
	public function save_settings( $section = null, $tab = '' ) {

		if ( 'mcp' === $section && ! self::is_mcp_available() ) {
			return;
		}
		parent::save_settings( $section, $tab );
	}

	/**
	 * Get sections for advanced settings.
	 *
	 * @return array<string, string>
	 */
	public function get_sections() {

		return array(
			'general' => esc_html__( 'General', 'learnpress' ),
			'mcp'     => esc_html__( 'MCP', 'learnpress' ),
			'webhook' => esc_html__( 'Webhook', 'learnpress' ),
		);
	}
}
return new LP_Settings_Advanced();
