<?php
/**
 * Template hooks Quiz in Course Builder.
 *
 * @since 4.3.6
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder\Quiz;

use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;

class BuilderQuizTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/quizzes/layout', [ $this, 'layout' ] );
	}

	public function layout( array $data = [] ) {
		// Check to switch layout.
		$item_id         = CourseBuilder::get_item_id();
		$data['item_id'] = $item_id;

		if ( ! empty( $item_id ) ) {
			// Show edit quiz
			BuilderEditQuizTemplate::instance()->layout( $data );
		} else {
			// Show list quizzes
			BuilderListQuizzesTemplate::instance()->layout( $data );
		}
	}

	public function get_link_edit( $quiz_id = 0 ) {
		if ( ! $quiz_id ) {
			return '';
		}

		return CourseBuilder::get_tab_link( 'quizzes', $quiz_id );
	}
}
