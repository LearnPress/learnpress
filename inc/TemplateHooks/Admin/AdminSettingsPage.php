<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\Services\UserService;
use LP_Helper;
use LP_Request;
use LP_Settings_Addons;
use LP_Settings_Advanced;
use LP_Settings_Cache;
use LP_Settings_Courses;

defined( 'ABSPATH' ) || exit();

/**
 * Renders the LearnPress Settings admin page (tabs + sections),
 * replacing the former LP_Submenu_Settings/LP_Abstract_Submenu classes.
 *
 * @since 4.2.8
 */
class AdminSettingsPage {
	use Singleton;

	const PAGE_ID = 'learn-press-settings';

	/**
	 * Cached tabs for the current request.
	 *
	 * @var array|null
	 */
	private $tabs = null;

	/**
	 * Singleton initialization hook.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'learn-press/admin/page-content-sections', array( $this, 'output_section_nav' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		add_action( 'learn-press/admin/page-content-settings', array( $this, 'page_contents' ) );
		add_action( 'learn-press/admin/page-settings/section-content', array( $this, 'section_content' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ) );
	}

	/**
	 * Get settings tabs (cached for the current request).
	 *
	 * @return array
	 */
	public function get_tabs(): array {
		if ( null !== $this->tabs ) {
			return $this->tabs;
		}

		$tabs = apply_filters(
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
				'addons'    => include_once LP_PLUGIN_PATH . 'inc/admin/settings/class-lp-settings-addons.php',
			)
		);

		// Check if no addon config on tab addons then remove it.
		if ( isset( $tabs['addons'] )
			&& $tabs['addons'] instanceof LP_Settings_Addons
			&& ! $tabs['addons']->has_sections() ) {
			unset( $tabs['addons'] );
		}

		$this->tabs = apply_filters( 'learn-press/submenu-' . self::PAGE_ID . '-heading-tabs', $tabs );

