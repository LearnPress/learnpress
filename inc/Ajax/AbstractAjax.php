<?php
/**
 * class AjaxBase
 *
 * @since 4.2.7.6
 * @version 1.0.5
 */

namespace LearnPress\Ajax;

/**
 * @use LoadContentViaAjax::load_content_via_ajax
 *
 * $action must unique name on all Ajax classes.
 * Because not specify a specific class.
 */
abstract class AbstractAjax {
	public static function catch_lp_ajax() {
		if ( ! empty( $_REQUEST['lp-load-ajax'] ) ) {
			$action = $_REQUEST['lp-load-ajax'];
			$nonce  = $_REQUEST['nonce'] ?? '';
			$class  = new static();

			if ( ! method_exists( $class, $action ) ) {
				return;
			}

			// For case cache HTML, so cache nonce is not required.
			$class_no_nonce = [
				LoadContentViaAjax::class,
			];

			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				if ( ! in_array( get_class( $class ), $class_no_nonce ) ) {
					wp_die( 'Invalid request!', 400 );
				}
			}

			if ( is_callable( [ $class, $action ] ) ) {
				call_user_func( [ $class, $action ] );
			}
		}
	}
}
