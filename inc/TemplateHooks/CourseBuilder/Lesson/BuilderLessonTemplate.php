<?php
/**
 * Template hooks Lesson in Course Builder.
 *
 * @since 4.3.6
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder\Lesson;

use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;

class BuilderLessonTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/lessons/layout', [ $this, 'layout' ] );
	}

	public function layout( array $data = [] ) {
		// Check to switch layout.
		$item_id         = CourseBuilder::get_item_id();
		$data['item_id'] = $item_id;

		if ( ! empty( $item_id ) ) {
			// Show edit lesson
			BuilderEditLessonTemplate::instance()->layout( $data );
		} else {
			// Show list lessons
			BuilderListLessonsTemplate::instance()->layout( $data );
		}
	}

	public function get_link_edit( $lesson_id = 0 ) {
		if ( ! $lesson_id ) {
			return '';
		}

		return CourseBuilder::get_tab_link( 'lessons', $lesson_id );
	}
}
