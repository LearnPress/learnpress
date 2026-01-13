<?php
/**
 * class AjaxBase
 *
 * @since 4.2.7.6
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

use Exception;
use LP_Helper;
use LP_REST_Response;
use stdClass;
use Throwable;

class LoadContentViaAjax extends AbstractAjax {
	public function load_content_via_ajax() {
		$response = new LP_REST_Response();

		try {
			$params = wp_unslash( $_REQUEST['data'] ?? '' );
			if ( empty( $params ) ) {
				throw new Exception( 'Error: params invalid!' );
			}

			$params = LP_Helper::json_decode( $params, true );

			if ( empty( $params['callback'] ) ||
				! isset( $params['args'] ) ) {
				throw new Exception( 'Error: params invalid!' );
			}

			// @var array $args
			$args     = $params['args'];
			$callBack = $params['callback'];

			if ( empty( $callBack['class'] ) ||
				empty( $callBack['method'] ) ) {
				throw new Exception( 'Error: callback invalid!' );
			}

			$class  = $callBack['class'];
			$method = $callBack['method'];

			// Security: check callback is registered.
			$allow_callbacks = apply_filters(
				'lp/rest/ajax/allow_callback',
				[
					'LP_Admin_Dashboard:order_statistic',
					'LP_Admin_Dashboard:plugin_status_content',
				]
			);
			$callBackStr     = $class . ':' . $method;
			if ( ! in_array( $callBackStr, $allow_callbacks ) ) {
				throw new Exception( 'Error: callback is not register!' );
			}

			// Check class and method is callable.
			if ( is_callable( [ $class, $method ] ) ) {
				$data = call_user_func( [ $class, $method ], $args );
			} else {
				throw new Exception( 'Error: callback is not callable!' );
			}

			if ( ! $data instanceof stdClass && ! isset( $data->content ) ) {
				throw new Exception( 'Error: data content invalid!' );
			}

			$response->message = $data->message ?? '';
			unset( $data->message );

			$response->status = 'success';
			$response->data   = $data;
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
