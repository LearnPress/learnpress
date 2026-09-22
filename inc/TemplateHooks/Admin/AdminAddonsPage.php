<?php

namespace LearnPress\TemplateHooks\Admin;

use DateTime;
use Exception;
use LearnPress\Helpers\Response;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\Services\AddonService;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Helper;
use LP_Settings;
use stdClass;
use Throwable;

defined( 'ABSPATH' ) || exit();

/**
 * Admin Add-ons page renderer.
 *
 * @since 4.2.8
 * @version 1.0.2
 */
class AdminAddonsPage {
	use Singleton;

	/**
	 * Singleton initialization hook.
	 *
	 * @return void
	 */
	public function init(): void {}

	/**
	 * Render the LearnPress Add-ons page.
	 *
	 * @return void
	 */
	public function html_page() {
		ob_start();
		?>
		<p class="lp-addons-page-subtitle">
			<?php esc_html_e( 'Discover high-performance Premium & Education themes optimized 100% for LearnPress LMS.', 'learnpress' ); ?>
		</p>
		<div class="lp-addons-page">
			<?php
			/** @use self::render_addons */
			echo TemplateAJAX::load_content_via_ajax(
				array(
					'id_url' => 'data-addons',
					'tab' => $_REQUEST['tab'] ?? '',
				),
				array(
					'class'  => self::class,
					'method' => 'render_addons',
				)
			);
			?>
		</div>
		<?php
		$content = ob_get_clean();

		echo AdminTemplate::html_on_wp_admin_screen(
			array(
				'content' => $content,
				'title'   => __( 'LearnPress Add-ons', 'learnpress' ),
				'id'      => 'learn-press-addons',
			)
		);
	}

	/**
	 * Render add-ons list for the AJAX response.
	 *
	 * @param array $data
	 * @return stdClass
	 */
	public static function render_addons( array $data = [] ): stdClass {
		$response = new stdClass();

		try {
			// Check permission
			if ( is_multisite() ? ! is_super_admin() : ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception( __( 'Access denied.', 'learnpress' ) );
			}

			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			$addons            = AddonService::instance()->get_addons();
			$plugins_installed = get_plugins();
			$plugins_activated = get_option( 'active_plugins', array() );
			$active_tab        = LP_Helper::sanitize_params_submitted( $data['tab'] ) ?? 'all';
			$keys_purchase     = LP_Settings::get_option( AddonService::instance()->key_purchase_addons, array() );

			$section = apply_filters(
				'learn-press/admin/addons/section',
				array(
					'wrapper'     => '<div class="lp-addons-wrapper">',
					'controls'    => self::html_controls(
						array(
							'active_tab' => $active_tab,
						)
					),
					'list'        => self::html_addons_list(
						array(
							'addons'            => $addons,
							'plugins_installed' => $plugins_installed,
							'plugins_activated' => $plugins_activated,
							'active_tab'        => $active_tab,
							'keys_purchase'     => $keys_purchase,
						)
					),
					'wrapper_end' => '</div>',
				),
				$addons,
				$active_tab
			);

			$response->content = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			$response->content = Template::print_message(
				$e->getMessage(),
				Response::STATUS_ERROR,
				false
			);
		}

		return $response;
	}

