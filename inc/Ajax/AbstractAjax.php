<?php
/**
 * class AjaxBase
 *
 * @since 4.2.7.6
 * @version 1.0.7
 */

namespace LearnPress\Ajax;

use LP_Request;

/**
 * @use LoadContentViaAjax::load_content_via_ajax
 *
 * $action must unique name on all Ajax classes.
 * Because not specify a specific class.
 */
abstract class AbstractAjax {
	public static function catch_lp_ajax() {
		if ( ! empty( $_REQUEST['lp-load-ajax'] ) ) {
			$action = LP_Request::get_param( 'lp-load-ajax' );
			$nonce  = LP_Request::get_param( 'nonce' );
			$class  = new static();

			if ( ! method_exists( $class, $action ) ) {
				return;
			}

			/**
			 * For case cache HTML, nonce of guest is cached (expired), so is not required.
			 * Only apply for guest: nonce of guest is public, not protect anything.
			 * User logged in must always send a valid nonce.
			 * Callbacks must check permission and validate args itself.
			 */
			$class_no_nonce = [
				LoadContentViaAjax::class,
			];

			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				if ( is_user_logged_in()
					|| ! in_array( get_class( $class ), $class_no_nonce, true ) ) {
					// Signal for JS (loadAJAX.js) to refresh nonce and retry once, for case HTML is cached.
					header( 'X-LP-Nonce-Invalid: 1' );
					wp_die( 'Invalid request!', 400 );
				}
			}

			if ( is_callable( [ $class, $action ] ) ) {
				call_user_func( [ $class, $action ] );
			}
		}
	}
}
