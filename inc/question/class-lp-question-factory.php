<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Question_Factory
 *
 * Helper class for creating a question.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
class LP_Question_Factory {

	/**
	 * Delete a question from database and it's related data.
	 *
	 * @param int $question_id
	 * @param int $quiz_id
	 *
	 * @return int|bool false on failed
	 */
	public static function delete_question( $question_id, $quiz_id = 0 ) {
		global $wpdb;
		$deleted = false;
		if ( LP_QUESTION_CPT === get_post_type( $question_id ) ) {
			// remove question answers
			$wpdb->delete( $wpdb->prefix . 'learnpress_question_answers', array( 'question_id' => $question_id ), array( '%d' ) );

			// remove from quiz
			$args = array( 'question_id' => $question_id );
			if ( $quiz_id ) {
				$args['quiz_id'] = $quiz_id;
			}
			$wpdb->delete( $wpdb->prefix . 'learnpress_quiz_questions', $args, array_fill( 0, sizeof( $args ), '%d' ) );

			// remove permanently question
			$deleted = wp_delete_post( $question_id );
		}

		return $deleted;
	}

	/**
	 * Convert a question from a type to a new type.
	 *
	 * @param int    $id
	 * @param string $from
	 * @param string $to
	 * @param array  $data
	 *
	 * @return LP_Question
	 */
	public static function convert_question( $id, $from, $to, $data = array() ) {
		/*if ( ! empty( $data['learn_press_question'] ) && ! empty( $data['learn_press_question'][ $id ] ) ) {
			$post_data = $data['learn_press_question'][ $id ];
		} else {
			$post_data = array();
		}
		if ( $question = self::get_question( $id ) ) {
			update_post_meta( $question->id, '_lp_type', $to );
			$question->type = $to;
			$question->save( $post_data );
		}

		update_post_meta( $question->id, '_lp_type', $to );*/

		if ( $from != $to ) {
			if ( $question = learn_press_get_question( $id ) ) {
				$old_data = $question->get_data();
				update_post_meta( $id, '_lp_type', $to );
				wp_cache_delete( 'answer-options-' . $id, 'lp-questions' );
				$new_question = learn_press_get_question( $id );
			}
		}

		return learn_press_get_question( $id );
	}

	////////////////////////////

	public static function init() {

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
			add_action( 'save_post', array( __CLASS__, 'save' ) );
			add_action( 'edit_form_after_editor', array( __CLASS__, 'admin_template' ), - 990 );
			add_action( 'learn_press_convert_question_type', array( __CLASS__, 'convert_question' ), 5, 4 );
			add_filter( 'learn_press_question_answers_data', array( __CLASS__, 'sanitize_answers' ), 10, 3 );
		}
		add_action( 'learn_press_load_quiz_question', array( __CLASS__, 'save_question_if_needed' ), 100, 3 );
		add_action( 'learn_press_user_finish_quiz', array( __CLASS__, 'save_question' ), 100, 2 );
		add_action( 'learn_press_after_quiz_question_title', array( __CLASS__, 'show_answer' ), 100, 2 );
		add_action( 'learn_press_after_question_wrap', array( __CLASS__, 'show_hint' ), 100, 2 );
		add_action( 'learn_press_after_question_wrap', array( __CLASS__, 'show_explanation' ), 110, 2 );
		//add_action( 'delete_post', array( __CLASS__, 'delete_question' ), 10, 2 );

		self::init_hooks();

