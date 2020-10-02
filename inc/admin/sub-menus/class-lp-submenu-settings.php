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
		$this->menu_title = __( 'Settings', 'learnpress' );
		$this->page_title = __( 'LearnPress Settings', 'learnpress' );
		$this->priority   = 30;

		$this->tabs = learn_press_settings_tabs_array();

		$this->init_tab();

		add_action( 'learn-press/admin/page-content-settings', array( $this, 'page_contents' ) );
		add_action( 'learn-press/admin/page-' . $this->_get_page() . '/section-content', array( $this, 'section_content' ) );
		add_action( 'admin_init', array( $this, 'maybe_save_settings' ) );
		add_filter( 'rwmb_field_meta', array( $this, 'field_meta' ), 10, 2 );

		/** Save metabox in LP4 */
		add_action( 'admin_init', array( $this, 'save_settings' ) );

		parent::__construct();
	}

	public function field_meta( $meta, $field ) {
		if ( ! empty( $field['learn-press-settings'] ) ) {
			$meta = $field['std'];
		}

		return $meta;
	}

	protected function init_tab() {
		$active_tab = $this->get_active_tab();

		if ( $active_tab ) {
			switch ( $active_tab ) {
				case 'payments':
					$this->sections = '';
					break;
				case 'emails':
					$sections       = array(
						'new_course' => __( 'New course', 'learnpress' ),
					);
					$this->sections = apply_filters( 'learn-press/admin/page-settings/emails/sections', $sections );
					break;
				default:
					do_action( 'learn-press/admin/page-settings/init', $active_tab, $this );
			}
		}
	}

	/**
	 * Display menu content
	 */
	public function page_content() {
		parent::page_content();
	}

	public function page_contents() {
		$active_tab = $this->get_active_tab();

		// Use custom metabox in LP4
		if ( $active_tab === 'profile' ) {
			$this->tabs[ $active_tab ]->admin_page_settings( $this->get_active_section(), $this->get_sections() );
		} else {
			$this->tabs[ $active_tab ]->admin_page( $this->get_active_section(), $this->get_sections() );
		}
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

		// Use custom metabox in LP4
		$active_tab = $this->get_active_tab();

		if ( $active_tab !== 'profile' ) {
			return;
		}

		$this->tabs[ $active_tab ]->save_settings( $this->get_active_section(), $this->get_sections() );

		flush_rewrite_rules();

		do_action( 'learn-press/update-settings/updated', $this );

		// Filter redirect
		$redirect = apply_filters( 'learn-press/update-settings/redirect', add_query_arg( 'settings-updated', 'yes' ), $this );

		if ( $redirect ) {
			wp_redirect( $redirect );
			exit();
		}
	}

	/**
	 * Save settings values upon admin init.
	 */
	public function maybe_save_settings() {
		$nonce = learn_press_get_request( 'lp-settings-nonce' );

		if ( ! wp_verify_nonce( $nonce, 'lp-settings' ) ) {
			return;
		}

		$active_tab = $this->get_active_tab();

		// Use custom metabox in LP4
		if ( $active_tab === 'profile' ) {
			return;
		}

		if ( ! empty( $_POST ) ) {
			$exclude_options = apply_filters( 'learn-press/update-settings/exclude-vars', array( 'lp-settings-nonce' ) );
			settype( $exclude_options, 'array' );
			$postdata = array_diff_key( $_POST, array_flip( $exclude_options ) );

			foreach ( $postdata as $key => $value ) {
				if ( false !== strpos( $key, 'learn_press_' ) ) {
					if ( apply_filters( 'learn-press/update-settings/' . $key, true ) ) {
						$value = apply_filters( 'learn-press/update-settings/settings-value', $value, $key, $postdata );
						update_option( $key, $value );
					}
				}
			}

			flush_rewrite_rules();
		}
		do_action( 'learn-press/update-settings/updated', $this );

		// Filter redirect
		$redirect = apply_filters( 'learn-press/update-settings/redirect', add_query_arg( 'settings-updated', 'yes' ), $this );

		if ( $redirect ) {
			wp_redirect( $redirect );
			exit();
		}
	}

	public function save() {

	}
}

return new LP_Submenu_Settings();
