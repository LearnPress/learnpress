<?php

/**
 * Base class for type of question
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
class LP_Abstract_Question {

	/**
	 * @var null
	 */
	protected $_options = null;

	/**
	 * @var null
	 */
	public $post = null;

	/**
	 * @var null
	 */
	public $id = null;

	/**
	 * @var null
	 */
	public $question_type = null;

	/**
	 * @var bool
	 */
	protected static $_instance = false;

	/**
	 * Construct
	 *
	 * @param mixed
	 * @param array
	 */
	function __construct( $the_question = null, $args = null ) {
		if ( is_numeric( $the_question ) ) {
			$this->id   = absint( $the_question );
			$this->post = get_post( $this->id );
		} elseif ( $the_question instanceof LP_Question ) {
			$this->id   = absint( $the_question->id );
			$this->post = $the_question->post;
		} elseif ( isset( $the_question->ID ) ) {
			$this->id   = absint( $the_question->ID );
			$this->post = $the_question;
		}

		$this->_options = $args;
		$this->_init();
	}

	function __get( $key ) {
		if ( !isset( $this->{$key} ) ) {
			$return = null;
			switch ( $key ) {
				case 'answers':

					$return = $this->_get_answers();
					break;
				default:
					$return = get_post_meta( $this->id, '_lp_' . $key, true );
			}
			$this->{$key} = $return;
		}
		return $this->{$key};
	}

	protected function _init() {
		add_action( 'save_post', array( $this, 'save' ) );
		add_action( 'learn_press_question_answers', array( $this, 'get_default_answers' ) );
	}

	public function save( $post_data = null ) {
		global $wpdb;
		if ( in_array( $this->type, array( 'true_or_false', 'multi_choice', 'single_choice' ) ) ) {
			$all_ids = array();
			if ( !empty( $post_data['answer'] ) ) {
				foreach ( $post_data['answer']['text'] as $index => $text ) {
					$answer_id = !empty( $post_data['answer']['id'][$index] ) ? $post_data['answer']['id'][$index] : 0;
					$data = array(
						'answer_data' => array(
							'text'    => stripslashes( $text ),
							'value'   => $post_data['answer']['value'][$index]
						),
						'ordering'    => $index + 1,
						'question_id'	=> $this->id
					);
					if( $this->type == 'multi_choice' ){
						$data['answer_data']['is_true'] = is_array( $checked = learn_press_get_request( "learn_press_question_{$this->id}" ) ) && in_array( $post_data['answer']['value'][$index], $checked )  ? 'yes' : 'no';
					}else{
						$data['answer_data']['is_true'] = $post_data['answer']['value'][$index] == learn_press_get_request( "learn_press_question_{$this->id}" ) ? 'yes' : 'no';
					}

					$data = apply_filters( 'learn_press_question_answer_data', $data, $this );

					$data['answer_data'] = maybe_serialize( $data['answer_data'] );

					if ( $answer_id ) {
						$wpdb->update(
							$wpdb->learnpress_question_answers,
							$data,
							array( 'question_answer_id' => $answer_id ),
							array( '%s' )
						);
						$all_ids[] = $post_data['answer']['id'][$index];
					} else {
						$wpdb->insert(
							$wpdb->learnpress_question_answers,
							$data,
							array( '%s', '%d' )
						);
						$all_ids[] = $wpdb->insert_id;
					}
				}
			}
			if ( $all_ids ) {
				$query = $wpdb->prepare( "
					DELETE
					FROM {$wpdb->learnpress_question_answers}
					WHERE question_answer_id NOT IN(" . join( ',', $all_ids ) . ")
					AND question_id = %d
				", $this->id );
			} else {
				$query = $wpdb->prepare( "
					DELETE
					FROM {$wpdb->learnpress_question_answers}
					WHERE question_id = %d
				", $this->id );
			}
			$wpdb->query( $query );
		}
		do_action( 'learn_press_update_question_answer', $this );
	}

	protected function _get_option_value( $value = null ) {
		if ( !$value ) {
			$value = uniqid();
		}
		return $value;
	}

	protected function _get_answers() {
		global $wpdb;
		$answers = array();
		$query   = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->learnpress_question_answers}
			WHERE question_id = %d
			ORDER BY ordering ASC
		", $this->id );
		if ( $rows = $wpdb->get_results( $query ) ) {
			foreach ( $rows as $row ) {
				$answers[$row->question_answer_id]       = maybe_unserialize( $row->answer_data );
				$answers[$row->question_answer_id]['id'] = $row->question_answer_id;
			}
		}
		return apply_filters( 'learn_press_question_answers', $answers, $this );
	}

	function get_default_answers( $answers = false ) {
		return $answers;
	}

	function submit_answer( $quiz_id, $answer ) {
		print_r( $_POST );
		die();
	}

	function get_type() {
		return $this->type;
	}

	/**
	 * Prints the header of a question in admin mode
	 * should call this function before in the top of admin_interface in extends class
	 *
	 * @param array $args
	 *
	 * @reutrn void
	 */
	function admin_interface_head( $args = array() ) {
		$view = learn_press_get_admin_view( 'meta-boxes/question/header.php' );
		include $view;
	}

	/**
	 * Prints the header of a question in admin mode
	 * should call this function before in the bottom of admin_interface in extends class
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	function admin_interface_foot( $args = array() ) {
		$view = learn_press_get_admin_view( 'meta-boxes/question/footer.php' );
		include $view;
	}

	/**
	 * Prints the content of a question in admin mode
	 * This function should be overridden from extends class
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	function admin_interface( $args = array() ) {
		printf( __( 'Function %s should override from its child', 'learn_press' ), __FUNCTION__ );
	}

	/**
	 * Prints the question in frontend user
	 *
	 * @param unknown
	 *
	 * @return void
	 */
	function render() {
		printf( __( 'Function %s should override from its child', 'learn_press' ), __FUNCTION__ );
	}

	function get_name() {
		return
			isset( $this->options['name'] ) ? $this->options['name'] : ucfirst( preg_replace_callback( '!_([a-z])!', create_function( '$matches', 'return " " . strtoupper($matches[1]);' ), $this->get_type() ) );
	}

	/**
	 * Sets the value for a variable of this class
	 *
	 * @param   $key      string  The name of a variable of this class
	 * @param   $value    any     The value to set
	 *
	 * @return  void
	 */
	function set( $key, $value ) {
		$this->$key = $value;
	}

	/**
	 * Gets the value of a variable of this class with multiple level of an object or array
	 * example: $obj->get('a.b') -> like this :
	 *          - $obj->a->b
	 *          - or $obj->a['b']
	 *
	 * @param   null $key     string  Single or multiple level such as a.b.c
	 * @param   null $default mixed   Return a default value if the key does not exists or is empty
	 * @param   null $func    string  The function to apply the result before return
	 *
	 * @return  mixed|null
	 */
	function get( $key = null, $default = null, $func = null ) {
		$val = $this->_get( $this, $key, $default );
		return is_callable( $func ) ? call_user_func_array( $func, array( $val ) ) : $val;
	}


	protected function _get( $prop, $key, $default = null, $type = null ) {
		$return = $default;

		if ( $key === false || $key == null ) {
			return $return;
		}
		$deep = explode( '.', $key );

		if ( is_array( $prop ) ) {
			if ( isset( $prop[$deep[0]] ) ) {
				$return = $prop[$deep[0]];
				if ( count( $deep ) > 1 ) {
					unset( $deep[0] );
					$return = $this->_get( $return, implode( '.', $deep ), $default, $type );
				}
			}
		} elseif ( is_object( $prop ) ) {
			if ( isset( $prop->{$deep[0]} ) ) {
				$return = $prop->{$deep[0]};
				if ( count( $deep ) > 1 ) {
					unset( $deep[0] );
					$return = $this->_get( $return, implode( '.', $deep ), $default, $type );
				}
			}
		}


		if ( $type == 'object' ) settype( $return, 'object' );
		elseif ( $type == 'array' ) settype( $return, 'array' );

		// return;
		return $return;
	}

	/**
	 * Save question data on POST action
	 */
	function save_post_action() {
	}

	/**
	 * Store question data
	 */
	function store() {
		$post_id = $this->get( 'ID' );
		$is_new  = false;
		if ( $post_id ) {
			$post_id = wp_update_post(
				array(
					'ID'          => $post_id,
					'post_title'  => $this->get( 'post_title' ),
					'post_type'   => LP()->question_post_type,
					'post_status' => 'publish'

				)
			);
		} else {
			$post_id = wp_insert_post(
				array(
					'post_title'  => $this->get( 'post_title' ),
					'post_type'   => LP()->question_post_type,
					'post_status' => 'publish'
				)
			);
			$is_new  = true;
		}
		if ( $post_id ) {
			$options         = $this->get( 'options' );
			$options['type'] = $this->get_type();

			$this->set( 'options', $options );

			update_post_meta( $post_id, '_lpr_question', $this->get( 'options' ) );

			// update default mark
			if ( $is_new ) update_post_meta( $post_id, '_lpr_question_mark', 1 );

			$this->ID = $post_id;
		}
		return $post_id;
	}

	function get_icon(){
		return '<img src="' . apply_filters( 'learn_press_question_icon', LP()->plugin_url( 'assets/images/question.png' ), $this ) . '">';
	}

	function get_params(){

	}

	function is_selected_option( $answer, $answered = false ){
		if( is_array( $answered ) ){
			$is_selected = isset( $answer['value'] ) && in_array( $answer['value'], $answered );
		}else{
			$is_selected = isset( $answer['value'] ) && ( $answer['value'] == $answered . '' );
		}
		return apply_filters( 'learn_press_is_selected_option', $is_selected, $answer, $answered, $this );
	}

	function save_user_answer( $answer, $quiz_id, $user_id = null ){
		if( $user_id ){
			$user = LP_User::get_user( $user_id );
		}else{
			$user = learn_press_get_current_user();
		}

		if( $progress = $user->get_quiz_progress( $quiz_id ) ){
			if( !isset( $progress->question_answers ) ){
				$question_answers = array();
			}else{
				$question_answers = $progress->question_answers;
			}
			$question_answers[ $this->id ] = $answer;

			$question_answers = apply_filters( 'learn_press_update_user_question_answers', $question_answers, $progress->history_id, $user_id, $this, $quiz_id );

			learn_press_update_user_quiz_meta( $progress->history_id, 'question_answers', $question_answers );
		}
		//do_action( 'learn_press_update_user_answer', $progress, $user_id, $this, $quiz_id );
	}

	function check( $args = null ) {
		$return = array(
			'correct' => false,
			'mark'    => 0
		);
		return $return;
	}
}

