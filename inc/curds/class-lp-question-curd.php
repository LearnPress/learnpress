<?php

class LP_Question_CURD implements LP_Interface_CURD {
	public function delete() {
		// TODO: Implement delete() method.
	}

	/**
	 * @param LP_Question $question
	 *
	 * @return mixed
	 */
	public function load( &$question ) {
		$the_id = $question->get_id();
		if ( ! $the_id || LP_QUESTION_CPT !== get_post_type( $the_id ) ) {
			LP_Debug::throw_exception( sprintf( __( 'Invalid question with ID "%d".', 'learnpress' ), $the_id ) );
		}
		$this->_load_answer_options($question);
	}

	public function update() {
		// TODO: Implement update() method.
	}

	/**
	 * Load answer options for the question from database.
	 * Load from cache if data is already loaded into cache.
	 * Otherwise, load from database and put to cache.
	 */
	protected function _load_answer_options( &$question ) {
		$id             = $question->get_id();
		$answer_options = wp_cache_get( 'answer-options-' . $id, 'lp-questions' );
		if ( false === $answer_options ) {
			global $wpdb;
			$query = $wpdb->prepare( "
				SELECT *
				FROM {$wpdb->prefix}learnpress_question_answers
				WHERE question_id = %d
				ORDER BY answer_order ASC
			", $id );
			if ( $answer_options = $wpdb->get_results( $query, OBJECT_K ) ) {
				foreach ( $answer_options as $k => $v ) {
					$answer_options[ $k ] = (array) $answer_options[ $k ];
					if ( $answer_data = maybe_unserialize( $v->answer_data ) ) {
						foreach ( $answer_data as $data_key => $data_value ) {
							$answer_options[ $k ][ $data_key ] = $data_value;
						}
					}
					unset( $answer_options[ $k ]['answer_data'] );
				}
			}
			$answer_options = apply_filters( 'learn-press/question/load-answer-options', $answer_options, $id );
			$this->_load_answer_option_meta( $answer_options );
			wp_cache_set( 'answer-options-' . $id, $answer_options, 'lp-questions' );
		}
		$question->set_data( 'answer_options', $answer_options );
	}

	/**
	 * Load meta data for answer options.
	 *
	 * @param array $answer_options
	 *
	 * @return mixed;
	 */
	protected function _load_answer_option_meta( &$answer_options ) {
		global $wpdb;
		if ( ! $answer_options ) {
			return false;
		}
		$answer_option_ids = wp_list_pluck( $answer_options, 'question_answer_id' );
		$format            = array_fill( 0, sizeof( $answer_option_ids ), '%d' );
		$query             = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->prefix}learnpress_question_answermeta
			WHERE learnpress_question_answer_id IN(" . join( ', ', $format ) . ")
		", $answer_option_ids );
		if ( $metas = $wpdb->get_results( $query ) ) {
			foreach ( $metas as $meta ) {
				$key        = $meta->meta_key;
				$option_key = $meta->learnpress_question_answer_id;
				if ( ! empty( $answer_options[ $option_key ] ) ) {
					if ( $key == 'checked' ) {
						$key = 'is_true';
					}
					$answer_options[ $option_key ][ $key ] = $meta->meta_value;
				}
			}
		}

		return true;
	}
}