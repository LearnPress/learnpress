<?php

use LearnPress\MCP\Auth\ApiKeysRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Admin controller for LearnPress MCP API keys UI and actions.
 */
class LP_Admin_MCP_API_Keys {
	/**
	 * @var self|null
	 */
	protected static $instance;

	/**
	 * @var ApiKeysRepository
	 */
	protected $repository;

	/**
	 * @var string
	 */
	protected $required_capability = 'manage_options';

	/**
	 * Get singleton instance for MCP API keys admin controller.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register admin hooks and required table dependency.
	 *
	 * @return void
	 */
	protected function __construct() {
		$this->repository = new ApiKeysRepository();

		add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'localize_admin_script' ) );

		require_once LP_PLUGIN_PATH . 'inc/admin/class-lp-admin-mcp-api-keys-table-list.php';
	}

	/**
	 * Localize MCP API key settings and i18n labels to admin JavaScript.
	 *
	 * Data is exposed under `window.lpMcpApiKeysSettings` and consumed by
	 * `lp-admin-mcp-api-keys` runtime script.
	 *
	 * @return void
	 */
	public function localize_admin_script(): void {
		if ( ! $this->is_mcp_adapter_active() || ! wp_script_is( 'lp-admin-mcp-api-keys', 'enqueued' ) ) {
			return;
		}

		wp_localize_script(
			'lp-admin-mcp-api-keys',
			'lpMcpApiKeysSettings',
			array(
				'is_mcp_keys_section' => $this->is_mcp_keys_settings_screen(),
				'actions' => array(
					'create' => 'mcp_create_api_key',
				),
				'i18n'     => array(
					'processing'      => __( 'Processing...', 'learnpress' ),
					'created'         => __( 'API key created.', 'learnpress' ),
					'request_failed'  => __( 'Request failed. Please try again.', 'learnpress' ),
					'confirm_revoke'  => __( 'Revoke this API key?', 'learnpress' ),
					'copy_success'    => __( 'Copied.', 'learnpress' ),
					'copy_fallback'   => __( 'Copy this value manually.', 'learnpress' ),
				),
			)
		);
	}

	/**
	 * Render MCP API key management screen inside LearnPress settings.
	 *
	 * Prepares list-table data and user options before loading the section template.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( $this->required_capability ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to manage MCP API keys.', 'learnpress' ) );
		}
		if ( ! $this->is_mcp_adapter_active() ) {
			return;
		}

		$table = new LP_Admin_MCP_API_Keys_Table_List( $this->repository );
		$table->prepare_items();

		$users        = get_users(
			array(
				'fields'  => array( 'ID', 'user_login', 'display_name' ),
				'orderby' => 'display_name',
				'order'   => 'ASC',
				'number'  => 200,
			)
		);
		$message_code = sanitize_key( $_GET['lp_mcp_notice'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$message      = $this->notice_from_code( $message_code );

		require LP_PLUGIN_PATH . 'inc/admin/views/settings/mcp-api-keys-form.php';
	}

	/**
	 * Process row and bulk revoke actions submitted from the list table.
	 *
	 * Supported actions:
	 * - `lp_mcp_key_action = revoke`
	 * - `action/action2 = bulk-revoke`
	 *
	 * @return void
	 */
	public function handle_admin_actions(): void {
		if ( ! $this->is_mcp_keys_settings_screen() || ! current_user_can( $this->required_capability ) || ! $this->is_mcp_adapter_active() ) {
			return;
		}

		$action = sanitize_key( $_REQUEST['lp_mcp_key_action'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'revoke' === $action ) {
			$key_id = absint( $_REQUEST['key_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $key_id <= 0 ) {
				return;
			}

			check_admin_referer( 'lp_mcp_revoke_key_' . $key_id );
			$this->repository->revoke_key( $key_id );
			$this->redirect_with_notice( 'revoked' );
		}

		$bulk_action = '';
		if ( isset( $_REQUEST['action'] ) && '-1' !== $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$bulk_action = sanitize_key( wp_unslash( $_REQUEST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_REQUEST['action2'] ) && '-1' !== $_REQUEST['action2'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$bulk_action = sanitize_key( wp_unslash( $_REQUEST['action2'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( 'bulk-revoke' !== $bulk_action ) {
			return;
		}

		check_admin_referer( 'lp_mcp_bulk_revoke_action', 'lp_mcp_bulk_revoke_nonce' );

		$key_ids = $_REQUEST['key_ids'] ?? array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key_ids = is_array( $key_ids ) ? $key_ids : array();
		$deleted = $this->repository->revoke_keys( $key_ids );

		$this->redirect_with_notice( $deleted > 0 ? 'bulk_revoked' : 'no_selection' );
	}

	/**
	 * Redirect back to MCP keys section with notice code.
	 *
	 * @param string $notice_code Notice key used by `notice_from_code`.
	 *
	 * @return void
	 */
	protected function redirect_with_notice( string $notice_code ): void {
		$url = add_query_arg(
			array(
				'page'          => 'learn-press-settings',
				'tab'           => 'mcp',
				'lp_mcp_notice' => $notice_code,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Convert notice code to display payload.
	 *
	 * @param string $code Notice code from query string.
	 *
	 * @return array<string, string>|null
	 */
	protected function notice_from_code( string $code ): ?array {
		$map = array(
			'revoked'      => array( 'type' => 'success', 'message' => __( 'API key revoked.', 'learnpress' ) ),
			'bulk_revoked' => array( 'type' => 'success', 'message' => __( 'Selected API keys revoked.', 'learnpress' ) ),
			'no_selection' => array( 'type' => 'warning', 'message' => __( 'No API keys selected.', 'learnpress' ) ),
		);

		return $map[ $code ] ?? null;
	}

	/**
	 * Check whether MCP Adapter plugin is active.
	 *
	 * @return bool
	 */
	protected function is_mcp_adapter_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return function_exists( 'is_plugin_active' ) && is_plugin_active( 'mcp-adapter/mcp-adapter.php' );
	}

	/**
	 * Is current request LearnPress MCP settings screen.
	 *
	 * @return bool
	 */
	protected function is_mcp_keys_settings_screen(): bool {
		$page    = sanitize_key( $_REQUEST['page'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab     = sanitize_key( $_REQUEST['tab'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return 'learn-press-settings' === $page
			&& 'mcp' === $tab;
	}

}