	/**
	 * Render the add-ons filter controls: tabs + search.
	 *
	 * @param array $data Renderer data.
	 *
	 * @return string
	 */
	public static function html_controls( array $data = [] ): string {
		$active_tab       = $data['active_tab'] ?? 'all';
		$tabs             = array(
			'all'           => sprintf( '%s (<span></span>)', __( 'All', 'learnpress' ) ),
			'installed'     => sprintf( '%s (<span></span>)', __( 'Installed', 'learnpress' ) ),
			'purchase'      => sprintf( '%s (<span></span>)', __( 'Paid', 'learnpress' ) ),
			'free'          => sprintf( '%s (<span></span>)', __( 'Free', 'learnpress' ) ),
			'update'        => sprintf( '%s (<span></span>)', __( 'Updated', 'learnpress' ) ),
			'license'       => sprintf( '%s (<span></span>)', __( 'License', 'learnpress' ) ),
			'not_installed' => sprintf( '%s (<span></span>)', __( 'Not Installed', 'learnpress' ) ),
		);
		$addon_categories = array(
			'create-course'          => __( 'Create Course', 'learnpress' ),
			'engagement'             => __( 'Engagement', 'learnpress' ),
			'learnpress'             => __( 'LearnPress', 'learnpress' ),
			'manage-course'          => __( 'Manage Course', 'learnpress' ),
			'marketing-optimization' => __( 'Marketing Optimization', 'learnpress' ),
			'monetize-course'        => __( 'Monetize Course', 'learnpress' ),
			'payment'                => __( 'Payment', 'learnpress' ),
		);

		$html_tabs = '';
		foreach ( $tabs as $tab => $name ) {
			$active_class = ( $tab === $active_tab ) ? ' nav-tab-active' : '';
			$tab_title    = apply_filters( 'learn-press/admin/submenu-heading-tab-title', $name, $tab );

			if ( $active_class ) {
				$html_tabs .= sprintf(
					'<a class="lp-addons-filter__item nav-tab%s" data-tab="%s" href="#" aria-pressed="true">%s</a>',
					esc_attr( $active_class ),
					esc_attr( $tab ),
					wp_kses_post( $tab_title )
				);
			} else {
				$html_tabs .= sprintf(
					'<a class="lp-addons-filter__item nav-tab" data-tab="%s" aria-pressed="false" href="?page=learn-press-addons&tab=%s">%s</a>',
					esc_attr( $tab ),
					esc_attr( $tab ),
					wp_kses_post( $tab_title )
				);
			}
		}

		$section = array(
			'wrapper'     => '<div class="lp-addons-controls">',
			'toolbar'     => '<div class="lp-nav-tab-wrapper lp-addons-toolbar">',
			'filter'      => sprintf(
				'<div class="lp-addons-filter" role="group" aria-label="%s">',
				esc_attr__( 'Filter add-ons', 'learnpress' )
			),
			'tabs'        => $html_tabs,
			'filter_end'  => '</div>',
			'search'      => sprintf(
				'<label class="lp-search-addons lp-addons-search"><span class="screen-reader-text">%s</span><span class="lp-addons-search__icon lp-icon-search" aria-hidden="true"></span><input id="lp-search-addons__input" class="lp-addons-search__input" type="search" placeholder="%s"/></label>',
				esc_html__( 'Search add-ons', 'learnpress' ),
				esc_attr__( 'Search add-ons by name…', 'learnpress' )
			),
			'toolbar_end' => '</div>',
			'categories'  => self::html_categories(
				array(
					'addon_categories' => $addon_categories,
				)
			),
			'wrapper_end' => '</div>',
		);

		return Template::combine_components( $section );
	}

	/**
	 * Render the add-ons category filter buttons.
	 *
	 * @param array $data Renderer data.
	 *
	 * @return string
	 */
	public static function html_categories( array $data = [] ): string {
		$addon_categories = $data['addon_categories'] ?? array();
		$html             = sprintf(
			'<button type="button" class="lp-addons-category active"
				data-category="all" aria-pressed="true">%s <span class="lp-addons-category__count"></span>
			</button>',
			esc_html__( 'All', 'learnpress' )
		);

		foreach ( $addon_categories as $category_slug => $category_label ) {
			$html .= sprintf(
				'<button type="button" class="lp-addons-category"
					data-category="%s" aria-pressed="false">%s <span class="lp-addons-category__count"></span>
				</button>',
				esc_attr( $category_slug ),
				esc_html( $category_label )
			);
		}

		return sprintf(
			'<div class="lp-addons-categories" role="group" aria-label="%s">%s</div>',
			esc_attr__( 'Filter add-ons by category', 'learnpress' ),
			$html
		);
	}

