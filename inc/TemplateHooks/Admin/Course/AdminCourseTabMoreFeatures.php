<?php

namespace LearnPress\TemplateHooks\Admin\Course;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Services\AddonService;
use Throwable;

defined( 'ABSPATH' ) || exit();

/**
 * Tab "More Features" of the Course Settings: recommended add-ons not installed yet.
 *
 * @since 4.4.15
 * @version 1.0.0
 */
class AdminCourseTabMoreFeatures {
	use Singleton;

	/**
	 * Max add-ons shown at the same time (applied by JS, server renders all candidates).
	 */
	const MAX_VISIBLE = 5;

	/**
	 * Page listing all add-ons.
	 */
	const URL_DISCOVER = 'https://thimpress.com/products/learnpress-add-ons/';

	/**
	 * Days a dismissed add-on stays hidden (applied by JS).
	 */
	const DISMISS_DAYS = 30;

	/**
	 * localStorage key storing { slug: timestamp }.
	 */
	const STORAGE_KEY = 'lp_more_features_dismissed';

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public function init() {}

	/**
	 * Get installed plugins keyed by basename. Overridable for tests.
	 *
	 * @return array
	 */
	protected function get_installed_plugins(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugins();
	}

	/**
	 * Get add-ons not installed yet, in remote order.
	 *
	 * @return array[] List of [ slug, name, description, image, link ].
	 */
	public function get_recommended_addons(): array {
		$list = [];

		try {
			$addons    = AddonService::instance()->get_addons();
			$installed = $this->get_installed_plugins();

			foreach ( $addons as $slug => $addon ) {
				if ( ! is_object( $addon ) || empty( $addon->name ) ) {
					continue;
				}

				$basename = $addon->basename ?? '';
				if ( ! empty( $basename ) && isset( $installed[ $basename ] ) ) {
					continue;
				}

				$list[] = [
					'slug'        => (string) $slug,
					'name'        => (string) $addon->name,
					'description' => (string) ( $addon->description ?? '' ),
					'image'       => (string) ( $addon->image ?? '' ),
					'link'        => (string) ( $addon->link ?? self::URL_DISCOVER ),
				];
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
			return [];
		}

		return $list;
	}

	/**
	 * HTML of tab "More Features".
	 *
	 * Server renders every candidate; JS filters dismissed ones and keeps MAX_VISIBLE.
	 * Items over the limit carry `hidden` so the markup is sane without JS.
	 *
	 * @return string
	 */
	public function html_more_features(): string {
		$addons = $this->get_recommended_addons();

		$items = '';
		foreach ( $addons as $index => $addon ) {
			$items .= $this->html_item( $addon, $index >= self::MAX_VISIBLE );
		}

		$empty_dismissed = [
			'wrap'     => '<div class="lp-more-features__empty" hidden>',
			'message'  => sprintf( '<span>%s</span> ', esc_html__( 'No more recommendations.', 'learnpress' ) ),
			'reset'    => sprintf(
				'<a href="#" class="lp-more-features__reset-link">%s</a>',
				esc_html__( 'Reset recommendations', 'learnpress' )
			),
			'wrap_end' => '</div>',
		];

		$none = '';
		if ( empty( $addons ) ) {
			$none = sprintf(
				'<p class="lp-more-features__none">%s</p>',
				esc_html__( 'No add-on recommendations available right now.', 'learnpress' )
			);
		}

		$section = [
			'wrap'      => sprintf(
				'<div class="lp-more-features" data-max="%1$d" data-ttl-days="%2$d" data-storage-key="%3$s">',
				self::MAX_VISIBLE,
				self::DISMISS_DAYS,
				esc_attr( self::STORAGE_KEY )
			),
			'title'     => sprintf(
				'<h3 class="lp-more-features__title">%s</h3>',
				esc_html__( 'Recommended Add-ons', 'learnpress' )
			),
			'list'      => '<ul class="lp-more-features__list">' . $items . '</ul>',
			'none'      => $none,
			'empty'     => Template::combine_components( $empty_dismissed ),
			'footer'    => '<div class="lp-more-features__footer">',
			'discover'  => sprintf(
				'<a href="%1$s" class="lp-more-features__discover" target="_blank" rel="noopener noreferrer">%2$s</a>',
				esc_url( self::URL_DISCOVER ),
				esc_html__( 'Discover more options', 'learnpress' )
			),
			'reset'     => sprintf(
				'<button type="button" class="button lp-more-features__reset" hidden>%s</button>',
				esc_html__( 'Reset suggestions', 'learnpress' )
			),
			'footer_end' => '</div>',
			'wrap_end'  => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML of one add-on item.
	 *
	 * @param array $addon  Item from get_recommended_addons().
	 * @param bool  $hidden Hide on first paint (over the visible limit).
	 *
	 * @return string
	 */
	protected function html_item( array $addon, bool $hidden = false ): string {
		$icon = '';
		if ( ! empty( $addon['image'] ) ) {
			$icon = sprintf(
				'<img class="lp-more-features__icon" src="%s" alt="" width="40" height="40" loading="lazy" />',
				esc_url( $addon['image'] )
			);
		}

		$section = [
			'wrap'     => sprintf(
				'<li class="lp-more-features__item" data-slug="%s"%s>',
				esc_attr( $addon['slug'] ),
				$hidden ? ' hidden' : ''
			),
			'icon'     => $icon,
			'info'     => sprintf(
				'<div class="lp-more-features__info"><h4 class="lp-more-features__name">%1$s</h4><p class="lp-more-features__desc">%2$s</p></div>',
				esc_html( $addon['name'] ),
				esc_html( $addon['description'] )
			),
			'learn'    => sprintf(
				'<a href="%1$s" class="button lp-more-features__learn" target="_blank" rel="noopener noreferrer">%2$s</a>',
				esc_url( $addon['link'] ),
				esc_html__( 'Learn More', 'learnpress' )
			),
			'dismiss'  => sprintf(
				'<button type="button" class="lp-more-features__dismiss" aria-label="%1$s" title="%1$s"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>',
				esc_attr__( 'Dismiss', 'learnpress' )
			),
			'wrap_end' => '</li>',
		];

		return Template::combine_components( $section );
	}
}