function lpr_get_question_types() {
	_deprecated_function( __FUNCTION__, '1.0', 'learn_press_question_types' );
	return learn_press_question_types();
}

function learn_press_submit_answer() {

	_deprecated_function( 'learn_press_submit_answer', '0.9.15', false );

	$quiz_id         = !empty( $_REQUEST['quiz_id'] ) ? intval( $_REQUEST['quiz_id'] ) : 0;
	$question_id     = !empty( $_REQUEST['question_id'] ) ? intval( $_REQUEST['question_id'] ) : 0;
	$next_id         = !empty( $_REQUEST['next_id'] ) ? intval( $_REQUEST['next_id'] ) : learn_press_get_next_question( $quiz_id, $question_id );
	$question_answer = isset( $_REQUEST['question_answer'] ) ? $_REQUEST['question_answer'] : null;
	$finish          = isset( $_REQUEST['finish'] ) ? $_REQUEST['finish'] : null;

	$user_id = get_current_user_id();
	$json    = array();

	$ques = lpr_get_question( $question_id );
	if ( $ques ) {
		$ques->submit_answer( $quiz_id, $question_answer );
	}
	ob_start();
	if ( $next_id ) {
		do_action( 'learn_press_submit_answer', $question_answer, $question_id, $quiz_id, $user_id, false );
		learn_press_get_template( 'quiz/form-question.php', array( 'question_id' => $next_id, 'course_id' => learn_press_get_course_by_quiz( $quiz_id ) ) );
	} else {
		$question_ids             = learn_press_get_user_quiz_questions( $quiz_id, $user_id );
		$quiz_completed           = get_user_meta( $user_id, '_lpr_quiz_completed', true );
		$quiz_completed[$quiz_id] = current_time( 'timestamp' );
		update_user_meta( $user_id, '_lpr_quiz_completed', $quiz_completed );
		$course_id = learn_press_get_course_by_quiz( $quiz_id );
		if ( !learn_press_user_has_finished_course( $course_id ) ) {
			if ( learn_press_user_has_completed_all_parts( $course_id, $user_id ) ) {
				learn_press_finish_course( $course_id, $user_id );
			}
		}
		learn_press_get_template( 'quiz/result.php' );
		$json['quiz_completed'] = true;
		do_action( 'learn_press_submit_answer', $question_answer, $question_id, $quiz_id, $user_id, true );
	}
	$output = ob_get_clean();
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$json['html']     = $output;
		$json['redirect'] = apply_filters( 'learn_press_submit_answer_redirect_url', get_the_permalink( $quiz_id ), $question_answer, $question_id, $quiz_id, $user_id );
		learn_press_send_json( $json );
	}
}

