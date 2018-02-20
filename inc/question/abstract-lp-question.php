<?php

/**
 * Base class for type of question
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

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

	public $content = '';

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
	public function __construct( $the_question = null, $args = null ) {
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

	public function __get( $key ) {
		if ( !isset( $this->{$key} ) ) {
			$return = null;
			switch ( $key ) {
				case 'answers':
					$return = $this->get_answers();
					break;
				default:
					$return = get_post_meta( $this->id, '_lp_' . $key, true );
					if ( $key == 'mark' && $return <= 0 ) {
						$return = 1;
					}
			}
			$this->{$key} = $return;
		}
		return $this->{$key};
	}

	protected function _init() {
		//add_action( 'save_post', array( $this, 'save' ) );
		add_filter( 'learn_press_question_answers', array( $this, '_get_default_answers' ), 10, 2 );
	}

	public function get_title() {
		return get_the_title( $this->id );
	}

	public function get_content() {
		if ( !did_action( 'learn_press_get_content_' . $this->id ) ) {
			global $post, $wp_query;
			$post  = get_post( $this->id );
			//$posts = apply_filters( 'the_posts', array( $post ), $wp_query );
			$posts = apply_filters_ref_array( 'the_posts', array( array( $post ), &$wp_query ) );

			if ( $posts ) {
				$post = $posts[0];
			}
			setup_postdata( $post );
			ob_start();
			the_content();
			$this->content = ob_get_clean();
			wp_reset_postdata();
			do_action( 'learn_press_get_content_' . $this->id );
		}
		return $this->content;
	}

	/**
	 * Remove all answers to prepare for inserting new
	 */
	public function empty_answers() {
		global $wpdb;
		$query = $wpdb->prepare( "
			DELETE FROM {$wpdb->learnpress_question_answers}
			WHERE question_id = %d
		", $this->id );
		do_action( 'learn_press_before_delete_question_answers', $this->id );
		$wpdb->query( $query );
		do_action( 'learn_press_delete_question_answers', $this->id );

		learn_press_reset_auto_increment( 'learnpress_question_answers' );
	}

	public function _get_default_answers( $answers = false, $q = null ) {
		if ( !$answers && ( $q && $q->id == $this->id ) ) {
			$answers = $this->get_default_answers( $answers );
		}
		return $answers;
	}

	public function get_default_answers( $answers = false ) {
		if ( !$answers ) {
			$answers = array(
				array(
					'is_true' => 'yes',
					'value'   => learn_press_uniqid(),
					'text'    => __( 'Option First', 'learnpress' )
				),
				array(
					'is_true' => 'no',
					'value'   => learn_press_uniqid(),
					'text'    => __( 'Option Seconds', 'learnpress' )
				),
				array(
					'is_true' => 'no',
					'value'   => learn_press_uniqid(),
					'text'    => __( 'Option Third', 'learnpress' )
				)
			);
		}
		return $answers;
	}

	public function save( $post_data = null ) {
		global $wpdb;
		/**
		 * Allows add more type of question to save with the rules below
		 */
		$types = apply_filters( 'learn_press_save_default_question_types', array( 'true_or_false', 'multi_choice', 'single_choice' ) );
		//echo $this->type;
		///print_r($post_data);die();
		if ( in_array( $this->type, $types ) ) {

			$this->empty_answers();

			if ( !empty( $post_data['answer'] ) ) {
				$checked = !empty( $post_data['checked'] ) ? (array) $post_data['checked'] : array();
				$answers = array();
				foreach ( $post_data['answer']['text'] as $index => $text ) {
					if ( !$text ) {
						continue;
					}
					$data      = array(
						'answer_data'  => array(
							'text'    => stripslashes( $text ),
							'value'   => $post_data['answer']['value'][$index],
							'is_true' => in_array( $post_data['answer']['value'][$index], $checked ) ? 'yes' : 'no'
						),
						'answer_order' => $index + 1,
						'question_id'  => $this->id
					);
					$answers[] = apply_filters( 'learn_press_question_answer_data', $data, $post_data['answer'], $this );
				}

				if ( $answers = apply_filters( 'learn_press_question_answers_data', $answers, $post_data['answer'], $this ) ) {
					foreach ( $answers as $answer ) {
						$answer['answer_data'] = maybe_serialize( $answer['answer_data'] );
						$wpdb->insert(
							$wpdb->learnpress_question_answers,
							$answer,
							array( '%s', '%d', '%d' )
						);
					}

				}
			}
			if ( $this->mark == 0 ) {
				$this->mark = 1;
				update_post_meta( $this->id, '_lp_mark', 1 );
			}
		}
		do_action( 'learn_press_update_question_answer', $this, $post_data );
	}

	protected function _get_option_value( $value = null ) {
		if ( !$value ) {
			$value = uniqid();
		}
		return $value;
	}

	public function get_answers( $field = null, $exclude = null ) {
		global $wpdb;
		$answers = array();
		/**
		 * Question post type should be cached
		 */
		if ( $question_post = get_post( $this->id ) ) {
			$answers = !empty( $question_post->answers ) ? maybe_unserialize( $question_post->answers ) : array();
		}

		/*	if ( !empty( $GLOBALS['learnpress_question_answers'][$this->id] ) ) {
				if ( array_key_exists( $this->id, $GLOBALS['learnpress_question_answers'] ) ) {
					$answers = $GLOBALS['learnpress_question_answers'][$this->id];
				}
			}
			if ( empty( $answers ) ) {
				$query = $wpdb->prepare( "
					SELECT *
					FROM {$wpdb->learnpress_question_answers}
					WHERE question_id = %d
					ORDER BY answer_order ASC
				", $this->id );
				if ( $rows = $wpdb->get_results( $query ) ) {
					foreach ( $rows as $row ) {
						$answers[$row->question_answer_id]       = maybe_unserialize( $row->answer_data );
						$answers[$row->question_answer_id]['id'] = $row->question_answer_id;
					}
				}
				$GLOBALS['learnpress_question_answers'][$this->id] = $answers;
			}*/
		if ( $answers && ( $field || $exclude ) ) {
			if ( $field ) settype( $field, 'array' );
			if ( $exclude ) settype( $exclude, 'array' );
			foreach ( $answers as $k => $v ) {
				$new_arr = $field ? array() : $v;
				if ( $field ) foreach ( $field as $f ) {
					$new_arr[$f] = $v[$f];
				}
				if ( $exclude ) foreach ( $exclude as $f ) {
					if ( array_key_exists( $f, $new_arr ) ) {
						unset( $new_arr[$f] );
					}
				}
				$answers[$k] = $new_arr;
			}
		}

		return apply_filters( 'learn_press_question_answers', $answers, $this );
	}

	/*function get_default_answers( $answers = false ) {
		return $answers;
	}*/

	public function submit_answer( $quiz_id, $answer ) {
		return false;
	}

	public function get_type() {
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
	public function admin_interface_head( $args = array() ) {
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
	public function admin_interface_foot( $args = array() ) {
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
	public function admin_interface( $args = array() ) {
		printf( __( 'Function %s should override from its child', 'learnpress' ), __FUNCTION__ );
	}

	/**
	 * Prints the question in frontend user
	 *
	 * @param unknown
	 *
	 * @return void
	 */
	public function render() {
		printf( __( 'Function %s should override from its child', 'learnpress' ), __FUNCTION__ );
	}

	public function get_name() {
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
	public function save_post_action() {
	}

	/**
	 * Store question data
	 */
	public function store() {
		$post_id = $this->id;
		$is_new  = false;
		if ( $post_id ) {
			$post_id = wp_update_post(
				array(
					'ID'          => $post_id,
					'post_title'  => $this->get( 'post_title' ),
					'post_type'   => LP_QUESTION_CPT,
					'post_status' => 'publish'

				)
			);
		} else {
			$post_id = wp_insert_post(
				array(
					'post_title'  => $this->get( 'post_title' ),
					'post_type'   => LP_QUESTION_CPT,
					'post_status' => 'publish'
				)
			);
			$is_new  = true;
		}
		learn_press_debug( $_POST );
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

	public function get_icon() {
		return '<img src="' . apply_filters( 'learn_press_question_icon', LP()->plugin_url( 'assets/images/question.png' ), $this ) . '">';
	}

	public function get_params() {

	}

	public function is_selected_option( $answer, $answered = false ) {
		$value = array_key_exists( 'value', $answer ) ? $answer['value'] : '';
		if ( is_array( $answered ) ) {
			$is_selected = in_array( $value, $answered );
		} else {
			$is_selected = ( $value . '' === $answered . '' );
		}

		return apply_filters( 'learn_press_is_selected_option', $is_selected, $answer, $answered, $this );
	}

	public function save_user_answer( $answer, $quiz_id, $user_id = null ) {
		if ( $user_id ) {
			$user = LP_User_Factory::get_user( $user_id );
		} else {
			$user = learn_press_get_current_user();
		}

		if ( $progress = $user->get_quiz_progress( $quiz_id ) ) {
			if ( !isset( $progress->question_answers ) ) {
				$question_answers = array();
			} else {
				$question_answers = $progress->question_answers;
			}
			$question_answers[$this->id] = $answer;

			$question_answers = apply_filters( 'learn_press_update_user_question_answers', $question_answers, $progress->history_id, $user_id, $this, $quiz_id );

			learn_press_update_user_quiz_meta( $progress->history_id, 'question_answers', $question_answers );
		}
		//do_action( 'learn_press_update_user_answer', $progress, $user_id, $this, $quiz_id );
	}

	public function can_check_answer() {
		return false;
	}

	public function check( $args = null ) {
		$return = array(
			'correct' => false,
			'mark'    => 0
		);
		return $return;
	}

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
			if ( $user_meta && array_key_exists( $this->id, $user_meta ) ) {
				$answered = $user_meta[$this->id];
			}
		} elseif ( $args['quiz_id'] && $args['course_id'] ) {
			$user    = learn_press_get_current_user();
			$history = $user->get_quiz_results( $args['quiz_id'], $args['course_id'], $args['force'] );

			if ( $history ) {
				$user_meta = learn_press_get_user_item_meta( $history->history_id, 'question_answers', true );

				if ( $user_meta && array_key_exists( $this->id, $user_meta ) ) {
					$answered = $user_meta[$this->id];
				}
			}
		}
		return $answered;
	}
}

/**
 * Class LP_Question
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
class LP_Question extends LP_Abstract_Question {
	/**
	 * @var array
	 */
	protected $instances = array();

	/**
	 * @var null
	 */
	protected $options = null;

	/**
	 * @var int
	 */
	public $id = 0;

	/**
	 * @var null
	 */
	public $post = null;

	/**
	 * @param null $the_question
	 * @param null $options
	 */
	public function __construct( $the_question = null, $options = null ) {
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

		$this->_options = $options;
		if ( is_admin() ) {
			add_action( 'admin_print_scripts', array( $this, 'admin_script' ) );
			add_action( 'admin_enqueue_styles', array( $this, 'admin_style' ) );

		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_script' ) );
			add_action( 'wp_enqueue_styles', array( $this, 'wp_style' ) );

		}

		$this->options = (array) $options;

		$this->_parse();
		//print_r($this);
	}

	public function __get( $key ) {
		$return = null;
		if ( strtolower( $key ) == 'id' ) {
			$key = 'id';
		}
		if ( property_exists( $this, $key ) ) {
			$return = $this->{$key};
		} else {
			switch ( $key ) {
				case 'answers':
					$question_meta = (array) get_post_meta( $this->id, '_lpr_question', true );
					$return        = !empty( $question_meta['answer'] ) ? $question_meta['answer'] : '';
					break;
			}
		}
		return $return;
	}

	public function submit_answer( $quiz_id, $answer ) {
		print_r( $_POST );
		die();
	}

	/**
	 * Parse the content of the post if the ID is passed to $options
	 * or try to find $post if it set
	 */
	private function _parse() {
		$this->options = array_merge( $this->options, (array) get_post_meta( $this->ID, '_lpr_question', true ) );
	}

	public function admin_script() {
		global $wp_query, $post, $post_type;
		if ( !in_array( $post_type, array( 'lpr_question', 'lpr_quiz', 'lpr_lesson' ) ) ) return;
		if ( empty( $post->ID ) || $wp_query->is_archive ) return;

	}

	public function admin_style() {

	}

	public function wp_script() {

	}

	public function wp_style() {

	}

	/**
	 * Prints the header of a question in admin mode
	 * should call this function before in the top of admin_interface in extends class
	 *
	 * @param array $args
	 *
	 * @reutrn void
	 */
	public function admin_interface_head( $args = array() ) {
		$post_id = $this->get( 'ID' );
		settype( $args, 'array' );
		$is_collapse = array_key_exists( 'toggle', $args ) && !$args['toggle'];

		$questions = learn_press_get_question_types();
		?>
		<div class="lpr-question lpr-question-<?php echo preg_replace( '!_!', '-', $this->get_type() ); ?>" data-type="<?php echo preg_replace( '!_!', '-', $this->get_type() ); ?>" data-id="<?php echo $this->get( 'ID' ); ?>" id="learn-press-question-<?php echo $this->id; ?>">
		<div class="lpr-question-head">
			<p>
				<a href="<?php echo get_edit_post_link( $post_id ); ?>"><?php _e( 'Edit', 'learnpress' ); ?></a>
				<a href="" data-action="remove"><?php _e( 'Remove', 'learnpress' ); ?></a>
				<a href="" data-action="expand" class="<?php echo !$is_collapse ? "hide-if-js" : ""; ?>"><?php _e( 'Expand', 'learnpress' ); ?></a>
				<a href="" data-action="collapse" class="<?php echo $is_collapse ? "hide-if-js" : ""; ?>"><?php _e( 'Collapse', 'learnpress' ); ?></a>
			</p>
			<span class="lpr-question-title"><input class="inactive" type="text" name="lpr_question[<?php echo $this->get( 'ID' ); ?>][text]" value="<?php echo esc_attr( $this->post->post_title ); ?>" /></span>
		</div>
		<div class="lpr-question-content<?php echo $is_collapse ? " hide-if-js" : ""; ?>">
		<?php //do_action( 'learn_press_admin_before_question_answer', $this );
		?>
		<p class="lpr-question-option-label"><?php _e( 'Answer', 'learnpress' ); ?></p>
		<?php
	}

	/**
	 * Prints the header of a question in admin mode
	 * should call this function before in the bottom of admin_interface in extends class
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	public function admin_interface_foot( $args = array() ) {
		settype( $args, 'array' );
		$is_collapse = array_key_exists( 'toggle', $args ) && !$args['toggle'];
		//print_r($args);
		$question_types = LPR_Question_Factory::get_types();
		$question_meta  = (array) get_post_meta( $this->id, '_lpr_question', true );
		$question_type  = LPR_Question_Factory::instance()->get_question_type( $question_meta );
		?>
		<p class="lpr-change-question-type">
			<span><?php _e( 'Change type of this question to', 'learnpress' ); ?></span>
			<select class="lpr-question-types" name="lpr_question[type]" id="lpr_question-type" data-type="<?php echo $question_type; ?>">
				<option value=""><?php _e( '--Select type--', 'learnpress' ); ?></option>
				<?php if ( $question_types ): foreach ( $question_types as $type ): ?>
					<option value="<?php echo $type; ?>" <?php selected( $type == $question_type ); ?>><?php echo learn_press_question_slug_to_title( $type ); ?></option>
				<?php endforeach; endif; ?>
			</select>
		</p>
		<input class="lpr-question-toggle" type="hidden" name="lpr_question[<?php echo $this->get( 'ID' ); ?>][toggle]" value="<?php echo $is_collapse ? 0 : 1; ?>" />
		</div>
		</div>
		<?php
	}

	/**
	 * Prints the content of a question in admin mode
	 * This function should be overridden from extends class
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	public function admin_interface( $args = array() ) {
		printf( __( 'Function %s should override from its child', 'learnpress' ), __FUNCTION__ );
	}

	/**
	 * Prints the question in frontend user
	 *
	 * @param unknown
	 *
	 * @return void
	 */
	public function render() {
		printf( __( 'Function %s should override from its child', 'learnpress' ), __FUNCTION__ );
	}

	public function get_type( $slug = false ) {
		$type = strtolower( preg_replace( '!LPR_Question_Type_!', '', get_class( $this ) ) );
		if ( $slug ) $type = preg_replace( '!_!', '-', $type );
		return $type;
	}

	public function get_name() {
		return
			isset( $this->options['name'] ) ? $this->options['name'] : ucfirst( preg_replace_callback( '!_([a-z])!', create_function( '$matches', 'return " " . strtoupper($matches[1]);' ), $this->get_type() ) );
	}

	protected function _get_option_value( $value = null ) {
		if ( !$value ) {
			$value = uniqid();
		}
		return $value;
	}

	/**
	 * Sets the value for a variable of this class
	 *
	 * @param   $key      string  The name of a variable of this class
	 * @param   $value    any     The value to set
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
		if ( is_string( $key ) && strpos( $key, '.' ) === false ) {
			return $this->{$key};
		}
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


	public function show_answer() {

	}

	/**
	 * Save question data on POST action
	 */
	public function save_post_action() {
	}

	/**
	 * Store question data
	 */
	public function store() {
		$post_id = $this->get( 'ID' );
		$is_new  = false;
		if ( $post_id ) {
			$post_id = wp_update_post(
				array(
					'ID'          => $post_id,
					'post_title'  => $this->get( 'post_title' ),
					'post_type'   => 'lpr_question',
					'post_status' => 'publish'

				)
			);
		} else {
			$post_id = wp_insert_post(
				array(
					'post_title'  => $this->get( 'post_title' ),
					'post_type'   => 'lpr_question',
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

	/**
	 * Gets an instance of a question by type or ID
	 * If the first param is a string ( type of question such as true_or_false ) then return the instance of class LPR_Question_Type_True_Or_False
	 * If the first param is a number ( ID of the post ) then find a post in the database with the type store in meta_key to return class corresponding
	 *
	 * @param   null $id_or_type Type or ID of an question in database
	 * @param   null $options
	 *
	 * @return  bool
	 */
	public static function instance( $id_or_type = null, $options = null ) {
		$type = $id_or_type;
		if ( is_numeric( $id_or_type ) ) {
			$question = get_post( $id_or_type );
			if ( $question ) {
				$meta = (array) get_post_meta( $id_or_type, '_lpr_question', true );
				if ( isset( $meta['type'] ) ) {
					$type    = $meta['type'];
					$options = array_merge( (array) $options, array( 'ID' => $id_or_type ) );
				}
				//print_r($meta);
			}
		} else {

		}
		$class_name     = 'LPR_Question_Type_' . ucfirst( preg_replace_callback( '!(_[a-z])!', create_function( '$matches', 'return strtolower($matches[1]);' ), $type ) );
		$class_instance = false;

		if ( !class_exists( $class_name ) ) {
			$paths = array(
				LPR_PLUGIN_PATH . '/inc/question-type'
			);
			$paths = apply_filters( 'lpr_question_type_path', $paths );
			if ( $paths ) foreach ( $paths as $path ) {
				if ( is_file( $path ) ) {
					$file = $path;
				} else {
					$file = $path . '/class.lpr-question-type-' . preg_replace( '!_!', '-', $type ) . '.php';
				}
				if ( file_exists( $file ) ) {
					require_once( $file );
				}
			}
		}
		if ( class_exists( $class_name ) ) {
			$class_instance = new $class_name( $id_or_type, $options );
		} else {
			$class_instance = new LPR_Question_Type_None();
		}
		return $class_instance;
	}

	public function check( $args = null ) {
		$return = array(
			'correct' => false,
			'mark'    => 0
		);
		return $return;
	}
}