		return $this->tabs;
	}

	/**
	 * Get active tab by checking ?tab=tab-name.
	 *
	 * @return bool|string
	 */
	public function get_active_tab() {
		$tabs = $this->get_tabs();

		if ( ! $tabs ) {
			return false;
		}

		$tab = LP_Helper::sanitize_params_submitted( $_REQUEST['tab'] ?? '' );

		if ( empty( $tab ) || empty( $tabs[ $tab ] ) ) {
			$tab_keys = array_keys( $tabs );
			$tab      = reset( $tab_keys );
		}

		return $tab;
	}

	/**
	 * Get sections of the active tab.
	 *
	 * @return array
	 */
	public function get_sections(): array {
		$tabs       = $this->get_tabs();
		$active_tab = $this->get_active_tab();
		$sections   = array();

		if ( ! empty( $tabs[ $active_tab ] ) && is_callable( array( $tabs[ $active_tab ], 'get_sections' ) ) ) {
			$sections = call_user_func( array( $tabs[ $active_tab ], 'get_sections' ) );
		}

		return apply_filters( 'learn-press/submenu-sections', $sections );
	}

	/**
	 * Get active section by checking ?section=section-name.
	 *
	 * @return bool|string
	 */
	public function get_active_section() {
		$sections = $this->get_sections();

		if ( ! $sections ) {
			return false;
		}

		$section = LP_Helper::sanitize_params_submitted( $_REQUEST['section'] ?? '' );

		if ( empty( $section ) || empty( $sections[ $section ] ) ) {
			$section_keys = array_keys( $sections );
			$section      = reset( $section_keys );
		}

		return $section;
	}

	/**
	 * Output section navigation. Hooked to 'learn-press/admin/page-content-sections'.
	 *
	 * @return void
	 */
	public function output_section_nav() {
		if ( self::PAGE_ID !== LP_Request::get_param( 'page' ) ) {
			return;
		}

		$active_section = $this->get_active_section();
		$sections       = $this->get_sections();

		if ( ! $sections ) {
			return;
		}
		?>

		<ul class="lp-admin-tab-navs">
			<?php foreach ( $sections as $slug => $section ) : ?>
				<?php
				$active_class  = ( $slug === $active_section ) ? ' nav-section-active' : '';
				$section_title = apply_filters( 'learn-press/admin/submenu-section-title', $section, $slug );
				?>

				<li class="nav-section<?php echo esc_attr( $active_class ); ?>">
					<?php if ( $active_class ) : ?>
						<span><?php echo wp_kses_post( $section_title ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url_raw( remove_query_arg( 'sub-section', add_query_arg( 'section', $slug ) ) ); ?>"><?php echo wp_kses_post( $section_title ); ?></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php
	}

	/**
	 * Append body classes on the Settings page.
	 *
	 * @param string $classes
	 *
	 * @return string
	 */
	public function body_class( $classes ) {
		if ( self::PAGE_ID !== LP_Request::get_param( 'page' ) ) {
			return $classes;
		}

		$classes   = $classes ? explode( ' ', $classes ) : array();
		$classes[] = 'learnpress';
		$classes[] = 'lp-submenu-settings';
		$classes   = array_unique( array_filter( $classes ) );

		return implode( ' ', $classes );
	}

	/**
	 * Render the content of the active tab, hooked to 'learn-press/admin/page-content-settings'.
	 *
	 * @return void
	 */
	public function page_contents() {
		$tabs       = $this->get_tabs();
		$active_tab = $this->get_active_tab();
		$section    = $this->get_active_section();

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

		$tabs[ $active_tab ]->admin_page_settings( $section, $this->get_sections() );

		$hide_save_button = false;
		if ( 'advanced' === $active_tab && 'mcp' === $section && class_exists( 'LP_Settings_Advanced' ) ) {
			$hide_save_button = ! learn_press_is_mcp_available();
		}
		?>

		<?php if ( ! $hide_save_button ) : ?>
			<input type="hidden" name="lp-settings-nonce" value="<?php echo wp_create_nonce( 'lp-settings' ); ?>">
			<p class="lp-admin-settings-buttons">
				<button class="button button-primary"><?php esc_html_e( 'Save settings', 'learnpress' ); ?></button>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Section content, hooked to 'learn-press/admin/page-settings/section-content'.
	 *
	 * @param string $section
	 *
	 * @return void
	 */
	public function section_content( $section ) {
	}

	/**
	 * Render tab content wrapper: sections nav + active tab content dispatch.
	 *
	 * @return void
	 */
	private function page_content() {
		do_action( 'learn-press/admin/page-content-sections' );

		echo '<div class="lp-admin-tab-content">';

		if ( $this->get_tabs() ) {
			$tab = $this->get_active_tab();

			do_action( 'learn-press/admin/before-page-content-sections', 'settings', $tab );
			do_action( 'learn-press/admin/page-content-settings', $tab );
			do_action( 'learn-press/admin/page-content-settings/' . $tab );
			do_action( 'learn-press/admin/after-page-content-sections', 'settings', $tab );
		}

		echo '</div>';
	}

	/**
	 * Main callback for the Settings admin page. Used as 'callback' in
	 * config/wp-menus.php, same pattern as the other menus.
	 *
	 * @return void
	 */
	public function html_page() {
		$tabs       = $this->get_tabs();
		$active_tab = $this->get_active_tab();

		$flat_tabs = array();
		foreach ( $tabs as $tab_slug => $tab_obj ) {
			$tab_id               = $tab_obj->id ?? $tab_slug;
			$tab_title            = $tab_obj->text ?? $tab_slug;
			$flat_tabs[ $tab_id ] = apply_filters( 'learn-press/admin/submenu-heading-tab-title', $tab_title, $tab_id );
		}

		ob_start();
		do_action( 'learn-press/admin/heading-icon', $active_tab );
		echo esc_html__( 'Settings', 'learnpress' );
		do_action( 'learn-press/admin/heading-title', $active_tab );
		$title = ob_get_clean();

		$classes  = array( 'lp-admin-tabs' );
		$sections = $this->get_sections();

		$has_sections = $sections && sizeof( $sections ) > 1;
		$has_sections = apply_filters( 'learn-press/admin/submenu-has-sections', $has_sections, $sections, $active_tab );

		if ( $has_sections ) {
			$classes[] = 'has-sections';
		}

		ob_start();
		$this->page_content();
		$page_content = ob_get_clean();

		$wrapper = array(
			sprintf(
				'<form class="%s" method="post" enctype="multipart/form-data">',
				esc_attr( implode( ' ', $classes ) )
			) => '</form>',
		);

		echo AdminTemplate::html_on_wp_admin_screen(
			array(
				'tabs'    => $flat_tabs,
				'content' => Template::instance()->nest_elements( $wrapper, $page_content ),
				'title'   => $title,
				'id'      => self::PAGE_ID,
			)
		);
	}

	/**
	 * Save settings of the active tab. Hooked to 'admin_init'.
	 *
	 * @return void
	 */
	public function save_settings() {
		if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR )
			|| ! is_admin() || ! isset( $_GET['page'] )
			|| self::PAGE_ID !== $_GET['page'] ) {
			return;
		}

		$nonce = learn_press_get_request( 'lp-settings-nonce' );

		if ( ! wp_verify_nonce( $nonce, 'lp-settings' ) ) {
			return;
		}

		$tabs       = $this->get_tabs();
		$active_tab = $this->get_active_tab();

		$tabs[ $active_tab ]->save_settings( $this->get_active_section(), $this->get_sections() );

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

		do_action( 'learn-press/update-settings/updated' );

		// Clear cache settings.
		$lp_settings_cache = new LP_Settings_Cache( true );
		$lp_settings_cache->clean_lp_settings();

		// Flush rewrite rules after save settings.
		if ( isset( $_REQUEST['tab'] ) && 'permalink' === $_REQUEST['tab'] ) {
			flush_rewrite_rules();
		}

		// Filter redirect.
		$redirect = apply_filters(
			'learn-press/update-settings/redirect',
			esc_url_raw(
				add_query_arg(
					array_merge(
						array( 'settings-updated' => 'yes' ),
						$redirect_args
					)
				)
			)
		);

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit();
		}
	}
}
