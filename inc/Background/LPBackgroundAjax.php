<?php
namespace LearnPress\Background;

use LP_Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class LPBackgroundAjax
 * To handle a function that can be run in background
 * Via call class:method extends AbstractAjax
 * $data_send: must have key 'lp-load-ajax' to call method handle
 *
 * @since 4.2.9.1
 * @version 1.0.1
 */
class LPBackgroundAjax {
	/**
	 * Method async handle
	 */
	public static function handle( array $data_send = [], array $args = [] ) {
		$url       = LP_Settings::url_handle_lp_ajax();
		$data_send = array_merge(
			[ 'nonce' => wp_create_nonce( 'wp_rest' ) ],
			$data_send
		);
		$args      = array_merge(
			[
				'timeout'   => 0.01,
				'blocking'  => false,
				'body'      => $data_send,
				'cookies'   => $_COOKIE,
				'sslverify' => is_ssl(),
				'headers'   => [
					'Referer' => $url,
				],
			],
			$args
		);
		wp_remote_post( $url, $args );
	}
}
