<?php

namespace LearnPress\Shortcodes\Course;

use LearnPress\Shortcodes\AbstractShortcode;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;

/**
 * Class AbstractListCourseShortcode
 *
 * @package LearnPress\Shortcodes
 * @since 4.2.6.4
 * @version 1.0.0
 */
abstract class AbstractListCourseShortcode extends AbstractShortcode {

	public function render( $attrs ): string {
		wp_enqueue_style( 'learnpress' );

		ob_start();

		echo '<div class="lp-archive-courses">';
		echo '<ul class="learn-press-courses grid" data-layout="grid">';

		$courses = $attrs['courses'] ?? [];
		foreach ( $courses as $course_obj ) {
			$course = learn_press_get_course( $course_obj->ID );
			if ( ! $course ) {
				continue;
			}

			echo ListCoursesTemplate::render_course( $course );
		}

		echo '</ul>';
		echo '</div>';

		return ob_get_clean();
	}
}

