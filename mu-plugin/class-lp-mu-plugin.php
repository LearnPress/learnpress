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
	public function load_plugins( $plugins_activating ) {
		try {
			$url_load_plugins = $this->getPluginsMustLoadInUrl();
			if ( ! $url_load_plugins ) {
				return $plugins_activating;
			}

			// Not handle if call from deactivate_plugins or is_plugin_active function.
			$methods_called_to = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
			foreach ( $methods_called_to as $method ) {
				if ( $method['function'] === 'deactivate_plugins' || $method['function'] === 'is_plugin_active' ) {
					return $plugins_activating;
				}
			}

			remove_all_actions( 'setup_theme' );
			remove_all_actions( 'after_setup_theme' );

			$plugins_load = [];

			foreach ( $url_load_plugins as $plugin => $plugin_dependencies ) {
				if ( in_array( $plugin, $plugins_activating, true ) ) {
					$plugins_load[] = $plugin;

					foreach ( $plugin_dependencies as $dependency ) {
						if ( in_array( $dependency, $plugins_activating, true ) ) {
							$plugins_load[] = $dependency;
						}
					}
				}
			}

			//error_log( 'plugins_load: ' . print_r( $plugins_load, true ) );

			return $plugins_load;
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $plugins_activating;
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
	 * Format:
	 * ['url' => ['plugin' => ['dependencies']]]
	 *
	 * @return bool|array
	 */
	public function getPluginsMustLoadInUrl() {
		$url_load_plugins = false;
		$urls_apply       = [
			'wp-json/lp/v1/courses/archive-course'      => [
				'learnpress/learnpress.php'           => [],
				'learnpress-woo-payment/learnpress-woo-payment.php' => [
					'woocommerce/woocommerce.php',
				],
				'learnpress-wpml/learnpress-wpml.php' => [
					'sitepress-multilingual-cms/sitepress.php',
				],
			],
			'wp-json/lp/v1/profile/course-tab'          => [
				'learnpress/learnpress.php'           => [],
				'learnpress-woo-payment/learnpress-woo-payment.php' => [
					'woocommerce/woocommerce.php',
				],
				'learnpress-wpml/learnpress-wpml.php' => [
					'sitepress-multilingual-cms/sitepress.php',
				],
			],
			'wp-json/lp/v1/lazy-load/course-curriculum' => [
				'learnpress/learnpress.php'           => [],
				'learnpress-assignments/learnpress-assignments.php' => [],
				'learnpress-h5p/learnpress-h5p.php'   => [],
				'learnpress-wpml/learnpress-wpml.php' => [
					'sitepress-multilingual-cms/sitepress.php',
				],
			],
			'wp-json/lp/v1/lazy-load/course-progress'   => [
				'learnpress/learnpress.php'         => [],
				'learnpress-assignments/learnpress-assignments.php' => [],
				'learnpress-h5p/learnpress-h5p.php' => [],
			],
			'wp-json/lp/v1/lazy-load/items-progress'    => [
				'learnpress/learnpress.php' => [],
			],
			'wp-json/lp/v1/profile/statistic'           => [
				'learnpress/learnpress.php' => [],
			],
		];

		foreach ( $urls_apply as $url => $plugins_load ) {
			if ( false !== strpos( self::getUrlCurrent(), $url ) ) {
				$url_load_plugins = $plugins_load;
				break;
			}
		}

		return $url_load_plugins;
	}
}

LP_MU_Plugin::instance();
