<?php
/**
 * Template hooks Question in Course Builder.
 *
 * @since 4.3.6
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder\Question;

use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;

class BuilderQuestionTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/questions/layout', [ $this, 'layout' ] );
	}

	public function layout( array $data = [] ) {
		// Check to switch layout.
		$item_id         = CourseBuilder::get_item_id();
		$data['item_id'] = $item_id;

		if ( ! empty( $item_id ) ) {
			// Show edit question
			BuilderEditQuestionTemplate::instance()->layout( $data );
		} else {
			// Show list questions
			BuilderListQuestionsTemplate::instance()->layout( $data );
		}
	}

	public function get_link_edit( $question_id = 0 ) {
		if ( ! $question_id ) {
			return '';
		}

		return CourseBuilder::get_tab_link( 'questions', $question_id );
	}
}
