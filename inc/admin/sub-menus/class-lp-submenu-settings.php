<?php
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
	 * @version 4.0.0
	 * @author ThimPress <nhamdv>
	 */
	public function save_settings() {
		if ( ! is_admin() || ! isset( $_GET['page'] ) || 'learn-press-settings' !== $_GET['page'] ) {
			return;
		}

		$nonce = learn_press_get_request( 'lp-settings-nonce' );

		if ( ! wp_verify_nonce( $nonce, 'lp-settings' ) ) {
			return;
		}

		$active_tab = $this->get_active_tab();

		$this->tabs[ $active_tab ]->save_settings( $this->get_active_section(), $this->get_sections() );

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
		$redirect = apply_filters( 'learn-press/update-settings/redirect', esc_url_raw( add_query_arg( 'settings-updated', 'yes' ) ), $this );
		if ( $redirect ) {
			wp_redirect( $redirect );
			exit();
		}
	}

	public function save() {
	}
}

return new LP_Submenu_Settings();
