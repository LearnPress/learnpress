<?php
/**
 * Shortcode display list instructors.
 */
namespace LearnPress\Shortcodes;

use LearnPress\Helpers\Singleton;

class ListInstructorsShortcode extends AbstractShortcode {
	use singleton;
	protected $shortcode_name = 'instructors';

	/**
	 * Show single instructor
	 *
	 * @param $attrs []
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		$content = '';

		ob_start();
		try {
			if ( empty( $attrs ) ) {
				$attrs = [];
			}
			do_action( 'learn-press/list-instructors/layout', $attrs );
			$content = ob_get_clean();
		} catch ( \Throwable $e ) {
			ob_end_clean();
			error_log( $e->getMessage() );
		}

		return $content;
	}
}