add_action( 'wp_ajax_learn_press_submit_answer', 'learn_press_submit_answer' );
add_action( 'wp_ajax_nopriv_learn_press_submit_answer', 'learn_press_submit_answer' );

add_action( 'learn_press_frontend_action_submit_answer', 'learn_press_submit_answer' );

function learn_press_load_question() {
	$question_id = !empty( $_REQUEST['question_id'] ) ? intval( $_REQUEST['question_id'] ) : 0;
	$quiz_id     = !empty( $_REQUEST['quiz_id'] ) ? intval( $_REQUEST['quiz_id'] ) : 0;
	/*$ques = lpr_get_question( $question_id );
	if( $ques ){
		$quiz_answers = learn_press_get_question_answers(null, $quiz_id );
		$ques->render( array(
			'answer' => isset( $quiz_answers[$question_id] ) ? $quiz_answers[$question_id] : null
		));
	}*/
	learn_press_get_template( 'quiz/form-question.php', array( 'question_id' => $question_id, 'course_id' => learn_press_get_course_by_quiz( $quiz_id ) ) );

	die();
}

add_action( 'wp_ajax_learn_press_load_question', 'learn_press_load_question' );
add_action( 'wp_ajax_nopriv_learn_press_load_question', 'learn_press_load_question' );

