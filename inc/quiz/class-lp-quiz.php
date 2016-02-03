<?php

/**
 * Class LP_Quiz
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
class LP_Quiz {
	/**
	 * The quiz (post) ID.
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * $post Stores post data
	 *
	 * @var $post WP_Post
	 */
	public $post = null;

	/**
	 * WP_Course object
	 *
	 * @var null
	 */
	public $course = null;

	/**
	 * Constructor gets the post object and sets the ID for the loaded course.
	 *
	 * @param int|LP_Quiz|object $quiz Quiz ID, post object, or course object
	 */
	public function __construct( $quiz ) {
		if ( is_numeric( $quiz ) ) {
			$this->id   = absint( $quiz );
			$this->post = get_post( $this->id );
		} elseif ( $quiz instanceof LP_Quiz ) {
			$this->id   = absint( $quiz->id );
			$this->post = $quiz->post;
		} elseif ( isset( $quiz->ID ) ) {
			$this->id   = absint( $quiz->ID );
			$this->post = $quiz;
		}
		$this->course = LP_Course::get_course( $this->_lpr_course );
		$this->_init();
	}

	protected function _init(){
		add_action( 'wp_head', array( $this, 'frontend_assets' ) );
	}

	protected function _get_localize(){
		$localize = array(
			'confirm_finish_quiz'		=> __( 'Are you sure you want to completely finish this quiz?', 'learn_press'),
			'confirm_retake_quiz'		=> __( 'Are you sure you want to retake this quiz?', 'learn_press'),
			'quiz_time_is_over_message' => __( 'The time is over! Your quiz will automate come to finish', 'learn_press' ),
			'quiz_time_is_over_title'   => __( 'Time up!', 'learn_press' )
		);
		return apply_filters( 'learn_press_single_quiz_localize', $localize, $this );
	}

	protected function _settings(){
		$current_question_id = learn_press_get_current_question();// !empty( $_REQUEST['question_id'] ) ? intval( $_REQUEST['question_id'] ) : 0;
		$questions           = learn_press_get_quiz_questions();
		if ( $questions ) {
			$question_ids = array_keys( $questions );
		} else {
			$question_ids = array();
		}
		if ( !$current_question_id || !in_array( $current_question_id, $question_ids ) ) {
			$current_question_id = reset( $question_ids );
		}
		$question = LP_Question_Factory::get_question($current_question_id );
		$user_id  = get_current_user_id();

		$user = learn_press_get_current_user();
		$js   = array(
			'time_format'	=> $this->duration > 300 ? 'h%:m%:s%' : 'm%:s%',
			'total_time'	=> $this->duration,
			'id'             => $this->id,
			'questions'      => $question_ids,
			'question_id'    => absint($current_question_id),
			'status'         => $user->get_quiz_status( $this->id ),
			'time_remaining' => ( $time_remaining = $user->get_quiz_time_remaining( $this->id ) ) !== false && !in_array( $user->get_quiz_status( $this->id ), array( '', 'completed') ) ? $time_remaining : $this->duration,
			'permalink'      => get_the_permalink(),
			'ajaxurl'        => admin_url( 'admin-ajax.php' ),
			'user_id'        => $user->id
		);

		return apply_filters( 'learn_press_single_quiz_params', $js, $this );
	}

	function frontend_assets(){
		if( is_quiz() && get_the_ID() == $this->id ){
			$translate = $this->_get_localize();
			LP_Assets::add_localize( $translate, false, 'single-quiz' );
			LP_Assets::add_param( $this->_settings(), false, 'single-quiz' );
		}
	}

	/**
	 * __isset function.
	 *
	 * @param mixed $key
	 *
	 * @return bool
	 */
	public function __isset( $key ) {
		return metadata_exists( 'post', $this->id, '_' . $key );
	}

	/**
	 * __get function.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( isset( $this->{$key} ) ) {
			return $this->{$key};
		}
		$value = null;
		switch( $key ){
			case 'current_question':
				if( ( $question = learn_press_get_request( 'question' ) ) && is_quiz() ){
					$value = LP_Question_Factory::get_question($question );
				}
				break;
			case 'questions':
				$value = $this->get_questions();
				break;
			default:
				if ( strpos( $key, '_lp_' ) === false ) {
					$key = '_lp_' . $key;
				}
				$value = get_post_meta( $this->id, $key, true );
				if( $key == '_lp_duration' ){
					$value = absint( $value ) * 60;
				}
		}
		if ( !empty( $value ) ) {
			$this->$key = $value;
		}
		return $value;
	}

	/**
	 * Get the quiz's post data.
	 *
	 * @return object
	 */
	public function get_quiz_data() {
		return $this->post;
	}

	function is_require_enrollment() {
		return $this->course && $this->course->is_require_enrollment();
	}

	function get_course() {
		if ( empty( $this->course ) ) {
			global $wpdb;
			$query = $wpdb->prepare("
				SELECT c.*
				FROM {$wpdb->posts} c
				INNER JOIN {$wpdb->learnpress_sections} s on c.ID = s.section_course_id
				INNER JOIN {$wpdb->learnpress_section_items} si on si.section_id = s.section_id AND si.item_id = %d
				", $this->id );
			if( $course_id = $wpdb->get_var( $query ) ){
				$this->course = LP_Course::get_course( $course_id );
			}
		}
		return $this->course;
	}

	function get_user_id() {
		if ( empty( $this->user_id ) ) {
			$user_id = get_current_user_id();
			if ( !$user_id ) {
				if ( !$this->is_require_enrollment() ) {
					$user_id = $_SESSION['learn_press_temp_user_id'];
				}
			}
			$this->user_id = $user_id;
		}
		return $this->user_id;
	}

	/**
	 * @param bool  $the_quiz
	 * @param array $args
	 *
	 * @return bool
	 */
	public static function get_quiz( $the_quiz = false, $args = array() ) {
		//$the_quiz = self::get_quiz_object( $the_quiz );
		if ( !$the_quiz ) {
			return false;
		}

		return new LP_Quiz( $the_quiz, $args );
	}

	function get_quiz_result( $quiz_id = null ) {
		return false;
	}

	function get_questions(){
		if( empty( $this->questions ) ){
			global $wpdb;
			$query = $wpdb->prepare("
				SELECT q.*, qq.params
				FROM {$wpdb->posts} q
				INNER JOIN {$wpdb->learnpress_quiz_questions} qq ON qq.question_id = q.ID
				AND q.post_type = %s
				AND qq.quiz_id = %d
			", LP()->question_post_type, $this->id );

			if( $this->questions = $wpdb->get_results( $query, OBJECT_K ) ){
				foreach( $this->questions as $id => $question ){
					$question->params = maybe_unserialize( $question->params );
					$this->questions[ $id ] = $question	;
				}
			}
		}
		return apply_filters( 'learn_press_quiz_questions', $this->questions );
	}

	function get_buttons(){
		$user = learn_press_get_current_user();
		$buttons = array();
		if ( !$user->has( 'started-quiz', $this->id ) ):
			$buttons['start'] = sprintf( '<button class="button-start-quiz" data-id="%d">%s</button>', $this->id, apply_filters( 'learn_press_start_quiz_button_text', __( "Start Quiz", "learn_press" ) ) );
			$buttons['start'] .= wp_nonce_field( 'start-quiz', 'start-quiz-nonce');
		endif;

		$buttons['finish'] = sprintf( '<button class="button-finish-quiz" data-id="%d">%s</button>', $this->id, apply_filters( 'learn_press_finish_quiz_button_text', __( "Finish Quiz", "learn_press" ) ) );
		$buttons['finish'] .= wp_nonce_field( 'finish-quiz', 'finish-quiz-nonce');


		if ( $remain = $user->can( 'retake-quiz', $this->id ) ):
			$buttons['retake'] = sprintf( '<button class="button-retake-quiz" data-id="%d">%s (+%d)</button>', $this->id, apply_filters( 'learn_press_retake_quiz_button_text', __( 'Retake', 'learn_press' ) ), $remain );
			$buttons['retake'] .= wp_nonce_field( 'retake-quiz', 'retake-quiz-nonce');
		endif;

		return apply_filters( 'learn_press_single_quiz_buttons', $buttons, $this, $user );
	}

	function has( $feature ){
		$args = func_get_args();
		unset( $args[0] );
		$method   = 'has_' . preg_replace( '!-!', '_', $feature );
		$callback = array( $this, $method );
		if ( is_callable( $callback ) ) {
			return call_user_func_array( $callback, $args );
		} else {
			throw new Exception( sprintf( __( 'The function %s doesn\'t exists', 'learn_press' ), $feature ) );
		}
	}

	function has_questions(){
		return $this->get_questions();
	}

	function get_mark( $force = false ){
		if( empty( $this->mark ) || $force ){
			global $wpdb;
			$query = $wpdb->prepare("
				SELECT SUM(pm.meta_value) as mark
				FROM {$wpdb->learnpress_quiz_questions} qq
				INNER JOIN {$wpdb->posts} q ON q.ID = qq.quiz_id
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = qq.question_id AND pm.meta_key = %s
				WHERE q.ID = %d
			", '_lp_mark', $this->id);
			$this->mark = $wpdb->get_var( $query );
		}
		return apply_filters( 'learn_press_quiz_mark', $this->mark, $this );
	}

	function get_question_link( $question_id = null ){
		return apply_filters( 'learn_press_quiz_question_permalink', get_the_permalink( $this->id ) . get_post_field( 'post_name', $question_id ), $question_id, $this );
	}

	function get_question_param($name, $id){
		if( $this->questions ){
			if( !empty( $this->questions[ $id ] ) ){
				return ! empty( $this->questions[ $id ]->params[ $name ] ) ? $this->questions[ $id ]->params[ $name ] : null;
			}
		}
		return false;
	}

	/**
	 * Get the quiz object
	 *
	 * @param  mixed $the_quiz
	 *
	 * @uses   WP_Post
	 * @return WP_Post|bool false on failure
	 */
	private static function get_quiz_object( $the_quiz ) {
		if ( false === $the_quiz ) {
			$the_quiz = $GLOBALS['post'];
		} elseif ( is_numeric( $the_quiz ) ) {
			$the_quiz = get_post( $the_quiz );
		} elseif ( $the_quiz instanceof LP_Quiz ) {
			$the_quiz = get_post( $the_quiz->id );
		} elseif ( !( $the_quiz instanceof WP_Post ) ) {
			$the_quiz = false;
		}

		return apply_filters( 'learn_press_quiz_object', $the_quiz );
	}
}