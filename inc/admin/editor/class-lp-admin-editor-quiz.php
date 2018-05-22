<?php

/**
 * Class LP_Admin_Editor_Quiz
 *
 * @since 3.0.2
 */
class LP_Admin_Editor_Quiz extends LP_Admin_Editor {

	/**
	 * @var LP_Quiz_CURD
	 */
	protected $quiz_curd = null;

	/**
	 * @var LP_Question_CURD
	 */
	protected $question_curd = null;

	/**
	 * @var LP_Quiz
	 */
	protected $quiz = null;

	/**
	 * LP_Admin_Editor_Quiz constructor.
	 */
	public function __construct() {
	}

	/**
	 * Do the action depending on ajax calls with params
	 *
	 * @return bool|mixed|WP_Error
	 */
	public function dispatch() {
		check_ajax_referer( 'learnpress_admin_quiz_editor', 'nonce' );
		$args = wp_parse_args( $_REQUEST, array( 'id' => false, 'type' => '' ) );

		// get quiz
		$quiz_id = $args['id'];
		$quiz    = learn_press_get_quiz( $quiz_id );

		if ( ! $quiz ) {
			return new WP_Error( 'INVALID_QUIZ', __( 'Invalid quiz', 'learnpress' ) );
		}

		$this->quiz          = $quiz;
		$this->quiz_curd     = new LP_Quiz_CURD();
		$this->question_curd = new LP_Question_CURD();
		$this->result        = array( 'status' => false );

		$this->call( $args['type'], array( $args ) );

		return $this->get_result();
	}