		do_action( 'learn_press_question_factory_init', __CLASS__ );
	}


	public static function show_answer( $id, $quiz_id ) {

		$quiz   = LP_Quiz::get_quiz( $quiz_id );
		$status = LP()->user->get_quiz_status( $quiz_id );

		if ( $status != 'completed' || $quiz->show_result != 'yes' ) {
			return;
		}
		$question = LP_Question::get_question( $id );
		$user     = LP()->user;
		$question->render( array( 'quiz_id' => $quiz->id, 'course_id' => get_the_ID(), 'check' => true ) );
	}

	public static function show_hint( $id, $quiz_id ) {
		learn_press_get_template( 'content-question/hint.php' );
	}

	public static function show_explanation( $id, $quiz_id ) {
		learn_press_get_template( 'content-question/explanation.php' );
	}



	public static function sanitize_answers( $answers, $posted, $q ) {
		$func = "_sanitize_{$q->type}_answers";
		if ( is_callable( array( __CLASS__, $func ) ) ) {
			return call_user_func_array( array( __CLASS__, $func ), array( $answers, $q ) );
		}

		return $answers;
	}

	protected static function _sanitize_multi_choice_answers( $answers, $q ) {
		$size = sizeof( $answers );
		if ( $size == 0 ) {
			$answers = $q->get_default_answers();
		}
		$answers     = array_values( $answers );
		$has_checked = false;
		foreach ( $answers as $k => $answer ) {
			if ( empty( $answer['answer_data']['is_true'] ) || $answer['answer_data']['is_true'] != 'yes' ) {
				$answers[ $k ]['answer_data']['is_true'] = 'no';
				continue;
			}
			$has_checked = true;
		}
		if ( ! $has_checked ) {
			$answers[0]['answer_data']['is_true'] = 'yes';
		}

		return $answers;
	}

	protected static function _sanitize_true_or_false_answers( $answers, $q ) {
		$size = sizeof( $answers );
		if ( $size > 2 ) {
			$answers = array_slice( $answers, 0, 2 );
		} elseif ( $size == 1 ) {
			$answers[] = array(
				'is_true' => 'no',
				'value'   => learn_press_uniqid(),
				'text'    => __( 'Option', 'learnpress' )
			);
		} elseif ( $size == 0 ) {
			return $answers;
		}
		$answers     = array_values( $answers );
		$has_checked = false;
		foreach ( $answers as $k => $answer ) {
			if ( $has_checked || empty( $answer['answer_data']['is_true'] ) || $answer['answer_data']['is_true'] != 'yes' ) {
				$answers[ $k ]['answer_data']['is_true'] = 'no';
				continue;
			}
			$has_checked = true;
		}
		if ( ! $has_checked ) {
			$answers[0]['answer_data']['is_true'] = 'yes';
		}

		return $answers;
	}

	protected static function _sanitize_single_choice_answers( $answers, $q ) {
		$size = sizeof( $answers );
		if ( $size == 0 ) {
			$answers = $q->get_default_answers();
		}
		$answers     = array_values( $answers );
		$has_checked = false;
		foreach ( $answers as $k => $answer ) {
			if ( $has_checked || empty( $answer['answer_data']['is_true'] ) || $answer['answer_data']['is_true'] != 'yes' ) {
				$answers[ $k ]['answer_data']['is_true'] = 'no';
				continue;
			}
			$has_checked = true;
		}
		if ( ! $has_checked ) {
			$answers[0]['answer_data']['is_true'] = 'yes';
		}

		return $answers;
	}

	public static function admin_assets() {
		///wp_enqueue_script('xxxsdfdsfdsfdsfsdfdsfdsfs');


		//LP_Assets::enqueue_style( 'learn-press-meta-box-question' );
		//LP_Assets::enqueue_script( 'learn-press-meta-box-question', false, array( 'learn-press-admin' ) );
	}

	/**
	 * Get all type of questions
	 *
	 * @return mixed
	 */
	public static function get_types() {
		$types = array(
			'true_or_false' => __( 'True Or False', 'learnpress' ),
			'multi_choice'  => __( 'Multi Choice', 'learnpress' ),
			'single_choice' => __( 'Single Choice', 'learnpress' )
		);

		return apply_filters( 'learn_press_question_types', $types );
	}

	/**
	 * Print js template for each question type
	 */
	public static function admin_template() {
		foreach ( self::get_types() as $type => $name ) {
			$class = self::get_class_name_from_question_type( $type );
			if ( ! class_exists( $class ) ) {
				continue;
			}
			do_action( 'learn-press/admin-before-question-js-template', $type );
			echo sprintf( '<!-- BEGIN %s JS Template -->', $class ) . "\n";
			call_user_func_array( array( $class, 'admin_js_template' ), array( array( 'echo' => true ) ) );
			echo sprintf( '<!-- END %s JS Template -->', $class ) . "\n";
			do_action( 'learn-press/admin-after-question-js-template', $type );
		}
	}


	public static function save( $post_id ) {
		global $post, $pagenow;

		// Ensure that we are editing course in admin side
		if ( ( $pagenow != 'post.php' ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! in_array( get_post_type( $post_id ), array( 'lp_quiz', 'lp_question' ) ) ) {
			return;
		}
		// prevent loop
		remove_action( 'save_post', array( __CLASS__, 'save' ) );
		if ( ! empty( $_POST['learn_press_question'] ) ) {
			foreach ( $_POST['learn_press_question'] as $the_id => $post_data ) {
				( $question = self::get_question( $the_id ) ) && $question->save( $post_data );
			}
		}
		add_action( 'save_post', array( __CLASS__, 'save' ) );
	}

	public static function fetch_question_content( $the_question, $args = false ) {
		$question = self::get_question( $the_question );
		$content  = '';
		if ( $question ) {
			ob_start();
			$question->render( $args );
			$content = ob_get_clean();
		}

		return $content;
	}


	public static function list_question_types( $args = array() ) {
		$args     = wp_parse_args(
			$args,
			array(
				'selected' => '',
				'tag_list' => 'ul',
				'echo'     => true,
				'li_attr'  => ''
			)
		);
		$types    = LP_Question_Factory::get_types();
		$dropdown = array();
		foreach ( $types as $slug => $type_name ) {
			$li_attr    = ! empty( $args['li_attr'] ) ? $args['li_attr'] : '';
			$li_attr    = str_replace( '{{type}}', $slug, $li_attr );
			$dropdown[] = sprintf( '<li data-type="%s" class="%s" %s><a href="">%s</a></li>', $slug, $slug == $args['selected'] ? 'active' : '', $li_attr, $type_name );
		}
		$list = sprintf( '<ul>%s</ul>', join( "\n", $dropdown ) );
		if ( $args['echo'] ) {
			echo $list;
		}

		return $list;
	}

	/**
	 * Init hooks
	 */
	public static function init_hooks() {
	}

	/**
	 * Add new question to questions bank.
	 * Also add to quiz if the id of quiz is passed.
	 *
	 * @param array $args
	 *
	 * @return mixed|int
	 */
	public static function add_question( $args = array() ) {
		global $wpdb;
		print_r( $args );
		$args        = wp_parse_args(
			(array) $args,
			array(
				'quiz_id' => 0,
				'order'   => - 1,
				'status'  => 'publish',
				'type'    => '',
				'title'   => __( 'Untitled question', 'learnpress' )
			)
		);
		$question_id = wp_insert_post(
			array(
				'post_type'   => LP_QUESTION_CPT,
				'post_status' => $args['status'],
				'post_title'  => $args['title']
			)
		);
		if ( $question_id ) {
			if ( ! empty( $args['quiz_id'] ) ) {
				$quiz = learn_press_get_quiz( $args['quiz_id'] );
				$quiz->add_question( $question_id, $args );
			}
			print_r( $args );
			update_post_meta( $question_id, '_lp_type', $args['type'] );
		}

		return $question_id;
	}
}

LP_Question_Factory::init();
