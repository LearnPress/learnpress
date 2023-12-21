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
 * @version 1.0.1
 */

namespace LearnPress\TemplateHooks;

use Exception;
use LearnPress\Helpers\Template;
use stdClass;
use Throwable;

class TemplateAJAX {
	/**
	 * When AJAX load done will innerHTML to class lp-target self.
	 * To use this, you need add hook 'lp/rest/ajax/allow_callback' to register for security reasons.
	 *
	 * @param array $args [ method_request' => 'GET/POST', 'el_loading_before_show_content' => '', 'el_loading_after_content_loaded' => '',... ],
	 * method_request: default is POST, use to set method type call to server.
	 * html_loading_before_show_content: html loading before content call API to render.
	 * html_loading_after_content_loaded: html loading when content rendered, and change.
	 * html_no_load_ajax_first: if not empty, remove el_loading_before_show_content, will not load ajax first, only send data to handle AJAX via event.
	 *
	 * @param array $callback [ 'class', 'method' ] method use to render content html
	 *
	 * @return string
	 * @since 4.2.5.7
	 * @version 1.0.1
	 */
	public static function load_content_via_ajax( array $args = [], array $callback = [] ): string {
		$html_wrapper = [
			'<div class="lp-load-ajax-element">' => '</div>',
		];
		$html_content = '';

		try {
			if ( ! isset( $callback['class'] ) || ! isset( $callback['method'] ) ) {
				throw new Exception( 'Missing args callback class || method' );
			}

			if ( isset( $args['html_no_load_ajax_first'] ) ) {
				$html_wrapper = [];
			}

			$target_id = uniqid( 'lp-target-' );
			$data      = [
				'args'     => $args,
				'callback' => $callback,
				'id'       => $target_id,
			];

			// Remove argument html no need send via AJAX.
			if ( ! empty( $args ) ) {
				$keys_unset = [
					'html_no_load_ajax_first',
					'html_loading_before_show_content',
					'html_loading_after_content_loaded',
				];

				foreach ( $keys_unset as $key ) {
					if ( isset( $data['args'][ $key ] ) ) {
						unset( $data['args'][ $key ] );
					}
				}
			}

			// If empty args, set to stdClass to JS read like an object, not array.
			if ( empty( $data['args'] ) ) {
				$data['args'] = new stdClass();
			}

			$data_send = esc_attr( htmlentities2( json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );
			ob_start();
			lp_skeleton_animation_html( 10 );
			$el_loading_before_show_content_default = ob_get_clean();

			$el_loading_before_show_content = sprintf(
				'<div class="loading-first">%s</div>',
				$args['html_loading_before_show_content'] ?? $el_loading_before_show_content_default
			);
			if ( isset( $args['html_no_load_ajax_first'] ) ) {
				$el_loading_before_show_content = '';
			}

			$el_loading_after_content_loaded = sprintf(
				'<div class="loading-after">%s</div>',
				$args['html_loading_after_content_loaded'] ?? '<div class="lp-loading-change"></div>'
			);

			$html_el_target = sprintf(
				'<div class="lp-target" data-id="%s" data-send="%s">%s</div>',
				$target_id,
				$data_send,
				$args['html_no_load_ajax_first'] ?? ''
			);
			$html_content   = sprintf(
				'%1s%2s%3s',
				$el_loading_before_show_content,
				$html_el_target,
				$el_loading_after_content_loaded
			);
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ' ' . $e->getMessage() );
		}

		return Template::instance()->nest_elements( $html_wrapper, $html_content );
	}

	public function create_lp_target_with_data_for_load_ajax() {
	}
}