	/**
	 * Render the list of add-on items.
	 *
	 * @param array $data Renderer data.
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function html_addons_list( array $data = [] ): string {
		$addons            = $data['addons'] ?? array();
		$plugins_installed = $data['plugins_installed'] ?? array();
		$plugins_activated = $data['plugins_activated'] ?? array();
		$active_tab        = $data['active_tab'] ?? 'all';
		$keys_purchase     = $data['keys_purchase'] ?? array();
		$html              = '';
		foreach ( $addons as $slug => $addon ) {
			$addon->slug = $slug;
			$html       .= self::html_addon_item(
				$addon,
				array(
					'plugins_installed' => $plugins_installed,
					'plugins_activated' => $plugins_activated,
					'active_tab'        => $active_tab,
					'keys_purchase'     => $keys_purchase,
				)
			);
		}

		return sprintf( '<div id="lp-addons">%s</div>', $html );
	}

	/**
	 * Render a single add-on card.
	 *
	 * @param object $addon Current addon object.
	 * @param array $data Renderer data.
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function html_addon_item( object $addon, array $data = [] ): string {
		$state = self::get_addon_classes_and_state( $addon, $data );

		$section = array(
			'wrapper'     => sprintf(
				'<div class="lp-addon-item %s" data-slug="%s" data-category="%s" data-addon="%s">',
				esc_attr( implode( ' ', $state['classes_status'] ) ),
				esc_attr( $addon->slug ),
				esc_attr( $state['addon_category'] ),
				esc_attr( htmlentities2( wp_json_encode( $addon ) ) )
			),
			'content'     => '<div class="lp-addon-item__content">',
			'header'      => self::html_addon_header( $addon ),
			'license'     => ! $state['is_free']
				? self::html_addon_license(
					$addon,
					array(
						'license_status'        => $state['license_status'],
						'number_days_remaining' => $state['number_days_remaining'],
						'date_expired'          => $state['date_expired'],
						'purchase_code_masked'  => $state['purchase_code_masked'],
						'show_license_panel'    => $state['show_license_panel'],
					)
				)
				: '',
			'description' => self::html_addon_description( $addon ),
			'meta'        => self::html_addon_meta(
				$addon,
				array(
					'version_current' => $state['version_current'],
				)
			),
			'new_update'  => $state['is_updated']
				? sprintf(
					'<span class="new-update">%s: %s</span>',
					esc_html__( 'Latest version', 'learnpress' ),
					esc_html( $state['version_latest'] )
				)
				: '',
			'content_end' => '</div>',
			'actions'     => self::html_addon_actions(
				$addon,
				array(
					'is_free'      => $state['is_free'],
					'is_installed' => $state['is_installed'],
					'is_updated'   => $state['is_updated'],
				)
			),
			'purchase'    => self::html_purchase_panel(
				$addon,
				array(
					'purchase_code_masked' => $state['purchase_code_masked'],
				)
			),
			'wrapper_end' => '</div>',
		);

		return Template::combine_components( $section );
	}

	/**
	 * Compute status classes and state flags for an addon.
	 *
	 * @param object $addon Current addon object.
	 * @param array $data Addon state data.
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function get_addon_classes_and_state( object $addon, array $data = [] ): array {
		$plugins_installed     = $data['plugins_installed'] ?? array();
		$plugins_activated     = $data['plugins_activated'] ?? array();
		$active_tab            = $data['active_tab'] ?? 'all';
		$keys_purchase         = $data['keys_purchase'] ?? array();
		$is_free               = $addon->is_free ?? 0;
		$addon_base            = $addon->basename ?? '';
		$version_latest        = $addon->version ?? 0;
		$version_current       = 0;
		$classes_status        = array();
		$addon_purchased       = $addon->purchase_info ?? false;
		$addon_category        = implode( ' ', array_map( 'sanitize_title', $addon->category ?? array() ) );
		$date_expired_str      = '';
		$number_days_remaining = null;
		$date_expired          = null;

		if ( ! $is_free ) {
			$classes_status[] = 'purchase';
		} else {
			$classes_status[] = 'free';
		}

		if ( isset( $plugins_installed[ $addon_base ] ) ) {
			$is_installed     = true;
			$classes_status[] = 'installed';
			$version_current  = $plugins_installed[ $addon_base ]['Version'];
		} else {
			$is_installed     = false;
			$classes_status[] = 'not_installed';
		}

		if ( in_array( $addon_base, $plugins_activated, true ) ) {
			$classes_status[] = 'activated';
		}

		$is_updated = false;
		if ( $is_installed && version_compare( $version_current, $version_latest, '<' ) ) {
			$classes_status[] = 'update';
			$is_updated       = true;
		}

		if ( $addon_purchased ) {
			$classes_status[] = 'license';
			$date_expired_str = $addon_purchased->date_expire ?? '';
			if ( ! empty( $date_expired_str ) ) {
				$date_expired          = new DateTime( $date_expired_str );
				$date_now              = new DateTime( gmdate( 'Y-m-d' ) );
				$date_diff             = date_diff( $date_now, $date_expired );
				$number_days_remaining = $date_diff->invert ? 0 : $date_diff->days;
			}
		}

		$show_license_panel = ! $is_free && $is_installed;
		$license_status     = 'not-activated';
		if ( $addon_purchased ) {
			$license_status = 'active';
			if ( ! empty( $date_expired_str )
				&& isset( $number_days_remaining )
				&& 0 === $number_days_remaining ) {
				$license_status = 'expired';
			}
		}

		$purchase_code_masked = AddonService::mask_purchase_code( $keys_purchase[ $addon->slug ] ?? '' );

		if ( ! in_array( $active_tab, $classes_status, true ) && 'all' !== $active_tab ) {
			$classes_status[] = 'hide';
		}

		return array(
			'classes_status'        => $classes_status,
			'is_free'               => $is_free,
			'is_installed'          => $is_installed,
			'is_updated'            => $is_updated,
			'version_current'       => $version_current,
			'version_latest'        => $version_latest,
			'license_status'        => $license_status,
			'number_days_remaining' => $number_days_remaining,
			'date_expired'          => $date_expired,
			'purchase_code_masked'  => $purchase_code_masked,
			'show_license_panel'    => $show_license_panel,
			'addon_category'        => $addon_category,
		);
	}

	/**
	 * Render the header section of an addon item.
	 *
	 * @param object $addon Current addon object.
	 * @param array  $data  Renderer data.
	 *
	 * @return string
	 */
	public static function html_addon_header( object $addon, array $data = [] ): string {
		$html = sprintf(
			'<div class="lp-addon-item__header"><img class="lp-addon-item__image" src="%s" alt=""/><div class="lp-addon-item__heading"><h3 class="lp-addon-item__title"><a href="%s" target="_blank" rel="noopener">%s</a></h3>',
			esc_url( $addon->image ?? '' ),
			esc_url( $addon->link ?? '' ),
			esc_html( $addon->name ?? '' )
		);

		if ( ! empty( $addon->badge ) ) {
			$html .= sprintf(
				'<span class="lp-addon-item__badge">%s</span>',
				esc_html( $addon->badge )
			);
		}

		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Render the description of an addon item.
	 *
	 * @param object $addon Current addon object.
	 * @param array  $data  Renderer data.
	 *
	 * @return string
	 */
	public static function html_addon_description( object $addon, array $data = [] ): string {
		return sprintf(
			'<p class="lp-addon-item__description" title="%s">%s</p>',
			esc_attr( $addon->description ?? '' ),
			esc_html( $addon->description ?? '' )
		);
	}

	/**
	 * Render the license status block for a paid addon.
	 *
	 * @param object $addon Current addon object.
	 * @param array  $data  Renderer data.
	 *
	 * @return string
	 */
	public static function html_addon_license( object $addon, array $data = [] ): string {
		$license_status        = $data['license_status'] ?? '';
		$number_days_remaining = $data['number_days_remaining'] ?? null;
		$date_expired          = $data['date_expired'] ?? null;
		$purchase_code_masked  = $data['purchase_code_masked'] ?? '';
		$show_license_panel    = $data['show_license_panel'] ?? false;
		$license_status_label  = 'active' === $license_status
			? __( 'Active', 'learnpress' )
			: ( 'expired' === $license_status
				? __( 'Expired', 'learnpress' )
				: ( 'deactivated' === $license_status
					? __( 'Deactivated', 'learnpress' )
					: __( 'Not Activated', 'learnpress' ) ) );

		$expiry_hidden = empty( $date_expired ) || ! in_array( $license_status, array( 'active', 'expired' ), true ) ? ' hidden' : '';

		$expiry_text = '';
		if ( ! empty( $date_expired ) ) {
			$expiry_format = 'expired' === $license_status
				? __( '(on %s)', 'learnpress' )
				: __( '(Updates until %s)', 'learnpress' );
			$expiry_text   = esc_html( sprintf( $expiry_format, date_i18n( 'F j, Y', $date_expired->getTimestamp() ) ) );
		}

		$extend_link = '';
		if ( 'expired' === $license_status ) {
			$extend_link = sprintf(
				'<a class="need-extend__link" href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( $addon->link ?? '' ),
				esc_html__( 'Extend now', 'learnpress' )
			);
		}

		$message        = '';
		$button_extends = '';
		if ( ! empty( $addon->purchase_info ) ) {
			$button_extends = sprintf(
				'<a class="need-extend__link" href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( $addon->link ?? '' ),
				esc_html__( 'Extend now', 'learnpress' )
			);

			if ( isset( $number_days_remaining ) && $number_days_remaining > 0 && $number_days_remaining < 61 ) {
				$message = sprintf(
					__( 'You have a license of for this item with %s days of update & support remaining. Please extend the update & support license to keep updating the latest versions & receive customer support from thimpress before it expires.', 'learnpress' ),
					sprintf( '<strong class="need-extend__days">%d</strong>', $number_days_remaining )
				);
			} else {
				$button_extends = '';
			}
		}

		$need_extend = '';
		if ( ! empty( $message ) || ! empty( $button_extends ) ) {
			$need_extend = sprintf(
				'<span class="need-extend">%s %s</span>',
				$message,
				$button_extends
			);
		}

		return sprintf(
			'<div class="lp-addon-license lp-addon-license--%1$s" data-purchase-code-masked="%2$s"%3$s>
				<div class="lp-addon-license__summary">
					<span>%4$s</span>
					<strong class="lp-addon-license__status">%5$s</strong>
					<span class="lp-addon-license__expiry"%6$s>%7$s</span>
					%8$s
				</div>
				%9$s
				<button class="btn-addon-action lp-addon-license__manage" data-action="update-purchase-code" type="button">%10$s</button>
			</div>',
			esc_attr( $license_status ),
			esc_attr( $purchase_code_masked ),
			$show_license_panel ? '' : ' hidden',
			esc_html__( 'License:', 'learnpress' ),
			esc_html( $license_status_label ),
			$expiry_hidden,
			$expiry_text,
			$extend_link,
			$need_extend,
			esc_html__( 'Manage', 'learnpress' )
		);
	}

	/**
	 * Render addon meta: version, docs, view details.
	 *
	 * @param object $addon Current addon object.
	 * @param array  $data  Renderer data.
	 *
	 * @return string
	 */
	public static function html_addon_meta( object $addon, array $data = [] ): string {
		$version_current = $data['version_current'] ?? '';
		$html            = '';

		if ( $version_current ) {
			$html .= sprintf(
				'<span>%s <span class="addon-version-current">%s</span></span>',
				esc_html__( 'Version', 'learnpress' ),
				esc_html( $version_current )
			);
		}

		if ( ! empty( $addon->link_doc ) ) {
			$html .= sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( $addon->link_doc ),
				esc_html__( 'Docs', 'learnpress' )
			);
		}

		$html .= sprintf(
			'<a href="%s" target="_blank" rel="noopener">%s</a>',
			esc_url( $addon->link ?? '' ),
			esc_html__( 'View Details', 'learnpress' )
		);

		return sprintf( '<div class="lp-addon-item__meta">%s</div>', $html );
	}

