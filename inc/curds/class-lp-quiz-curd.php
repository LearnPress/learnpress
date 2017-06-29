<?php

class LP_Quiz_CURD implements LP_Interface_CURD {
	public function load( &$quiz ) {
		$the_id = $quiz->get_id();
		if ( ! $the_id || LP_QUIZ_CPT !== get_post_type( $the_id ) ) {
			throw new Exception( __( 'Invalid quiz.', 'learnpress' ) );
		}
		$this->load_questions( $quiz );
		$this->update_meta_cache( $quiz );
	}

	public function update() {
		// TODO: Implement update() method.
	}

	public function delete() {
		// TODO: Implement delete() method.
	}

	public function load_questions( &$quiz ) {

	}

	public function update_meta_cache( $quiz ) {

	}
}