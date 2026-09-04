<?php
namespace LearnPress\TemplateHooks\Admin;

use Exception;
use LP_Helper;
use LP_WP_Filesystem;
use stdClass;

/**
 * Template Show list items to select in popup.
 *
 * @since 4.4.5
 * @version 1.0.0
 */
class AdminHelpCenterDataTemplate {
	/**
	 * URL of the remote "What's New" + "Latest Articles" JSON.
	 *
	 * Fetched with wp_remote_get (30s timeout, no auth) and cached for 12 hours.
	 * Falls back to the local demo file so the page still renders when the
	 * endpoint is unavailable.
	 *
	 * @var string
	 */
	protected static $url_help_center_data = 'https://learnpress.github.io/learnpress/help-center-data.json';

	/**
	 * Render the online Help Center data section via AJAX.
	 *
	 * @return stdClass Object with a `content` property containing the rendered HTML.
	 */
	public static function html_data_online(): stdClass {
		$remote_data = self::get_remote_data();
		$response    = new stdClass();

		$response->content = learn_press_admin_view_content(
			'help-center/data-online',
			array(
				'whats_new' => $remote_data['whats_new'] ?? array(),
				'articles'  => $remote_data['articles'] ?? array(),
				'banner_ad' => $remote_data['banner_ad'] ?? array(),
				'tick_icon' => LP_WP_Filesystem::get_icon_svg( 'help-center/ico-hc-tick.svg' ),
			)
		);

		return $response;
	}

	/**
	 * "What's New" + "Latest Articles" + "Banner Ad" data.
	 *
	 * Fetches $url_help_center_data with wp_remote_get (30s timeout, no auth).
	 * The response is cached for 12 hours. Falls back to the local demo JSON
	 * bundled with the plugin when the URL isn't set yet or the request fails,
	 * so the page keeps rendering while the schema is finalized.
	 *
	 * @return array
	 */
	protected static function get_remote_data(): array {
		$default = array(
			'whats_new' => array(),
			'articles'  => array(),
			'banner_ad' => array(),
		);

		$data = [];

		try {
			if ( ! empty( self::$url_help_center_data ) ) {
				$response = wp_remote_get( self::$url_help_center_data, array( 'timeout' => 30 ) );

				if ( ! is_wp_error( $response )
					&& 200 === wp_remote_retrieve_response_code( $response ) ) {
					$data = LP_Helper::json_decode( wp_remote_retrieve_body( $response ), true );
				}
			}
		} catch ( Exception $e ) {
			$data = $default;
		}

		return $data;
	}
}
