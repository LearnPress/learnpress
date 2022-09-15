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

	public function load_plugins( $plugins ) {
		try {
			if ( ! $this->isRestApiLP() ) {
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
				'bbpress/bbpress.php',
				'elementor/elementor.php',
			];

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

	public function isRestApiLP() {
		return strpos( $this->getUrlCurrent(), '/wp-json/lp/' );
	}
}

LP_MU_Plugin::instance();
