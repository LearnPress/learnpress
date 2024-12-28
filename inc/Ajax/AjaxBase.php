<?php
/**
 * class AjaxBase
 *
 * @since 4.2.7.6
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

use LearnPress\Helpers\Singleton;

class AjaxBase {
	use Singleton;

	public function init() {
		$this->catch_lp_ajax();
	}

	public function catch_lp_ajax() {
		if ( ! empty( $_REQUEST['lp-ajax'] ) ) {
			$action = $_REQUEST['lp-ajax'];
			$action = str_replace( '-', '_', $action );
			$action = 'lp_ajax_' . $action;
			if ( is_callable( $action ) ) {
				call_user_func( $action );
			}
			wp_die( '0', 400 );
		}
	}
}
