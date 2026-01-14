<?php

namespace LearnPress\Ajax;

class ExportOrderCSVAjax extends AbstractAjax
{
	public function __construct()
	{

	}

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
