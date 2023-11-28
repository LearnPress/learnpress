<?php
/**
 * Template load via AJAX.
 *
 * @since 4.2.5.7
 * @version 1.0.0
 */

namespace learnpress\inc\TemplateHooks;

use LearnPress\Helpers\Template;
use Throwable;

class TemplateAJAX {
	/**
	 * @param array $args
	 * @param array $callback [ 'class', 'method' ]
	 *
	 * @return string
	 */
	public static function send_data_to_load_ajax( array $args = [], array $callback = [] ): string {
		wp_enqueue_script( 'lp-load-template-via-ajax' );
		$content = '';

		try {
			$html_wrapper = [
				'<div class="lp-load-ajax-element">' => '</div>',
			];
			$content      = sprintf(
				'%s<span class="lp-loading-circle hide"></span>',
				__( 'Load more', 'learnpress' )
			);
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ' ' . $e->getMessage() );
		}

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}
}
