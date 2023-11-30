<?php
/**
 * Template load via AJAX.
 *
 * @since 4.2.5.7
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks;

use LearnPress\Helpers\Template;
use Throwable;

class TemplateAJAX {
	/**
	 * @param string $content
	 * @param array $args
	 * @param array $callback [ 'class', 'method' ]
	 *
	 * @return string
	 * @since 4.2.5.7
	 * @version 1.0.0
	 */
	public static function load_content_via_ajax( string $content = '', array $args = [], array $callback = [] ): string {
		$html_wrapper = '';

		try {
			$data         = [
				'args'     => $args,
				'callback' => $callback,
			];
			$data_send    = esc_attr( htmlentities2( json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );
			$el_has_data = sprintf( '<div class="lp-load-ajax-element" data-send="%s">', $data_send );
			$html_wrapper = [
				$el_has_data => '</div>',
			];
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ' ' . $e->getMessage() );
		}

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}
}