	/**
	 * Render addon price block.
	 *
	 * @param object $addon Current addon object.
	 * @param array  $data  Renderer data.
	 *
	 * @return string
	 */
	public static function html_addon_price( object $addon, array $data = [] ): string {
		$is_free = $addon->is_free ?? 0;

		if ( $is_free ) {
			return sprintf(
				'<strong class="lp-addon-item__price-regular lp-addon-item__price-regular--free">%s</strong>',
				esc_html__( 'Free', 'learnpress' )
			);
		}

		if ( ! empty( $addon->sale_price ) ) {
			return sprintf(
				'<strong class="lp-addon-item__price-sale">$%s</strong><del class="lp-addon-item__price-regular">$%s</del>',
				esc_html( number_format_i18n( $addon->sale_price ) ),
				esc_html( number_format_i18n( $addon->regular_price ?? 0 ) )
			);
		}

		return sprintf(
			'<strong class="lp-addon-item__price-regular lp-addon-item__price-regular--current">$%s</strong>',
			esc_html( number_format_i18n( $addon->regular_price ?? 0 ) )
		);
	}

	/**
	 * Render addon action buttons.
	 *
	 * @param object $addon Current addon object.
	 * @param array  $data  Renderer data.
	 *
	 * @return string
	 */
	public static function html_addon_actions( object $addon, array $data = [] ): string {
		$is_free       = $data['is_free'] ?? false;
		$is_installed  = $data['is_installed'] ?? false;
		$is_updated    = $data['is_updated'] ?? false;
		$actions_left  = '';
		$actions_right = '';

		if ( ! empty( $addon->setting ) ) {
			$actions_left .= sprintf(
				'<a class="lp-addon-button" data-action="setting" href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( site_url( $addon->setting ) ),
				esc_html__( 'Settings', 'learnpress' )
			);
		}

		if ( $is_free ) {
			$actions_left .= sprintf(
				'<a class="btn-addon-action" data-action="install" href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( $addon->link ?? '' ),
				esc_html__( 'Install', 'learnpress' )
			);
		} else {
			$actions_left .= sprintf(
				'<button class="btn-addon-action" data-action="install"><span class="dashicons dashicons-update"></span><span class="text">%s</span></button>',
				esc_html__( 'Install', 'learnpress' )
			);
		}

		$actions_left .= sprintf(
			'<button class="btn-addon-action" data-action="purchase">%s</button>',
			esc_html__( 'Install', 'learnpress' )
		);

		$actions_left .= sprintf(
			'<button class="btn-addon-action" data-action="update"><span class="dashicons dashicons-update"></span><span class="text">%s</span></button>',
			esc_html__( 'Update', 'learnpress' )
		);

		$actions_right .= sprintf(
			'<button class="btn-addon-action" data-action="deactivate"><span class="dashicons dashicons-update"></span><span class="text">%s</span></button>',
			esc_html__( 'Deactivate', 'learnpress' )
		);

		$actions_right .= sprintf(
			'<button class="btn-addon-action" data-action="activate"><span class="dashicons dashicons-update"></span><span class="text">%s</span></button>',
			esc_html__( 'Activate', 'learnpress' )
		);

		$price_html = self::html_addon_price( $addon );
		$section    = array(
			'wrapper'     => '<div class="lp-addon-item__actions">',
			'price'       => sprintf( '<div class="lp-addon-item__price">%s</div>', $price_html ),
			'actions_left'  => sprintf( '<div class="lp-addon-item__actions__left">%s</div>', $actions_left ),
			'actions_right' => sprintf( '<div class="lp-addon-item__actions__right">%s</div>', $actions_right ),
			'wrapper_end' => '</div>',
		);

		return Template::combine_components( $section );
	}

