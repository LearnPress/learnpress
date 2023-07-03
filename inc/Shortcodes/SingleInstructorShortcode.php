<?php
/**
 * Shortcode display single instructor.
 */
namespace LearnPress\Shortcodes;

use LearnPress\Helpers\Singleton;

class SingleInstructorShortcode extends AbstractShortcode {
	use singleton;
	protected $shortcode_name = 'single_instructor';

	/**
	 * Show single instructor
	 *
	 * @param $attrs [instructor_id: int]
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		$content = '';

		try {
			if ( empty( $attrs ) ) {
				$attrs = [];
			}
			ob_start();
			do_action( 'learn-press/single-instructor/layout', $attrs );
			$content = ob_get_clean();
		} catch ( \Throwable $e ) {
			ob_end_clean();
			error_log( $e->getMessage() );
		}

		return $content;
	}
}