function learn_press_show_answer() {
	$quiz_id         = !empty( $_REQUEST['quiz_id'] ) ? intval( $_REQUEST['quiz_id'] ) : 0;
	$question_id     = !empty( $_REQUEST['question_id'] ) ? intval( $_REQUEST['question_id'] ) : 0;
	$question_answer = isset( $_REQUEST['question_answer'] ) ? $_REQUEST['question_answer'] : null;

	$user_id = get_current_user_id();
	$json    = array();
	ob_start();
	$ques = lpr_get_question( $question_id );
	if ( $ques ) {
		$ques->submit_answer( $quiz_id, $question_answer );
	}
	global $quiz;
	$user_id      = get_current_user_id();
	$quiz_answers = learn_press_get_question_answers( null, $quiz_id );
	$answer       = isset( $quiz_answers[$question_id] ) ? $quiz_answers[$question_id] : array();
	switch ( $ques->get_type() ) {
		case 'multi_choice':
			?>
			<ul class="lpr-question-hint">
				<?php if ( $answers = $ques->get( 'options.answer' ) ) foreach ( $answers as $k => $ans ):
					$classes = array();
					if ( in_array( $k, $answer ) ) {
						if ( $ques->get( "options.answer.{$k}.is_true" ) ) {
							$classes[] = "correct";
						} else {
							$classes[] = "wrong";
						}
					} else if ( $ques->get( "options.answer.{$k}.is_true" ) ) {
						$classes[] = "correct";
					}
					?>
					<li <?php echo $classes ? 'class="' . join( " ", $classes ) . '"' : ''; ?>>
						<label>
							<input type="checkbox"
								   disabled="disabled" <?php checked( in_array( $k, $answer ) ? 1 : 0 ); ?> />
							<?php echo $ques->get( "options.answer.{$k}.text" ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
			break;
		case 'single_choice':
			?>
			<ul class="lpr-question-hint">
				<?php if ( $answers = $ques->get( 'options.answer' ) ) foreach ( $answers as $k => $ans ):
					$classes = array();
					if ( $k == $answer ) {
						if ( $ques->get( "options.answer.{$k}.is_true" ) ) {
							$classes[] = "correct";
						} else {
							$classes[] = "wrong";
						}
					} else if ( $ques->get( "options.answer.{$k}.is_true" ) ) {
						$classes[] = "correct";
					}
					?>
					<li <?php echo $classes ? 'class="' . join( " ", $classes ) . '"' : ''; ?>>
						<label>
							<input type="radio" disabled="disabled" <?php checked( $k == $answer ? 1 : 0 ); ?> />
							<?php echo $ques->get( "options.answer.{$k}.text" ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
			break;
		case 'true_or_false':
			?>
			<ul class="lpr-question-hint">
				<?php
				for ( $k = 0; $k < 2; $k ++ ) {
					$classes = array();
					if ( $k == $answer ) {
						if ( $ques->get( "options.answer.{$k}.is_true" ) ) {
							$classes[] = "correct";
						} else {
							$classes[] = "wrong";
						}
					} else if ( $ques->get( "options.answer.{$k}.is_true" ) ) {
						$classes[] = "correct";
					}
					?>
					<li <?php echo $classes ? 'class="' . join( " ", $classes ) . '"' : ''; ?>>
						<label>
							<input type="radio" disabled="disabled" <?php checked( $answer == $k ? 1 : 0 ); ?> />
							<?php echo $ques->get( 'options.answer.' . $k . '.text' ); ?>
						</label>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
			break;
		default:
			do_action( 'learn_press_question_suggestion_' . $ques->get_type(), $ques, $answer );
	}
	?>
	<h4><?php _e( "Answer explanation", 'learn_press' ) ?></h4>
	<p><?php echo $ques->get( 'options.explaination' ) ?></p>
	<?php
	$json['html'] = ob_get_clean();

	wp_send_json( $json );

	die();
}

add_action( 'wp_ajax_learn_press_show_answer', 'learn_press_show_answer' );
add_action( 'wp_ajax_nopriv_learn_press_show_answer', 'learn_press_show_answer' );

/**
 * Class LP_Question
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
class LP_Question extends LP_Abstract_Question {
}