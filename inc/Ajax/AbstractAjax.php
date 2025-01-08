<?php
/**
 * class AjaxBase
 *
 * @since 4.2.7.6
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

/**
 * @use LoadContentViaAjax::load_content_via_ajax
 */
abstract class AbstractAjax {
	public static function catch_lp_ajax() {
		if ( ! empty( $_REQUEST['lp-load-ajax'] ) ) {
			$action = $_REQUEST['lp-load-ajax'];
			$nonce  = $_REQUEST['nonce'] ?? '';
			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				wp_die( 'Invalid request!', 400 );
			}

			$class = new static();
			if ( is_callable( [ $class, $action ] ) ) {
				call_user_func( [ $class, $action ] );
			}
		}
	}
}