	/**
	 * Get question data in admin quiz editor.
	 *
	 * @since 3.0.0
	 *
	 * @param       $question
	 * @param       $object | if true, input in question object, do not need init LP_Question::get_question()
	 * @param array $args
	 *
	 * @return array
	 */
	public function get_question_data_to_quiz_editor( $question, $object = false, $args = array() ) {

		if ( ! $object ) {
			if ( get_post_type( $question ) !== LP_QUESTION_CPT ) {
				return array();
			}

			// get question
			$question = LP_Question::get_question( $question );
		}

		// question id
		$question_id = $question->get_id();
		// question answer
		$answers = array_values( $question->get_data( 'answer_options' ) );

		$data = wp_parse_args( $args, array(
			'id'       => $question_id,
			'open'     => false,
			'title'    => get_the_title( $question_id ),
			'type'     => array(
				'key'   => $question->get_type(),
				'label' => $question->get_type_label()
			),
			'answers'  => $answers,
			'settings' => array(
				'mark'        => get_post_meta( $question_id, '_lp_mark', true ),
				'explanation' => get_post_meta( $question_id, '_lp_explanation', true ),
				'hint'        => get_post_meta( $question_id, '_lp_hint', true )
			),
			'order'    => count( $answers )
		) );

		return $data;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function hidden_questions( $args = array() ) {
		$hidden = ! empty( $args['hidden'] ) ? $args['hidden'] : false;
		update_post_meta( $this->quiz->get_id(), '_lp_hidden_questions', $hidden );

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function new_question( $args = array() ) {
		// new question
		$question = ! empty( $args['question'] ) ? $args['question'] : false;
		$question = json_decode( wp_unslash( $question ), true );

		if ( ! $question ) {
			return false;
		}

		$quiz_id = $this->quiz->get_id();

		// draft quiz
		if ( get_post_status( $quiz_id ) == 'auto-draft' ) {

			$draft_quiz = ! empty( $args['draft_quiz'] ) ? $args['draft_quiz'] : '';
			$draft_quiz = (array) ( json_decode( wp_unslash( $draft_quiz ), '' ) );

			$quiz_args = array(
				'id'      => $this->quiz->get_id(),
				'title'   => $draft_quiz['title'] ? $draft_quiz['title'] : __( 'New Quiz', 'learnpress' ),
				'content' => $draft_quiz['content'],
				'status'  => 'draft'
			);

			$quiz_id = $this->quiz_curd->create( $quiz_args );
		}

		if ( ! isset( $quiz_id ) ) {
			$this->result = new WP_Error( 'CREATE_QUIZ_FAILED', __( 'Quiz creation failed.', 'learnpress' ) );

			return false;
		}

		$args = array(
			'quiz_id' => $quiz_id,
			'title'   => $question['title'],
			'type'    => $question['type'],
		);

		$new_question = $this->question_curd->create( $args );

		if ( ! is_wp_error( $new_question ) ) {

			// update hidden questions in quiz meta
			$quiz             = LP_Quiz::get_quiz( $quiz_id );
			$hidden_questions = $quiz->get_questions();

			if ( $hidden_questions ) {
				unset( $hidden_questions[ $new_question->get_id() ] );
				$hidden_questions = array_keys( $hidden_questions );
			}

			update_post_meta( $quiz_id, '_lp_hidden_questions', $hidden_questions );

			// get new question data
			$this->result = $this->get_question_data_to_quiz_editor( $new_question, true, array( 'open' => true ) );

			if ( isset( $question['id'] ) ) {
				$this->result['temp_id'] = $question['id'];
			}

			return true;
		}

		return false;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function sort_questions( $args = array() ) {
		$order = ! empty( $args['order'] ) ? $args['order'] : false;
		$order = json_decode( wp_unslash( $order ), true );

		if ( ! $order ) {
			return false;
		}

		$this->result = $this->quiz_curd->sort_questions( $order );

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function update_question_title( $args = array() ) {
		$question = ! empty( $args['question'] ) ? $args['question'] : false;
		$question = json_decode( wp_unslash( $question ), true );

		if ( ! $question ) {
			return false;
		}

		wp_update_post( array( 'ID' => $question['id'], 'post_title' => $question['title'] ) );

		$this->result['status'] = true;

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function change_question_type( $args = array() ) {
		$question_id = ! empty( $args['question_id'] ) ? $args['question_id'] : false;
		$type        = ! empty( $args['question_type'] ) ? $args['question_type'] : false;

		if ( ! ( $question_id || $type ) ) {
			return false;
		}

		$question = LP_Question::get_question( $question_id );

		// change question type
		$question = $this->question_curd->change_question_type( $question, $type );

		$this->result = $this->get_question_data_to_quiz_editor( $question, true );

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function clone_question( $args = array() ) {
		$question = ! empty( $args['question'] ) ? $args['question'] : false;
		$question = json_decode( wp_unslash( $question ), true );

		if ( ! $question ) {
			return false;
		}

		// duplicate question
		$new_question_id = $this->question_curd->duplicate( $question['id'], array( 'post_status' => 'publish' ) );

		if ( ! is_wp_error( $new_question_id ) ) {

			// add question to hidden questions in quiz meta
			$hidden_questions = get_post_meta( $this->quiz->get_id(), '_lp_hidden_questions', true );
			if ( ! $hidden_questions ) {
				$hidden_questions = array();
			}
			$hidden_questions[] = $new_question_id;// add question to hidden questions in quiz meta
			update_post_meta( $this->quiz->get_id(), '_lp_hidden_questions', $hidden_questions );

			// add question to quiz
			$this->quiz_curd->add_question( $this->quiz->get_id(), $new_question_id );

			$this->result = $this->get_question_data_to_quiz_editor( $new_question_id );

			return true;
		}

		return false;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function remove_question( $args = array() ) {
		$question_id = ! empty( $args['question_id'] ) ? $args['question_id'] : false;

		if ( ! $question_id ) {
			return false;
		}

		$this->result = $this->quiz_curd->remove_questions( $this->quiz->get_id(), $question_id );

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function delete_question( $args = array() ) {
		$question_id = ! empty( $args['question_id'] ) ? $args['question_id'] : false;

		if ( ! $question_id ) {
			return false;
		}

		$this->result = wp_trash_post( $question_id );

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function sort_question_answers( $args = array() ) {
		$question_id = ! empty( $args['question_id'] ) ? $args['question_id'] : false;

		$order = ! empty( $args['order'] ) ? $args['order'] : false;
		$order = json_decode( wp_unslash( $order ), true );


		if ( ! ( $question_id && $order ) ) {
			return false;
		}

		// sort answer
		$this->result = $this->question_curd->sort_answers( $question_id, $order );

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function update_question_answer_title( $args = array() ) {
		// question id
		$question_id = ! empty( $args['question_id'] ) ? $args['question_id'] : false;

		// answers
		$answer = ! empty( $args['answer'] ) ? $args['answer'] : false;
		$answer = json_decode( wp_unslash( $answer ), true );

		if ( ! ( $question_id && $answer ) ) {
			return false;
		}

		// update answer title
		$this->result = $this->question_curd->update_answer_title( $question_id, $answer );

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function change_question_correct_answer( $args = array() ) {
		$question_id = ! empty( $args['question_id'] ) ? $args['question_id'] : false;

		// correct answer
		$correct = ! empty( $args['correct'] ) ? $args['correct'] : false;
		$correct = json_decode( wp_unslash( $correct ), true );

		if ( ! ( $question_id && $correct ) ) {
			return false;
		}

		$question = LP_Question::get_question( $question_id );
		// update correct answer, get new question
		$question = $this->question_curd->change_correct_answer( $question, $correct );

		$this->result = $this->get_question_data_to_quiz_editor( $question, true );

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function delete_question_answer( $args = array() ) {
		$question_id = isset( $_POST['question_id'] ) ? $_POST['question_id'] : false;
		$answer_id   = isset( $_POST['answer_id'] ) ? intval( $_POST['answer_id'] ) : false;

		if ( ! ( $question_id && $answer_id ) ) {
			return false;
		}

		$this->result = $this->question_curd->delete_answer( $question_id, $answer_id );

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function new_question_answer( $args = array() ) {
		$question_id = ! empty( $args['question_id'] ) ? $args['question_id'] : false;

		if ( ! $question_id ) {
			return false;
		}

		// new answer
		$answer = LP_Question::get_default_answer();
		// add new
		$new_answer_id = $this->question_curd->new_answer( $question_id, $answer );

		$question = LP_Question::get_question( $question_id );

		if ( $new_answer_id ) {
			$this->result = array_merge( $answer, array(
				'temp_id'            => isset( $args['question_answer_id'] ) ? $args['question_answer_id'] : 0,
				'question_answer_id' => $new_answer_id,
				'question_id'        => $question_id,
				'answer_order'       => count( $question->get_data( 'answer_options' ) )
			) );

			return true;
		}

		return false;
	}

	public function update_quiz_questions_hidden( $args = array() ) {
		$id        = $args['id'];
		$questions = $args['hidden'];

		if ( $questions ) {
			update_post_meta( $id, '_hidden_questions_settings', $questions );
		} else {
			delete_post_meta( $id, '_hidden_questions_settings' );
		}

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function update_question_content( $args = array() ) {
		$question = ! empty( $args['question'] ) ? $args['question'] : false;
		$question = json_decode( wp_unslash( $question ), true );

		if ( ! $question ) {
			return false;
		}

		wp_update_post( array(
			'ID'           => $question['id'],
			'post_content' => $question['settings']['content']
		) );

		$this->result['status'] = true;

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function update_question_meta( $args = array() ) {
		$question = ! empty( $args['question'] ) ? $args['question'] : false;
		$question = json_decode( wp_unslash( $question ), true );

		$meta_key = ! empty( $args['meta_key'] ) ? $args['meta_key'] : false;

		if ( ! ( $question && $meta_key ) ) {
			return false;
		}

		update_post_meta( $question['id'], '_lp_' . $meta_key, $question['settings'][ $meta_key ] );

		$this->result['status'] = true;

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function search_items( $args = array() ) {
		$query   = ! empty( $args['query'] ) ? $args['query'] : '';
		$page    = ! empty( $args['page'] ) ? intval( $args['page'] ) : 1;
		$exclude = ! empty( $args['exclude'] ) ? intval( $args['exclude'] ) : '';

		if ( $exclude ) {
			$exclude = json_decode( $exclude, true );
		}

		$ids_exclude = array();
		if ( is_array( $exclude ) ) {
			foreach ( $exclude as $item ) {
				$ids_exclude[] = $item['id'];
			}
		}

		$search = new LP_Modal_Search_Items( array(
			'type'       => 'lp_question',
			'context'    => 'quiz',
			'context_id' => $this->quiz->get_id(),
			'term'       => $query,
			'limit'      => apply_filters( 'learn-press/quiz-editor/choose-items-limit', 10 ),
			'paged'      => $page,
			'exclude'    => $ids_exclude
		) );

		$ids_item = $search->get_items();

		$items = array();
		foreach ( $ids_item as $id ) {
			$post = get_post( $id );

			$items[] = array(
				'id'    => $post->ID,
				'title' => $post->post_title,
				'type'  => $post->post_type
			);
		}

		$this->result = array(
			'items'      => $items,
			'pagination' => $search->get_pagination( false )
		);

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function add_questions_to_quiz( $args = array() ) {
		// added questions
		$questions = isset( $_POST['items'] ) ? $_POST['items'] : false;
		$questions = json_decode( wp_unslash( $questions ), true );

		if ( ! $questions ) {
			return false;
		}

		$quiz_id = $this->quiz->get_id();

		// draft quiz
		if ( get_post_status( $quiz_id ) == 'auto-draft' ) {

			$draft_quiz = ! empty( $args['draft_quiz'] ) ? $args['draft_quiz'] : '';
			$draft_quiz = (array) ( json_decode( wp_unslash( $draft_quiz ), '' ) );

			$quiz_args = array(
				'id'      => $quiz_id,
				'title'   => $draft_quiz['title'],
				'content' => $draft_quiz['content'],
				'status'  => 'draft'
			);

			$quiz_id = $this->quiz_curd->create( $quiz_args );
		}

		if ( ! isset( $quiz_id ) ) {
			$this->result = new WP_Error( __( 'Quiz creation failed.', 'learnpress' ) );

			return false;
		}

		if ( $questions ) {
			// add question to hidden questions in quiz meta
			$hidden_questions = get_post_meta( $quiz_id, '_lp_hidden_questions', true );

			if ( ! $hidden_questions ) {
				$hidden_questions = array();
			}

			foreach ( $questions as $key => $question ) {
				// add question to hidden questions in quiz meta
				$hidden_questions[] = $question['id'];
				update_post_meta( $quiz_id, '_lp_hidden_questions', $hidden_questions );
				// add question to quiz
				$this->quiz_curd->add_question( $quiz_id, $question['id'] );
			}
			$this->result = $this->quiz->quiz_editor_get_questions();

			return true;
		}

		return false;
	}
}