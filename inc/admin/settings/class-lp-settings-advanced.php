<?php
/**
 * Class LP_Settings_Advanced
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

		if ( ! self::is_mcp_adapter_active() ) {
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
	 * Check whether MCP Adapter plugin is active.
	 *
	 * @return bool
	 */
	public static function is_mcp_adapter_active(): bool {

		if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return function_exists( 'is_plugin_active' ) && is_plugin_active( 'mcp-adapter/mcp-adapter.php' );
	}

	/**
	 * Render missing MCP Adapter requirement notice.
	 *
	 * @return void
	 */
	protected function render_missing_adapter_notice(): void {

		$learn_more = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://github.com/WordPress/mcp-adapter' ),
			esc_html__( 'Learn more', 'learnpress' )
		);

		$message = sprintf(
			'%1$s %2$s',
			esc_html__( 'This feature requires the MCP Adapter plugin. Please install and activate it to access the settings. ', 'learnpress' ),
			$learn_more
		);
		?>
		<div class="notice notice-warning inline">
			<p><?php echo wp_kses_post( $message ); ?></p>
		</div>
		
		<?php
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

		if ( 'mcp' === $section ) {
			if ( ! self::is_mcp_adapter_active() ) {
				$this->render_missing_adapter_notice();
				return;
			}

			parent::admin_page_settings( $section, $tab );

			if ( class_exists( 'LP_Admin_MCP_API_Keys' ) ) {
				LP_Admin_MCP_API_Keys::instance()->render_page();
			}
			return;
		}

		parent::admin_page_settings( $section, $tab );

		if ( 'webhook' === $section ) {
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

		if ( 'mcp' === $section && ! self::is_mcp_adapter_active() ) {
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
