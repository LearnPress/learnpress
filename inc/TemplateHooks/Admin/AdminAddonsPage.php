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
	 * HTML the LearnPress Add-ons page.
	 *
	 * @return void
	 */
	public function html_page() {
		/** @use self::render_addons */
		$section = apply_filters(
			'learn-press/admin/addons/page-section',
			array(
				'subtitle'    => sprintf(
					'<p class="lp-addons-page-subtitle">%s</p>',
					esc_html__(
						'Discover high-performance Premium & Education themes optimized 100% for LearnPress LMS.',
						'learnpress'
					)
				),
				'wrapper'     => '<div class="lp-addons-page">',
				'addons'      => TemplateAJAX::load_content_via_ajax(
					array(
						'id_url' => 'data-addons',
						'tab'    => $_REQUEST['tab'] ?? '',
					),
					array(
						'class'  => self::class,
						'method' => 'render_addons',
					)
				),
				'wrapper_end' => '</div>',
			)
		);

		echo AdminTemplate::html_on_wp_admin_screen(
			array(
				'content' => Template::combine_components( $section ),
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
				$data
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

		$section_tabs = array();
		foreach ( $tabs as $tab => $tab_title ) {
			$active_class = ( $tab === $active_tab ) ? ' nav-tab-active' : '';

			if ( $active_class ) {
				$section_tabs[ $tab ] = sprintf(
					'<a class="lp-addons-filter__item nav-tab%s" data-tab="%s" href="#" aria-pressed="true">%s</a>',
					esc_attr( $active_class ),
					esc_attr( $tab ),
					wp_kses_post( $tab_title )
				);
			} else {
				$section_tabs[ $tab ] = sprintf(
					'<a class="lp-addons-filter__item nav-tab"
						data-tab="%s" aria-pressed="false" href="?page=learn-press-addons&tab=%s">%s</a>',
					esc_attr( $tab ),
					esc_attr( $tab ),
					wp_kses_post( $tab_title )
				);
			}
		}

		$section = array(
			'wrapper'           => '<div class="lp-addons-controls">',
			'toolbar'           => '<div class="lp-nav-tab-wrapper lp-addons-toolbar">',
			'filter'            => sprintf(
				'<div class="lp-addons-filter" role="group" aria-label="%s">',
				esc_attr__( 'Filter add-ons', 'learnpress' )
			),
			'tabs'              => Template::combine_components( $section_tabs ),
			'filter_end'        => '</div>',
			'search'            => '<label class="lp-search-addons lp-addons-search">',
			'search_text'       => sprintf(
				'<span class="screen-reader-text">%s</span>',
				esc_html__( 'Search add-ons', 'learnpress' )
			),
			'search_icon'       => '<span class="lp-addons-search__icon lp-icon-search" aria-hidden="true"></span>',
			'search_input'      => sprintf(
				'<input id="lp-search-addons__input"
					class="lp-addons-search__input" type="search" placeholder="%s"/>',
				esc_attr__( 'Search add-ons by name…', 'learnpress' )
			),
			'search_end'        => '</label>',
			'toolbar_end'       => '</div>',
			'categories'        => self::html_categories(
				array(
					'addon_categories' => $addon_categories,
				)
			),
			'wrapper_end'       => '</div>',
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
		$section          = array(
			'wrapper' => sprintf(
				'<div class="lp-addons-categories" role="group" aria-label="%s">',
				esc_attr__( 'Filter add-ons by category', 'learnpress' )
			),
			'all'     => sprintf(
				'<button type="button" class="lp-addons-category active"
					data-category="all" aria-pressed="true">%s <span class="lp-addons-category__count"></span>
				</button>',
				esc_html__( 'All', 'learnpress' )
			),
		);

		foreach ( $addon_categories as $category_slug => $category_label ) {
			$section[ $category_slug ] = sprintf(
				'<button type="button" class="lp-addons-category"
					data-category="%s" aria-pressed="false">%s <span class="lp-addons-category__count"></span>
				</button>',
				esc_attr( $category_slug ),
				esc_html( $category_label )
			);
		}

		$section['wrapper_end'] = '</div>';

		return Template::combine_components( $section );
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
		$addons = $data['addons'] ?? array();
		$html   = '';
		foreach ( $addons as $slug => $addon ) {
			$addon->slug = $slug;
			$html       .= self::html_addon_item( $addon, $data );
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

		$section = apply_filters(
			'learn-press/admin/addons/item',
			array(
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
				'description' => sprintf(
					'<p class="lp-addon-item__description" title="%s">%s</p>',
					esc_attr( $addon->description ?? '' ),
					esc_html( $addon->description ?? '' )
				),
				'meta'        => self::html_addon_meta(
					$addon,
					array(
						'version_current' => $state['version_current'],
					)
				),
				'new_update'  => $state['is_updated']
					? sprintf(
						'<span class="new-update">%s: <strong>%s</strong></span>',
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
			),
			$addon,
			$data
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
		$version_current       = $addon->version ?? 0;
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
		$section = array(
			'wrapper'     => '<div class="lp-addon-item__header">',
			'image'       => sprintf(
				'<img class="lp-addon-item__image" src="%s" alt=""/>',
				esc_url( $addon->image ?? '' )
			),
			'heading'     => '<div class="lp-addon-item__heading">',
			'title'       => '<h3 class="lp-addon-item__title">',
			'link'        => sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( $addon->link ?? '' ),
				esc_html( $addon->name ?? '' )
			),
			'title_end'   => '</h3>',
			'badge' => ! empty( $addon->badge ) ? sprintf(
				'<span class="lp-addon-item__badge">%s</span>',
				esc_html( $addon->badge )
			) : '',
			'heading_end' => '</div>',
			'wrapper_end' => '</div>',
		);

		return Template::combine_components( $section );
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

		$expiry_hidden = empty( $date_expired )
		|| ! in_array( $license_status, array( 'active', 'expired' ), true ) ? ' hidden' : '';

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
					__( 'You have a license for this item with %s day(s) of update & support remaining. Please extend your update & support license to continue receiving the latest versions and customer support from Thimpress before it expires.', 'learnpress' ),
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

		$section = array(
			'wrapper'     => sprintf(
				'<div class="lp-addon-license lp-addon-license--%s" data-purchase-code-masked="%s"%s>',
				esc_attr( $license_status ),
				esc_attr( $purchase_code_masked ),
				$show_license_panel ? '' : ' hidden'
			),
			'summary'     => '<div class="lp-addon-license__summary">',
			'label'       => sprintf(
				'<span>%s</span>',
				esc_html__( 'License:', 'learnpress' )
			),
			'status'      => sprintf(
				'<strong class="lp-addon-license__status">%s</strong>',
				esc_html( $license_status_label )
			),
			'expiry'      => sprintf(
				'<span class="lp-addon-license__expiry"%s>%s</span>',
				$expiry_hidden,
				$expiry_text
			),
			'extend_link' => $extend_link,
			'summary_end' => '</div>',
			'need_extend' => $need_extend,
			'manage'      => sprintf(
				'<button class="btn-addon-action lp-addon-license__manage"
					data-action="update-purchase-code" type="button">%s</button>',
				esc_html__( 'Manage', 'learnpress' )
			),
			'wrapper_end' => '</div>',
		);

		return Template::combine_components( $section );
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
		$section         = array(
			'wrapper' => '<div class="lp-addon-item__meta">',
			'version' => $version_current
				? sprintf(
					'<span>%s <span class="addon-version-current">%s</span></span>',
					esc_html__( 'Version', 'learnpress' ),
					esc_html( $version_current )
				)
				: '',
			'docs'    => ! empty( $addon->link_doc )
				? sprintf(
					'<a href="%s" target="_blank" rel="noopener">%s</a>',
					esc_url( $addon->link_doc ),
					esc_html__( 'Docs', 'learnpress' )
				)
				: '',
			'details' => sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( $addon->link ?? '' ),
				esc_html__( 'View Details', 'learnpress' )
			),
			'settings' => ! empty( $addon->setting )
				? sprintf(
					'<a data-action="setting" href="%s" target="_blank" rel="noopener">%s</a>',
					esc_url( site_url( $addon->setting ) ),
					esc_html__( 'Settings', 'learnpress' )
				)
				: '',
			'wrapper_end' => '</div>',
		);

		return Template::combine_components( $section );
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
			$section = array(
				'sale'    => sprintf(
					'<strong class="lp-addon-item__price-sale">$%s</strong>',
					esc_html( number_format_i18n( $addon->sale_price ) )
				),
				'regular' => sprintf(
					'<del class="lp-addon-item__price-regular">$%s</del>',
					esc_html( number_format_i18n( $addon->regular_price ?? 0 ) )
				),
			);

			return Template::combine_components( $section );
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
		$section_right = apply_filters(
			'learn-press/admin/addons/actions/section_right',
			array(
				'install'    => $is_free
					? sprintf(
						'<a class="lp-button btn-addon-action" data-action="install" href="%s"
						target="_blank" rel="noopener">%s</a>',
						esc_url( $addon->link ?? '' ),
						esc_html__( 'Install', 'learnpress' )
					)
					: sprintf(
						'<button class="lp-button btn-addon-action" data-action="install">
						<span class="text">%s</span>
					</button>',
						esc_html__( 'Install', 'learnpress' )
					),
				'purchase'   => sprintf(
					'<button class="lp-button btn-addon-action" data-action="purchase">%s</button>',
					esc_html__( 'Install', 'learnpress' )
				),
				'update'     => sprintf(
					'<button class="lp-button btn-addon-action" data-action="update">
					<span class="text">%s</span>
				</button>',
					esc_html__( 'Update', 'learnpress' )
				),
				'deactivate' => sprintf(
					'<button class="lp-button btn-addon-action" data-action="deactivate">
					<span class="text">%s</span>
				</button>',
					esc_html__( 'Deactivate', 'learnpress' )
				),
				'activate'   => sprintf(
					'<button class="lp-button btn-addon-action" data-action="activate">
						<span class="text">%s</span>
					</button>',
					esc_html__( 'Activate', 'learnpress' )
				),
			),
			$addon,
			$data
		);

		$section = array(
			'wrapper'           => '<div class="lp-addon-item__actions">',
			'actions_left'      => '<div class="lp-addon-item__actions__left">',
			'price'             => sprintf(
				'<div class="lp-addon-item__price">%s</div>',
				self::html_addon_price( $addon )
			),
			'actions_left_end'  => '</div>',
			'actions_right'     => '<div class="lp-addon-item__actions__right">',
			'actions'           => Template::combine_components( $section_right ),
			'actions_right_end' => '</div>',
			'wrapper_end'       => '</div>',
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
		$panel = array(
			'wrapper'     => '<div class="purchase-install">',
			'header'      => '<div class="lp-addon-purchase__header">',
			'title'       => sprintf(
				'<strong class="lp-addon-purchase__title">%s</strong>',
				esc_html__( 'Purchase Code', 'learnpress' )
			),
			'close'       => sprintf(
				'<button class="btn-addon-action
					lp-addon-purchase__close" data-action="cancel" type="button" aria-label="%s">',
				esc_attr__( 'Close', 'learnpress' )
			),
			'close_icon'  => '<span class="lp-addon-purchase__close-symbol" aria-hidden="true">&times;</span>',
			'close_end'   => '</button>',
			'header_end'  => '</div>',
			'field'       => '<label class="lp-addon-purchase__field">',
			'field_text'  => sprintf(
				'<span class="screen-reader-text">%s</span>',
				esc_html__( 'Purchase Code', 'learnpress' )
			),
			'field_input' => sprintf(
				'<input type="text" class="enter-purchase-code" placeholder="%s" value="">',
				esc_attr__( 'Enter Purchase Code', 'learnpress' )
			),
			'field_end'   => '</label>',
			'submit'      => '<button class="lp-button btn-addon-action lp-addon-purchase__submit"
				data-action="install" type="button">',
			'submit_text' => sprintf( '<span class="text">%s</span>', esc_html__( 'Submit', 'learnpress' ) ),
			'submit_end'  => '</button>',
			'divider'     => sprintf(
				'<div class="lp-addon-purchase__divider"><span>%s</span></div>',
				esc_html__( 'Don\'t have a code?', 'learnpress' )
			),
			'buy'         => sprintf(
				'<a class="btn-addon-action lp-addon-purchase__buy" href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( $addon->link ?? '' ),
				esc_html__( 'Buy Now', 'learnpress' )
			),
			'wrapper_end' => '</div>',
		);

		$section = array(
			'wrapper'       => '<div class="lp-addon-item__purchase">',
			'purchase'      => '<div class="lp-addon-item__purchase__wrapper">',
			'panel'         => Template::combine_components( $panel ),
			'purchase_code' => '<input type="hidden" name="purchase-code" value="">',
			'purchase_end'  => '</div>',
			'wrapper_end'   => '</div>',
		);

		return Template::combine_components( $section );
	}
}
