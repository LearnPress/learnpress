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
	 * @param array $args [ 'el_target' => 'id/class', 'method_request' => 'GET/POST', 'el_loading_before_show_content' => '', 'el_loading_after_content_loaded' => '' ],
	 * id/class is target for js detect to attach content to $html_el_target,
	 * id has prefix '#', class has prefix '.'
	 * method_request: default is POST, use to set method type call to server.
	 * el_loading_before_show_content: html loading before content call API to render.
	 * el_loading_after_content_loaded: html loading when content rendered, and change.
	 *
	 * @param array $callback [ 'class', 'method' ] method use to render content html
	 *
	 * @return string
	 * @since 4.2.5.7
	 * @version 1.0.0
	 */
	public static function load_content_via_ajax( string $html_el_target = '', array $args = [], array $callback = [] ): string {
		$html_wrapper = '';
		$html_content = '';

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

			$target_id   = uniqid( 'lp-load-ajax-element-' );
			$data        = [
				'args'     => $args,
				'callback' => $callback,
				'id'       => $target_id,
			];
			$data_send   = esc_attr( htmlentities2( json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );
			$el_has_data = sprintf( '<div class="lp-load-ajax-element" data-send="%s" data-id="%s">', $data_send, $target_id );
			ob_start();
			lp_skeleton_animation_html( 10 );
			$el_loading_before_show_content_default = ob_get_clean();
			$el_loading_before_show_content         = sprintf(
				'<div class="loading-first">%s</div>',
				$args['el_loading_before_show_content'] ?? $el_loading_before_show_content_default
			);
			$el_loading_after_content_loaded        = sprintf(
				'<div class="loading-after">%s</div>',
				$args['el_loading_after_content_loaded'] ?? '<div class="lp-loading-change"></div>'
			);
			$html_wrapper                           = [
				$el_has_data => '</div>',
			];
			$html_content                           = $el_loading_before_show_content . $html_el_target . $el_loading_after_content_loaded;
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ' ' . $e->getMessage() );
		}

		return Template::instance()->nest_elements( $html_wrapper, $html_content );
	}
}
