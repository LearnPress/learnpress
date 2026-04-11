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
		if ( ! self::is_mcp_adapter_active() ) {
			return array();
		}

		return Config::instance()->get( 'mcp', 'settings' );
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
	 * Render MCP settings and API keys on one page (no sub-tabs).
	 *
	 * @param string $section
	 * @param string $tab
	 *
	 * @return void
	 */
	public function admin_page_settings( $section = null, $tab = '' ) {
		if ( ! self::is_mcp_adapter_active() ) {
			$this->render_missing_adapter_notice();
			return;
		}

		parent::admin_page_settings( $section, $tab );

		if ( class_exists( 'LP_Admin_MCP_API_Keys' ) ) {
			LP_Admin_MCP_API_Keys::instance()->render_page();
		}
	}

	/**
	 * Save MCP settings when MCP Adapter is available.
	 *
	 * @param string $section
	 * @param string $tab
	 *
	 * @return void
	 */
	public function save_settings( $section = null, $tab = '' ) {
		if ( ! self::is_mcp_adapter_active() ) {
			return;
		}

		parent::save_settings( $section, $tab );
	}
}

return new LP_Settings_Mcp();
