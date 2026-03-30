<?php

use LearnPress\Models\UserModel;
use LearnPress\Services\UserService;

defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Submenu_Settings
 */
class LP_Submenu_Settings extends LP_Abstract_Submenu {
	/**
	 * @var LP_Abstract_Settings_Page[]
	 */
	protected $tabs = array();

	/**
	 * LP_Submenu_Settings constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-settings';
		$this->menu_title = esc_html__( 'Settings', 'learnpress' );
		$this->page_title = esc_html__( 'LearnPress Settings', 'learnpress' );
		$this->priority   = 30;
		$this->callback   = [ $this, 'display' ];

		$this->tabs = apply_filters(
			'learn-press/admin/settings-tabs-array',
			array(
				'general'   => include_once LP_PLUGIN_PATH . 'inc/admin/settings/class-lp-settings-general.php',
				'courses'   => new LP_Settings_Courses(),
				'profile'   => include_once LP_PLUGIN_PATH . 'inc/admin/settings/class-lp-settings-profile.php',
				'payments'  => include_once LP_PLUGIN_PATH . 'inc/admin/settings/class-lp-settings-payments.php',
				'emails'    => include_once LP_PLUGIN_PATH . 'inc/admin/settings/class-lp-settings-emails.php',
				'permalink' => include_once LP_PLUGIN_PATH . 'inc/admin/settings/class-lp-settings-permalink.php',
				'advanced'  => include_once LP_PLUGIN_PATH . 'inc/admin/settings/class-lp-settings-advanced.php',
				'open-ai'   => include_once LP_PLUGIN_PATH . 'inc/admin/settings/class-lp-settings-open-ai.php',
			)
		);

		add_action( 'learn-press/admin/page-content-settings', array( $this, 'page_contents' ) );
		add_action( 'learn-press/admin/page-' . $this->_get_page() . '/section-content', array( $this, 'section_content' ) );

		/** Save metabox in LP4 */
		add_action( 'admin_init', array( $this, 'save_settings' ) );

		parent::__construct();
	}

	/**
	 * Display menu content
	 */
	public function page_content() {
		parent::page_content();
	}

	public function page_contents() {
		$active_tab = $this->get_active_tab();

		if ( 'permalink' === $active_tab && isset( $_GET['lp-user-slug-generated'] ) ) {
			$processed = absint( $_GET['lp-user-slug-processed'] ?? 0 );
			$generated = absint( $_GET['lp-user-slug-generated'] ?? 0 );
			$skipped   = absint( $_GET['lp-user-slug-skipped'] ?? 0 );
			$failed    = absint( $_GET['lp-user-slug-failed'] ?? 0 );
			?>
			<div class="notice notice-success">
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: processed users, 2: generated slugs, 3: skipped users, 4: failed users */
							__( 'User slug generation finished. Processed: %1$d, Generated: %2$d, Skipped: %3$d, Failed: %4$d.', 'learnpress' ),
							$processed,
							$generated,
							$skipped,
							$failed
						)
					);
					?>
				</p>
			</div>
			<?php
		}

		$this->tabs[ $active_tab ]->admin_page_settings( $this->get_active_section(), $this->get_sections() );
		?>

		<input type="hidden" name="lp-settings-nonce" value="<?php echo wp_create_nonce( 'lp-settings' ); ?>">
		<p class="lp-admin-settings-buttons">
			<button class="button button-primary"><?php esc_html_e( 'Save settings', 'learnpress' ); ?></button>
		</p>

		<?php
	}

	public function section_content( $section ) {
	}

	/**
	 * Update metabox setting
	 *
	 * @return void
	 * @version 4.0.1
	 * @author ThimPress <nhamdv>
	 */
	public function save_settings() {
		if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR )
			| ! is_admin() || ! isset( $_GET['page'] )
			|| 'learn-press-settings' !== $_GET['page'] ) {
			return;
		}

		$nonce = learn_press_get_request( 'lp-settings-nonce' );

		if ( ! wp_verify_nonce( $nonce, 'lp-settings' ) ) {
			return;
		}

		$active_tab = $this->get_active_tab();

		$this->tabs[ $active_tab ]->save_settings( $this->get_active_section(), $this->get_sections() );

		$redirect_args = array();
		if ( 'permalink' === $active_tab &&
			'yes' === LP_Request::get_param( 'lp_generate_user_slug' ) ) {
			$result        = UserService::instance()->generate_users_pretty_slug();
			$redirect_args = array(
				'lp-user-slug-generated' => $result['generated'],
				'lp-user-slug-processed' => $result['processed'],
				'lp-user-slug-skipped'   => $result['skipped'],
				'lp-user-slug-failed'    => $result['failed'],
			);
		}

		do_action( 'learn-press/update-settings/updated', $this );

		// Clear cache settings
		$lp_settings_cache = new LP_Settings_Cache( true );
		$lp_settings_cache->clean_lp_settings();

		// Clear cache lp rewrite rules
		//$lp_settings_cache->clean_lp_rewrite_rules();

		// Flush rewrite rules after save settings.
		if ( isset( $_REQUEST['tab'] ) && 'permalink' === $_REQUEST['tab'] ) {
			flush_rewrite_rules();
		}

		// Filter redirect
		$redirect = apply_filters(
			'learn-press/update-settings/redirect',
			esc_url_raw(
				add_query_arg(
					array_merge(
						array( 'settings-updated' => 'yes' ),
						$redirect_args
					)
				)
			),
			$this
		);
		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit();
		}
	}

	public function save() {
	}
}

return new LP_Submenu_Settings();
