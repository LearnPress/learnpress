<?php
/**
 * Shortcode display single instructor.
 */
namespace LearnPress\Shortcodes\Course;

use LearnPress\Helpers\Singleton;
use LearnPress\Shortcodes\AbstractShortcode;

class FilterCourseShortcode extends AbstractShortcode {
	use singleton;
	protected $shortcode_name = 'filter_course';

	/**
	 * Show Course Filter
	 *
	 * @param $attrs array
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
			$data = array_merge(
				[ 'params_url' => lp_archive_skeleton_get_args() ],
				$attrs
			);
			do_action( 'learn-press/filter-courses/layout', $data );
			$content = ob_get_clean();
		} catch ( \Throwable $e ) {
			ob_end_clean();
			error_log( $e->getMessage() );
		}

		return $content;
	}
}

