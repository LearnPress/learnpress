<?php

use LearnPress\Webhook\WebhookEvents;

defined( 'ABSPATH' ) || exit;

/**
 * Admin controller for LearnPress webhook management.
 */
class LP_Admin_Webhooks {
	/**
	 * @var self|null
	 */
	protected static $instance;

	/**
	 * @var string
	 */
	protected $required_capability = 'manage_options';

	/**
	 * Get singleton instance.
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
	 * Register dependencies and hooks.
	 */
	protected function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'localize_admin_script' ) );

		require_once LP_PLUGIN_PATH . 'inc/admin/class-lp-admin-webhooks-table-list.php';
	}

	/**
	 * Localize webhook CRUD settings for admin JavaScript.
	 *
	 * @return void
	 */
	public function localize_admin_script(): void {
		if ( ! $this->is_webhook_settings_screen() || ! $this->is_webhook_integration_enabled() || ! wp_script_is( 'lp-admin-webhooks', 'enqueued' ) ) {
			return;
		}
		wp_localize_script(
			'lp-admin-webhooks',
			'lpWebhooksSettings',
			array(
				'is_webhook_section' => true,
				'actions'            => array(
					'create'     => 'create_webhook',
					'update'     => 'update_webhook',
					'delete'     => 'delete_webhook',
					'regenerate' => 'regenerate_webhook_secret',
				),
				'i18n'               => array(
					'processing'                => __( 'Processing...', 'learnpress' ),
					'request_failed'            => __( 'Request failed. Please try again.', 'learnpress' ),
					'confirm_delete'            => __( 'Delete this webhook?', 'learnpress' ),
					'confirm_regenerate'        => __( 'Regenerate this webhook secret?', 'learnpress' ),
					'copy_success'              => __( 'Copied.', 'learnpress' ),
					'copy_fallback'             => __( 'Copy this value manually.', 'learnpress' ),
					'create_title'              => __( 'Create Webhook', 'learnpress' ),
					'created_title'             => __( 'Webhook Created', 'learnpress' ),
					'edit_title'                => __( 'Edit Webhook', 'learnpress' ),
					'create_button'             => __( 'Create Webhook', 'learnpress' ),
					'update_button'             => __( 'Update Webhook', 'learnpress' ),
					'secret_create_placeholder' => __( 'Leave blank to auto-generate a secret.', 'learnpress' ),
					'secret_edit_placeholder'   => __( 'Leave blank to keep the current secret.', 'learnpress' ),
				),
			)
		);
	}

	/**
	 * Render webhook management after Advanced webhook settings.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( $this->required_capability ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to manage webhooks.', 'learnpress' ) );
		}
		if ( ! $this->is_webhook_integration_enabled() ) {
			return;
		}

		$table = new LP_Admin_Webhooks_Table_List();
		$table->prepare_items();
		$events = WebhookEvents::all();

		require LP_PLUGIN_PATH . 'inc/admin/views/settings/webhooks-form.php';
	}

	/**
	 * Check whether current request is the Advanced webhook section.
	 *
	 * @return bool
	 */
	protected function is_webhook_settings_screen(): bool {
		$page    = sanitize_key( $_REQUEST['page'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab     = sanitize_key( $_REQUEST['tab'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$section = sanitize_key( $_REQUEST['section'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return 'learn-press-settings' === $page && 'advanced' === $tab && 'webhook' === $section;
	}

	/**
	 * Check whether webhook integration is enabled.
	 *
	 * @return bool
	 */
	protected function is_webhook_integration_enabled(): bool {
		return 'yes' === LP_Settings::get_option( 'enable_webhook_integration', 'no' );
	}
}
