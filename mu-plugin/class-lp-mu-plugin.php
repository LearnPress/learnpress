<?php

/**
 * Class LP_MU_Plugin
 *
 * @since 4.1.7.1
 * @version 1.0.0
 */
class LP_MU_Plugin {
	public static $version = 1;

	public static function instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	private function __construct() {
		add_filter( 'option_active_plugins', [ $this, 'load_plugins' ], -1 );
	}

	/**
	 * Handle list plugins can load in REST API LP
	 * Not handle if called from deactivate_plugins or is_plugin_active function.
	 *
	 * @param $plugins
	 *
	 * @return array|mixed
	 */
	public function load_plugins( $plugins ) {
		try {
			if ( ! $this->urlRequestApply() ) {
				return $plugins;
			}

			// Not handle if call from deactivate_plugins or is_plugin_active function.
			$methods_called_to = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
			foreach ( $methods_called_to as $method ) {
				if ( $method['function'] === 'deactivate_plugins' || $method['function'] === 'is_plugin_active' ) {
					return $plugins;
				}
			}

			remove_all_actions( 'setup_theme' );
			remove_all_actions( 'after_setup_theme' );

			$plugins_no_load = [
				'buddypress/bp-loader.php',
				'woocommerce/woocommerce.php',
				'paid-memberships-pro/paid-memberships-pro.php',
				'learnpress-paid-membership-pro/learnpress-paid-memberships-pro.php',
				'bbpress/bbpress.php',
				'elementor/elementor.php',
			];

			if ( in_array( 'learnpress-woo-payment/learnpress-woo-payment.php', $plugins, true ) ) {
				$index = array_search( 'woocommerce/woocommerce.php', $plugins_no_load );
				unset( $plugins_no_load[ $index ] );
			}

			$plugins_load = [];

			foreach ( $plugins as $plugin ) {
				if ( in_array( $plugin, $plugins_no_load ) ) {
					continue;
				} else {
					$plugins_load[] = $plugin;
				}
			}

			return $plugins_load;
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $plugins;
	}

	/**
	 * Get the current url
	 *
	 * @return string
	 * @since  3.2.6.8
	 * @author tungnx
	 */
	public static function getUrlCurrent(): string {
		$schema    = is_ssl() ? 'https://' : 'http://';
		$http_host = $_SERVER['HTTP_HOST'] ?? '';

		return $schema . $http_host . untrailingslashit( esc_url_raw( $_SERVER['REQUEST_URI'] ?? '' ) );
	}

	/**
	 * Check url request can apply check load plugins.
	 *
	 * @return bool
	 */
	public function urlRequestApply(): bool {
		$apply      = false;
		$urls_apply = [
			'/wp-json/lp/v1/courses',
			'/wp-json/lp/v1/lazy-load/course-curriculum',
			'/wp-json/lp/v1/courses/continue-course',
		];

		foreach ( $urls_apply as $url ) {
			if ( false !== strpos( self::getUrlCurrent(), $url ) ) {
				$apply = true;
				break;
			}
		}

		//return strpos( $this->getUrlCurrent(), '/wp-json/lp/v1/courses' );
		return $apply;
	}
}

LP_MU_Plugin::instance();
