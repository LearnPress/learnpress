<?php
/**
 * Template load via AJAX.
 * Use for load any content via AJAX.
 *
 * Logic:
 * 1. Create html has class .lp-load-ajax-element attach setting, args want to handle on Template.
 * 2. Create html element target to attach content via AJAX.
 * 3. (loadAJAX.js) JS detect html element target and send data to server.
 * 4. (LP_REST_AJAX_Controller) Server receive data, call callback Class::Method and render content.
 *
 * @since 4.2.5.7
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks;

use Exception;
use LearnPress\Helpers\Template;
use Throwable;

class TemplateAJAX {
	/**
	 * @param string $html_el_target html content element want to attach innerHTML.
	 * EX: '<div id="el-want-attach-content-ajax"></div>'
	 *
	 * @param array $args [ 'el_target' => 'id/class', 'method_request' => 'GET/POST' ],
	 * id/class is target for js detect to attach content to $html_el_target,
	 * id has prefix '#', class has prefix '.'
	 *
	 * @param array $callback [ 'class', 'method' ] method use to render content html
	 *
	 * @return string
	 * @since 4.2.5.7
	 * @version 1.0.0
	 */
	public static function load_content_via_ajax( string $html_el_target = '', array $args = [], array $callback = [] ): string {
		$html_wrapper = '';

		try {
			if ( empty( $html_el_target ) ) {
				throw new Exception( 'Missing html element target' );
			}

			if ( ! isset( $args['el_target'] ) ) {
				throw new Exception( 'Missing args el_target' );
			}

			if ( ! isset( $callback['class'] ) || ! isset( $callback['method'] ) ) {
				throw new Exception( 'Missing args callback class || method' );
			}

			$data         = [
				'args'     => $args,
				'callback' => $callback,
			];
			$data_send    = esc_attr( htmlentities2( json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );
			$el_has_data  = sprintf( '<div class="lp-load-ajax-element" data-send="%s">', $data_send );
			$html_wrapper = [
				$el_has_data => '</div>',
			];
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ' ' . $e->getMessage() );
		}

		return Template::instance()->nest_elements( $html_wrapper, $html_el_target );
	}
}
