<?php
/**
 * Base class for types of question
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Question' ) ) {

	/**
	 * Class LP_Question
	 *
	 * @extend LP_Course_Item
	 */
	class LP_Question extends LP_Course_Item {

		/**
		 * @var null
		 */
		protected $_options = null;

		/**
		 * @var string
		 */
		protected $_content = '';

		/**
		 * Type of this question.
		 *
		 * @var string
		 */
		protected $_question_type = 'single_choice';

		/**
		 * @var string
		 */
		protected $_item_type = LP_QUESTION_CPT;

		/**
		 * @var array
		 */
		protected static $_types = array();

		/**
		 * Any features this question support.
		 *
		 * @var array
		 */
		protected $_supports = array();

		/**
		 * support answer options
		 *
		 * @var bool
		 */
		protected $_answer_options = true;

		/**
		 * @var int
		 */
		protected static $_loaded = 0;

		/**
		 * @var array
		 */
		protected $_data = array(
			'mark'                 => 0,
			'answer_options'       => array(),
			'show_correct_answers' => '',
			'disable_answers'      => '',
			'answered'             => '',
			'explanation'          => '',
			'hint'                 => ''
		);

		/**
		 * Construct
		 *
		 * @param mixed
		 * @param array
		 *
		 * @throws Exception
		 */
		public function __construct( $the_question = null, $args = null ) {

			parent::__construct( $the_question, $args );

			$this->_curd = new LP_Question_CURD();
			if ( is_numeric( $the_question ) && $the_question > 0 ) {
				$this->set_id( $the_question );
			} elseif ( $the_question instanceof self ) {
				$this->set_id( absint( $the_question->get_id() ) );
			} elseif ( ! empty( $the_question->ID ) ) {
				$this->set_id( absint( $the_question->ID ) );
			}

			if ( $this->_answer_options ) {
				$this->add_support( 'answer_options' );
				$this->add_support( 'auto_calculate_point' );
				if ( $this->get_type() !== 'true_or_false' ) {
					$this->add_support( 'add_answer_option' );
				}
			}

			$this->set_data( 'answered', false );

			if ( $this->get_id() > 0 ) {
				$this->load();
			}

			$this->_options = $args;
			$this->_init();
			self::$_loaded ++;
			if ( self::$_loaded == 1 ) {
				add_filter( 'debug_data', array( __CLASS__, 'log' ) );
			}
		}

		/**
		 * Debug log.
		 *
		 * @since 3.0.0
		 *
		 * @param $data
		 *
		 * @return array
		 */
		public static function log( $data ) {
			$data[] = __CLASS__ . '( ' . self::$_loaded . ' )';

			return $data;
		}

		/**
		 * Load data for question
		 *
		 * @throws Exception
		 */
		public function load() {
			$this->_curd->load( $this );
		}

		/**
		 * Get default question meta.
		 *
		 * @return mixed
		 */
		public static function get_default_meta() {
			$meta = array(
				'mark'        => 1,
				'explanation' => null,
				'hint'        => null
			);

			return apply_filters( 'learn-press/question/default-meta', $meta );
		}

		/**
		 * Save question data.
		 *
		 * @since 3.0.0
		 *
		 * @return int|object|WP_Error
		 */
		public function save() {

			if ( $this->get_id() ) {
				$return = $this->_curd->update( $this );
			} else {
				$return = $this->_curd->create( $this );
			}

			return $return;
		}

		/**
		 * @return array|mixed
		 */
		public function get_mark() {
			return $this->get_data( 'mark' );
		}

		/**
		 * @param $mark
		 */
		public function set_mark( $mark ) {
			$this->_set_data( 'mark', abs( $mark ) );
		}

		/**
		 * @param string $yes_or_no
		 *
		 * @return array|mixed
		 */
		public function show_correct_answers( $yes_or_no = '' ) {
			if ( in_array( $yes_or_no, array( 'yes', 'no' ) ) ) {
				$this->_set_data( 'show_correct_answers', $yes_or_no );
			}

			return $this->get_data( 'show_correct_answers' );
		}

		/**
		 * @param string $yes_or_no
		 *
		 * @return array|mixed
		 */
		public function disable_answers( $yes_or_no = '' ) {
			if ( in_array( $yes_or_no, array( 'yes', 'no' ) ) ) {
				$this->_set_data( 'disable_answers', $yes_or_no );
			}

			return $this->get_data( 'disable_answers' );
		}

		/**
		 * Set answer for this question.
		 *
		 * @param mixed $answered
		 */
		public function set_answered( $answered ) {
			$this->set_data( 'answered', $answered );
		}

		/**
		 * Get answer for this question if set.
		 *
		 * @return array|mixed
		 */
		public function get_answered() {
			return $this->get_data( 'answered' );
		}

		/**
		 * Get answer options of the question
		 *
		 * @return mixed
		 */
		public function get_answer_options() {
			return apply_filters( 'learn-press/question/answer-options', $this->get_data( 'answer_options' ), $this->get_id() );
		}

		/**
		 * @param string $explanation
		 */
		public function set_explanation( $explanation = '' ) {
			$this->_set_data( 'explanation', $explanation );
		}

		/**
		 * @return mixed
		 */

		/**
		 * @param string $hint
		 */
		public function set_hint( $hint = '' ) {
			$this->_set_data( 'hint', $hint );
		}

		/**
		 * @return mixed
		 */
		public function get_hint() {
			return apply_filters( 'learn-press/question/hint', do_shortcode( $this->get_data( 'hint' ) ), $this->get_id() );
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
		 * Store question and it's related data into database.
		 *
		 * @return mixed
		 */
		public function store() {
			global $wpdb;
			$id        = absint( $this->get_id() );
			$is_update = $id > 0;
			$post_data = array(
				'post_title' => $this->get_data( 'title' ),
				'post_type'  => LP_QUESTION_CPT,
				'ID'         => $id
			);
			if ( $is_update ) {
				$updated = wp_update_post( $post_data, true );
			} else {
				$updated = wp_insert_post( $post_data, true );
			}

			if ( ! is_numeric( $updated ) ) {
				return false;
			}

			// Does this question support answer options?
			if ( ! $this->is_support( 'answer_options' ) ) {
				return $updated;
			}

			$this->empty_answers();
			if ( $answer_options = $this->get_data( 'answer_options' ) ) {
				$question_order = 1;
				$query          = "INSERT INTO {$wpdb->prefix}learnpress_question_answers(`question_id`, `answer_order`) VALUES";
				foreach ( $answer_options as $answer_option ) {
					if ( empty( $answer_option['text'] ) ) {
						if ( apply_filters( 'learn-press/question/ignore-insert-empty-answer-option', true, $answer_option, $id ) ) {
							continue;
						}
					}
					$qry = $query . $wpdb->prepare( "(%d, %d)", $id, $question_order ++ );
					do_action( 'learn-press/question/insert-answer-option', $id, $answer_option );
					if ( $wpdb->query( $qry ) ) {
						$inserted_id = $wpdb->insert_id;
						learn_press_update_question_answer_meta( $inserted_id, 'text', $answer_option['text'] );
						learn_press_update_question_answer_meta( $inserted_id, 'value', $answer_option['value'] );
						if ( ! empty( $answer_option['is_true'] ) && ! learn_press_is_negative_value( $answer_option['is_true'] ) ) {
							learn_press_update_question_answer_meta( $inserted_id, 'checked', 'yes' );
						}
						do_action( 'learn-press/question/inserted-answer-option', $inserted_id, $id, $answer_option );
					}
				}
			}

			return $updated;
		}

		/**
		 * Remove all answers to prepare for inserting new
		 */
		public function empty_answers() {
			global $wpdb;
			$id         = absint( $this->get_id() );
			$table_meta = $wpdb->learnpress_question_answermeta;
			$table_main = $wpdb->learnpress_question_answers;
			$query      = $wpdb->prepare( "
				DELETE FROM t1, t2
				USING {$table_main} AS t1 INNER JOIN {$table_meta} AS t2 ON t1.question_answer_id = t2.learnpress_question_answer_id
				WHERE t1.question_id = %d
			", $id );

			// deprecated
			do_action( 'learn_press_before_delete_question_answers', $id );

			do_action( 'learn-press/question/delete-answers', $id );
			if ( $wpdb->query( $query ) ) {
				do_action( 'learn-press/question/deleted-answers', $id );
			}
			// deprecated
			do_action( 'learn_press_delete_question_answers', $id );
		}

		/**
		 * Output the meta boxes of question.
		 * Do some dirty-works to show the meta box.
		 */
		public function output_meta_box_settings() {
			global $wp_meta_boxes, $post;

			if ( ! class_exists( 'LP_Quiz_Question_Meta_Box' ) ) {
				include_once LP_PLUGIN_PATH . 'inc/admin/meta-box/class-lp-quiz-question-meta-box.php';
			}

			// Fake screen for question meta box
			$screen = WP_Screen::get( LP_QUESTION_CPT );

			// Fake global $post
			$origin_post = $post;
			$post        = get_post( $this->get_id() );
			setup_postdata( $post );
			$origin_meta_boxes = null;
			// There is no meta boxes
			if ( ! empty( $wp_meta_boxes[ LP_QUESTION_CPT ] ) ) {

				// Track the origin meta-boxes
				$origin_meta_boxes = $wp_meta_boxes[ LP_QUESTION_CPT ];

				// Unset origin meta box so new meta box with the same id is effected.
				unset( $wp_meta_boxes[ LP_QUESTION_CPT ] );
			}

			add_filter( 'rwmb_field_meta', array( $this, '_filter_meta_box_meta' ), 10.01, 10 );

			$meta_box_settings            = LP_Question_Post_Type::settings_meta_box();
			$meta_box_settings['post_id'] = $this->get_id();// Store the ID of current question for some purpose.

			// Add new field to beginning of list for displaying content
			array_unshift( $meta_box_settings['fields'], array(
					'id'      => 'question-content',
					'type'    => 'textarea', //'wysiwyg',
					'name'    => __( 'Question Content', 'learnpress' ),
					'default' => '',
					'context' => 'quiz-list-questions'
				)
			);

			// Create new meta box
			$box = new LP_Quiz_Question_Meta_Box( $meta_box_settings );

			// Add this manually because the hook is already done!!!
			add_meta_box( $box->id, $box->title, array(
				$box,
				'show'
			), LP_QUESTION_CPT, $box->context, $box->priority );

			//remove_filter( 'default_hidden_meta_boxes', '');
			// Show meta box
			do_meta_boxes( $screen, 'normal', $post );

			// ==> Okay, restore all data

			// Restore origin $post
			$post = $origin_post;
			setup_postdata( $post );

			if ( $origin_meta_boxes ) {
				// Restore origin meta boxes
				$wp_meta_boxes[ LP_QUESTION_CPT ] = $origin_meta_boxes;
			}
		}

		/**
		 * @param $meta
		 * @param $field
		 * @param $is_saved
		 *
		 * @return string
		 */
		public function _filter_meta_box_meta( $meta, $field, $is_saved ) {
			if ( preg_match( '~\[question-content\]~', $field['id'] ) && $field['context'] == 'quiz-list-questions' ) {
				$post = get_post( $this->get_id() );
				$meta = $post->post_content;
			}

			return $meta;
		}

		/**
		 * Set new type of question.
		 * Update _lp_type meta to new type.
		 *
		 * @param string $type
		 *
		 * @return bool
		 */
		public function set_type( $type = '' ) {

			if ( ! $type ) {
				return false;
			}

			if ( ! learn_press_is_support_question_type( $type ) ) {
				return false;
			}

			// Change to new type and update meta value
			$this->_question_type = $type;
			update_post_meta( $this->get_id(), '_lp_type', $type );

			return true;
		}

		/**
		 * Update ordering of question answers
		 *
		 * @param array $orders List of answers
		 */
		public function update_answer_orders( $orders ) {
			global $wpdb;
			$query = $wpdb->prepare( "
			    SELECT qa.question_answer_id, qam2.meta_value as `name`, qam.meta_value as `value`
	            FROM {$wpdb->learnpress_question_answers} qa
	            INNER JOIN {$wpdb->learnpress_question_answermeta} qam ON qa.question_answer_id = qam.learnpress_question_answer_id AND qam.meta_key = %s
	            INNER JOIN {$wpdb->learnpress_question_answermeta} qam2 ON qa.question_answer_id = qam2.learnpress_question_answer_id AND qam2.meta_key = %s
	            WHERE qa.question_id = %d
	            ORDER BY answer_order
			", 'value', 'text', $this->get_id() );
			if ( $answers = $wpdb->get_results( $query ) ) {
				$query = "
                UPDATE {$wpdb->learnpress_question_answers} 
                SET answer_order = CASE
            ";
				for ( $order = 0, $n = sizeof( $orders ); $order < $n; $order ++ ) {
					$found_answer = false;
					foreach ( $answers as $answer ) {
						if ( $answer->value == $orders[ $order ]['value'] && $answer->name == $orders[ $order ]['text'] ) {
							$found_answer = $answer;
							break;
						}
					}
					if ( $found_answer === false ) {
						continue;
					}
					$query .= $wpdb->prepare( "WHEN question_answer_id = %d THEN %d", $found_answer->question_answer_id, $order + 1 ) . "\n";
				}
				$query .= sprintf( "ELSE answer_order END WHERE question_id = %d", $this->get_id() );
				$wpdb->query( $query );
			}
		}

		/**
		 * Return type of question.
		 *
		 * @return string
		 */
		public function get_type() {
			return $this->_question_type;
		}

		/**
		 * Return type of question in 'readable text'.
		 *
		 * @return string
		 */
		public function get_type_label() {
			return ucwords( str_replace( '_', ' ', $this->get_type() ) );
		}

		protected function _init() {
			add_filter( 'learn_press_question_answers', array( $this, '_get_default_answers' ), 10, 2 );
		}


		/**
		 *
		 * @param mixed       $answers
		 * @param LP_Question $q
		 *
		 * @return array|bool
		 */
		public function _get_default_answers( $answers = false, $q = null ) {
			if ( ! $answers && ( $q && $q->get_id() == $this->get_id() ) ) {
				$answers = $this->get_default_answers();
			}

			return $answers;
		}

		/**
		 * Get default question answer.
		 *
		 * @return array
		 */
		public static function get_default_answer() {
			$answer = array(
				'question_answer_id' => - 1,
				'text'               => __( 'New Option', 'learnpress' ),
				'is_true'            => false,
				'value'              => learn_press_uniqid()
			);

			return $answer;
		}

		/**
		 * Get default question list answers.
		 *
		 * @return array|bool
		 */
		public function get_default_answers() {
			$answers = array(
				array(
					'is_true' => 'yes',
					'value'   => learn_press_uniqid(),
					'text'    => __( 'First option', 'learnpress' )
				),
				array(
					'is_true' => 'no',
					'value'   => learn_press_uniqid(),
					'text'    => __( 'Second option', 'learnpress' )
				),
				array(
					'is_true' => 'no',
					'value'   => learn_press_uniqid(),
					'text'    => __( 'Third option', 'learnpress' )
				)
			);

			return $answers;
		}


		/**
		 * @param string $field
		 * @param string $exclude
		 *
		 * @return LP_Question_Answers
		 */
		public function get_answers( $field = null, $exclude = null ) {
			$answers = array();
			if ( false === ( $data_answers = wp_cache_get( 'answer-options-' . $this->get_id(), 'lp-questions' ) ) ) {
				$data_answers = $this->get_default_answers();
			};

			if ( $data_answers ) {
				$answers = new LP_Question_Answers( $this, $data_answers );
			}

			// @deprecated
			$answers = apply_filters( 'learn_press_question_answers', $answers, $this );

			return apply_filters( 'learn-press/questions/answers', $answers, $this->get_id() );
		}

		/**
		 * Prints the question in frontend user.
		 *
		 * @param mixed $args
		 *
		 * @return void
		 */
		public function render( $args = false ) {
			$this->set_answered( $args );

			$type = '';
			switch ( $this->get_type() ) {
				case 'true_or_false':
				case 'single_choice':
					$type = 'single-choice';
					break;
				case 'multi_choice':
					$type = 'multi-choice';
					break;
			}

			learn_press_get_template( 'content-question/' . $type . '/answer-options.php', array( 'question' => $this ) );
		}

		public function setup_data( $quiz_id, $course_id = 0, $user_id = 0 ) {

			$quiz   = learn_press_get_quiz( $quiz_id );
			$course = $course_id ? learn_press_get_course( $course_id ) : LP_Global::course();

			if ( $user_id ) {
				$user = learn_press_get_user( $user_id );
			} else {
				$user = learn_press_get_current_user();
			}

			$show_correct = false;
			if ( $user && $quiz && $course ) {
				if ( $user_quiz = $user->get_quiz_data( $quiz->get_id(), $course->get_id() ) ) {
					$has_checked  = $user->has_checked_answer( $this->get_id(), $quiz->get_id(), $course->get_id() );
					$show_correct = $user_quiz->is_completed() && ( $has_checked || $quiz->get_show_result() ) ? 'yes' : false;
					$answered     = $user_quiz->get_question_answer( $this->get_id() );
					$this->set_answered( $answered );
				}
			}

			$this->show_correct_answers( $show_correct );
		}

		/**
		 * Return HTML of question content.
		 *
		 * @param array $args
		 *
		 * @return string
		 */
		public function get_html( $args = array() ) {
			ob_start();
			$this->render( $args );

			return ob_get_clean();
		}


		/**
		 * Get question name.
		 *
		 * @return string
		 */
		public function get_name() {
			return isset( $this->_options['name'] ) ? $this->_options['name'] : ucfirst( preg_replace_callback( '!_([a-z])!', array(
				$this,
				'sanitize_name_callback'
			), $this->get_type() ) );
		}

		/**
		 * @param $matches
		 *
		 * @return string
		 */
		public function sanitize_name_callback( $matches ) {
			return strtoupper( $matches[1] );
		}

		/**
		 * Sets the value for a variable of this class
		 *
		 * @param   $key      string  The name of a variable of this class
		 * @param   $value    mixed     The value to set
		 *
		 * @return  void
		 */
		public function set( $key, $value ) {
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
		public function get( $key = null, $default = null, $func = null ) {
			$val = $this->_get( $this, $key, $default );

			return is_callable( $func ) ? call_user_func_array( $func, array( $val ) ) : $val;
		}


		/**
		 * Magic function to get question data.
		 *
		 * @param      $prop
		 * @param      $key
		 * @param null $default
		 * @param null $type
		 *
		 * @return mixed|null
		 */
		protected function _get( $prop, $key, $default = null, $type = null ) {
			$return = $default;

			if ( $key === false || $key == null ) {
				return $return;
			}
			$deep = explode( '.', $key );

			if ( is_array( $prop ) ) {
				if ( isset( $prop[ $deep[0] ] ) ) {
					$return = $prop[ $deep[0] ];
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


			if ( $type == 'object' ) {
				settype( $return, 'object' );
			} elseif ( $type == 'array' ) {
				settype( $return, 'array' );
			}

			return $return;
		}

		/**
		 * Find value in answer's option and compare with value answered by user.
		 *
		 * @param LP_Question_Answer_Option $answer
		 * @param mixed                     $answered
		 *
		 * @return bool
		 */
		public function is_selected_option( $answer, $answered = false ) {

			if ( is_array( $answered ) ) {
				$is_selected = in_array( $answer['value'], $answered );
			} else {
				$is_selected = ( $answer['value'] . '' === $answered . '' );
			}

			return apply_filters( 'learn-press/question/is-selected-option', $is_selected, $answer, $answered, $this->get_id() );
		}

		/**
		 * Save user question answer.
		 *
		 * @param      $answer
		 * @param      $quiz_id
		 * @param null $user_id
		 */
		public function save_user_answer( $answer, $quiz_id, $user_id = null ) {
			if ( $user_id ) {
				$user = LP_User_Factory::get_user( $user_id );
			} else {
				$user = learn_press_get_current_user();
			}

			if ( $progress = $user->get_quiz_progress( $quiz_id ) ) {
				if ( ! isset( $progress->question_answers ) ) {
					$question_answers = array();
				} else {
					$question_answers = $progress->question_answers;
				}
				$question_answers[ $this->get_id() ] = $answer;

				$question_answers = apply_filters( 'learn_press_update_user_question_answers', $question_answers, $progress->history_id, $user_id, $this, $quiz_id );

				//learn_press_update_user_quiz_meta( $progress->history_id, 'question_answers', $question_answers );
			}
		}

		/**
		 * Allow check question answer, default disable for True or False and Single choice, override by Multiple choice question.
		 *
		 * @return bool
		 */
		public function can_check_answer() {
			return false;
		}

		/**
		 * Check user answer, override by question type class.
		 *
		 * @param null $args | question answered
		 *
		 * @return array
		 */
		public function check( $args = null ) {
			$return = array(
				'correct' => false,
				'mark'    => 0
			);

			return $return;
		}

		/**
		 * Get answer at position
		 *
		 * @since 3.0.0
		 *
		 * @param int $at
		 *
		 * @return LP_Question_Answer_Option|mixed
		 */
		public function get_answer_at( $at ) {
			return $this->get_answers()->get_answer_at( $at );
		}

		/**
		 * Get user answered data.
		 *
		 * @param $args
		 *
		 * @return null
		 */
		public function get_user_answered( $args ) {
			$args     = wp_parse_args(
				$args,
				array(
					'history_id' => 0,
					'quiz_id'    => 0,
					'course_id'  => 0,
					'force'      => false
				)
			);
			$answered = null;
			if ( $args['history_id'] ) {
				$user_meta = learn_press_get_user_item_meta( $args['history_id'], 'question_answers', true );
				if ( $user_meta && array_key_exists( $this->get_id(), $user_meta ) ) {
					$answered = $user_meta[ $this->get_id() ];
				}
			} elseif ( $args['quiz_id'] && $args['course_id'] ) {
				$user    = learn_press_get_current_user();
				$history = $user->get_quiz_results( $args['quiz_id'], $args['course_id'] );

				if ( $history ) {
					$user_meta = learn_press_get_user_item_meta( $history->history_id, 'question_answers', true );

					if ( $user_meta && array_key_exists( $this->get_id(), $user_meta ) ) {
						$answered = $user_meta[ $this->get_id() ];
					}
				}
			}

			return $answered;
		}
		public function get_explanation() {
			$checkResponse = $this->check($this->get_answered());
			if (!$checkResponse['correct']){
				return apply_filters( 'learn-press/question/explanation', do_shortcode( $this->get_data( 'explanation' ) ), $this->get_id() );
			} else {
				return apply_filters( 'learn-press/question/explanation_correct', get_post_meta($this->get_id(), '_lp_explanation_correct', true), $this->get_id() );
			}
		}
		/**
		 * Get question.
		 *
		 * @param bool  $the_question
		 * @param array $args
		 *
		 * @return LP_Question|bool
		 */
		public static function get_question( $the_question = false, $args = array() ) {
			// question object
			$the_question = self::get_question_object( $the_question );
			if ( ! $the_question ) {
				return false;
			}

			if ( ! empty( $args['force'] ) ) {
				$force = ! ! $args['force'];
				unset( $args['force'] );
			} else {
				$force = false;
			}

			$key_args = wp_parse_args( $args, array( 'id' => $the_question->ID, 'type' => $the_question->post_type ) );

			$key = LP_Helper::array_to_md5( $key_args );

			if ( $force ) {
				LP_Global::$questions[ $key ] = false;
			}

			if ( empty( LP_Global::$questions[ $key ] ) ) {
				$class_name = self::get_question_class( $the_question, $args );
				if ( is_string( $class_name ) && class_exists( $class_name ) ) {
					$lesson = new $class_name( $the_question->ID, $args );
				} elseif ( $class_name instanceof LP_Question ) {
					$lesson = $class_name;
				} else {
					$lesson = new self( $the_question->ID, $args );
				}
				LP_Global::$questions[ $key ] = $lesson;
			}

			return LP_Global::$questions[ $key ];
		}

		/**
		 * Get the question class name.
		 *
		 * @param  WP_Post $the_question
		 * @param  array   $args (default: array())
		 *
		 * @return string
		 */
		private static function get_question_class( $the_question, $args = array() ) {
			$question_id = absint( $the_question->ID );
			if ( ! empty( $args['type'] ) ) {
				$question_type = $args['type'];
			} else {
				$question_type = get_post_meta( $question_id, '_lp_type', true );
			}

			$class_name = self::get_class_name_from_question_type( array( $question_type ) );

			// Filter class name so that the class can be overridden if extended.
			return apply_filters( 'learn-press/question/object-class', $class_name, $question_type, $question_id );
		}

		/**
		 * Get question class from question type.
		 *
		 * @param  string $question_type
		 *
		 * @return string|false
		 */
		public static function get_class_name_from_question_type( $question_type ) {

			if ( is_array( $question_type ) ) {
				$question_type = reset( $question_type );
			}

			return ! $question_type ? __CLASS__ : 'LP_Question_' . implode( '_', array_map( 'ucfirst', explode( '-', $question_type ) ) );
		}

		/**
		 * Get the question object.
		 *
		 * @since  3.0.0
		 *
		 * @param  mixed $the_question
		 *
		 * @uses   WP_Post
		 * @return WP_Post|bool false on failure
		 */
		private static function get_question_object( $the_question ) {
			if ( false === $the_question ) {
				$the_question = get_post_type() === LP_QUESTION_CPT ? $GLOBALS['post'] : false;
			} elseif ( is_numeric( $the_question ) ) {
				$the_question = get_post( $the_question );
			} elseif ( $the_question instanceof LP_Course_Item ) {
				$the_question = get_post( $the_question->get_id() );
			} elseif ( ! ( $the_question instanceof WP_Post ) ) {
				$the_question = false;
			}

			return apply_filters( 'learn-press/question/post-object', $the_question );
		}

		protected function _get_checked( $user_answer = null ) {
			$key = $user_answer ? md5( serialize( $user_answer ) ) : - 1;

			return wp_cache_get( 'question-' . $this->get_id() . '/' . $key, 'lp-answer-checked' );
		}

		protected function _set_checked( $checked, $user_answer ) {
			$key = $user_answer ? md5( serialize( $user_answer ) ) : - 1;

			return wp_cache_set( 'question-' . $this->get_id() . '/' . $key, $checked, 'lp-answer-checked' );
		}
	}

}
