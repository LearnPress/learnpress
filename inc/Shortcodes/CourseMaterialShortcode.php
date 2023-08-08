<?php
/**
 * Shortcode display single instructor.
 */
namespace LearnPress\Shortcodes;

use LearnPress\Helpers\Singleton;

class CourseMaterialShortcode extends AbstractShortcode {
	use singleton;
	protected $shortcode_name = 'course_materials';

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
			do_action( 'learn-press/course-material/layout', $attrs );
			$content = ob_get_clean();
		} catch ( \Throwable $e ) {
			ob_end_clean();
			error_log( $e->getMessage() );
		}

		return $content;
	}
}