	/**
	 * Render the purchase-code overlay panel.
	 *
	 * @param object $addon Current addon object.
	 * @param array  $data  Renderer data.
	 *
	 * @return string
	 */
	public static function html_purchase_panel( object $addon, array $data = [] ): string {
		$panel = sprintf(
			'<div class="purchase-install">
				<div class="lp-addon-purchase__header">
					<strong class="lp-addon-purchase__title">%1$s</strong>
					<button class="btn-addon-action lp-addon-purchase__close" data-action="cancel" type="button" aria-label="%2$s">
						<span class="lp-addon-purchase__close-symbol" aria-hidden="true">&times;</span>
					</button>
				</div>
				<label class="lp-addon-purchase__field">
					<span class="screen-reader-text">%1$s</span>
					<input type="text" class="enter-purchase-code" placeholder="%3$s" value="">
				</label>
				<button class="btn-addon-action lp-addon-purchase__submit" data-action="install" type="button">
					<span class="dashicons dashicons-update"></span>
					<span class="text">%4$s</span>
				</button>
				<div class="lp-addon-purchase__divider"><span>%5$s</span></div>
				<a class="btn-addon-action lp-addon-purchase__buy" href="%6$s" target="_blank" rel="noopener">%7$s</a>
			</div>',
			esc_html__( 'Purchase Code', 'learnpress' ),
			esc_attr__( 'Close', 'learnpress' ),
			esc_attr__( 'Enter Purchase Code', 'learnpress' ) ,
			esc_html__( 'Submit', 'learnpress' ),
			esc_html__( 'Don\'t have a code?', 'learnpress' ),
			esc_url( $addon->link ?? '' ),
			esc_html__( 'Buy Now', 'learnpress' )
		);

		$section = array(
			'wrapper'       => '<div class="lp-addon-item__purchase">',
			'purchase'      => '<div class="lp-addon-item__purchase__wrapper">',
			'panel' => $panel,
			'purchase_code' => '<input type="hidden" name="purchase-code" value="">',
			'purchase_end'  => '</div>',
			'wrapper_end'   => '</div>',
		);

		return Template::combine_components( $section );
	}
}
