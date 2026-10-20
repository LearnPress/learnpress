<?php
/**
 * Admin notice check service.
 *
 * Centralizes pure check/validation logic used by admin notices.
 *
 * @package LearnPress\Services
 * @since 4.4.9 instead of LP_Admin_Notice
 * @version 1.0.0
 */

namespace LearnPress\Services;

use Exception;
use LP_Debug;
use LP_Helper;
use stdClass;
use Throwable;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdminNoticeService
 */
class AdminNoticeService {
	/**
	 * Check if the plugin base directory is correct.
	 *
	 * @return bool True if plugin base is NOT the expected value.
	 */
	public static function check_plugin_base(): bool {
		return 0 !== strcmp( LP_PLUGIN_BASENAME, 'learnpress/learnpress.php' );
	}

	/**
	 * Check wp_remote_get connectivity.
	 *
	 * @return array|WP_Error Response array on success, WP_Error on failure.
	 */
	public static function check_wp_remote() {
		$test_url = LP_PLUGIN_URL . 'assets/images/icon-128x128.png';
		$args     = [
			'timeout' => 5,
		];

		return wp_remote_get( $test_url, $args );
	}

	/**
	 * Check LP beta version from remote config.
	 *
	 * @return bool|array
	 */
	public static function check_lp_beta_version() {
		$url    = 'https://learnpress.github.io/learnpress/lp-beta-version.json';
		$config = new stdClass();

		try {
			$res = wp_remote_get( $url );
			if ( is_wp_error( $res ) ) {
				throw new Exception( $res->get_error_message() );
			}

			$config  = LP_Helper::json_decode( wp_remote_retrieve_body( $res ) );
			$version = $config->version ?? 0;
			if ( ! $version ) {
				throw new Exception( 'Version LP beta is invalid!' );
			}

			if ( ! version_compare( $version, LEARNPRESS_VERSION, '>' ) ) {
				return false;
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $config;
	}
}
