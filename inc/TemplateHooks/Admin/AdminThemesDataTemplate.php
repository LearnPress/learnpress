<?php

namespace LearnPress\TemplateHooks\Admin;

use Exception;
use LP_Helper;
use stdClass;

class AdminThemesDataTemplate {

	protected static $url_themes_data = 'https://learnpress.github.io/learnpress/themes-data.json';

	public static function init(): void {
		add_filter(
			'lp/rest/ajax/allow_callback',
			array( __CLASS__, 'allow_ajax_callback' )
		);
	}

	public static function allow_ajax_callback( array $callbacks ): array {
		$callbacks[] = __CLASS__ . ':html_data_online';

		return $callbacks;
	}

	public static function html_data_online(): stdClass {
		$data = self::get_remote_data();

		$response = new stdClass();

		$response->content = learn_press_admin_view_content(
			'themes/data-online',
			array(
				'themes' => $data['themes'] ?? array(),
			)
		);

		return $response;
	}

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

				if (
					! is_wp_error( $response )
					&& 200 === wp_remote_retrieve_response_code( $response )
				) {
					$data = LP_Helper::json_decode(
						wp_remote_retrieve_body( $response ),
						true
					);
				}
			}
		} catch ( Exception $e ) {
			$data = array();
		}

		if (
			! is_array( $data )
			|| empty( $data['themes'] )
			|| ! is_array( $data['themes'] )
		) {
			$data = self::get_local_data();
		}

		return $data;
	}

	protected static function get_local_data(): array {
		$file = LP_PLUGIN_PATH . 'inc/admin/views/themes/themes-data.json';

		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			return array(
				'themes' => array(),
			);
		}

		$content = file_get_contents( $file );

		if ( false === $content ) {
			return array(
				'themes' => array(),
			);
		}

		$data = LP_Helper::json_decode( $content, true );

		if (
			! is_array( $data )
			|| empty( $data['themes'] )
			|| ! is_array( $data['themes'] )
		) {
			return array(
				'themes' => array(),
			);
		}

		return $data;
	}
}

AdminThemesDataTemplate::init();