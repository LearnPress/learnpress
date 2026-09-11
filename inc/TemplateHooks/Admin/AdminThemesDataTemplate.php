<?php

namespace LearnPress\TemplateHooks\Admin;

use Exception;
use LP_Helper;
use stdClass;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdminThemesDataTemplate
 *
 * Display themes via AJAX
 * @since 4.4.7
 * @version 1.0.0
 */
class AdminThemesDataTemplate {

	protected static $url_themes_data = 'https://learnpress.github.io/learnpress/themes.json';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'lp/rest/ajax/allow_callback', array( __CLASS__, 'allow_ajax_callback' ) );
	}

	/**
	 * Add the themes AJAX callback to the allowlist.
	 *
	 * @param array $callbacks Allowed AJAX callbacks.
	 *
	 * @return array
	 */
	public static function allow_ajax_callback( array $callbacks ): array {
		/** @use self::html_data_online */
		$callbacks[] = __CLASS__ . ':html_data_online';

		return $callbacks;
	}

	/**
	 * Render themes data for the AJAX response.
	 *
	 * @return stdClass
	 */
	public static function html_data_online(): stdClass {
		$data = self::get_remote_data();

		$response = new stdClass();

		$response->content = learn_press_admin_view_content(
			'themes/data-online',
			array( 'themes' => $data )
		);

		return $response;
	}

	/**
	 * Get themes data from the remote source or local fallback.
	 *
	 * @return array
	 */
	protected static function get_remote_data(): array {
		$data = array();

		try {
			if ( ! empty( self::$url_themes_data ) ) {
				$response = wp_remote_get(
					self::$url_themes_data,
					array(
						'timeout' => 30,
					)
				);

				if ( ! is_wp_error( $response )
					&& 200 === wp_remote_retrieve_response_code( $response ) ) {
					$data = LP_Helper::json_decode(
						wp_remote_retrieve_body( $response ),
						true
					);
				}
			}

			// Get data local
			if ( empty( $data ) || ! is_array( $data ) ) {
				$data = self::get_local_data();
			}
		} catch ( Throwable $e ) {
			$data = array();
		}

		return $data;
	}

	/**
	 * Get themes data from the local JSON file.
	 *
	 * @return array
	 * @throws Exception If JSON decoding fails.
	 */
	protected static function get_local_data(): array {
		$file = LP_PLUGIN_PATH . 'inc/admin/views/themes/themes-data.json';

		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			return array();
		}

		$content = file_get_contents( $file );

		if ( false === $content ) {
			return array();
		}

		$data = LP_Helper::json_decode( $content, true );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return array();
		}

		return $data;
	}
}
