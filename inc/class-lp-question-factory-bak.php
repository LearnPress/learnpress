<?php

/**
 * Class LP_Question_Factory
 *
 * Helper class for question type
 */
class LP_Question_Factory {

	/**
	 * @var array
	 */
	protected static $_questions = array();

	/**
	 * @var bool
	 */
	protected static $_instance = false;

	/**
	 * @param bool|false $the_question
	 * @param array      $args
	 *
	 * @return bool
	 */
	public function get_question( $the_question = false, $args = array() ) {
		$the_question = $this->get_question_object( $the_question );

		if ( !$the_question ) {
			return false;
		}
		$classname = $this->get_question_class( $the_question, $args );
		if ( !class_exists( $classname ) ) {
			$classname = 'LP_Question_Type_None';
		}
		if ( is_array( $args ) ) {
			ksort( $args );
			$args_str = serialize( $args );
		} else {
			$args_str = $args;
		}

		$the_id = md5( $classname . $the_question->ID . '_' . $args_str );
		if ( empty( self::$_questions[$the_id] ) ) {
			self::$_questions[$the_id] = new $classname( $the_question, $args );
		}

		return self::$_questions[$the_id];
	}

	/**
	 * Get the class for a type of question from type slug
	 *
	 * @param $question_type
	 *
	 * @return bool|string
	 */
	public function get_classname_from_question_type( $question_type ) {
		return $question_type ? 'LP_Question_' . implode( '_', array_map( 'ucfirst', explode( '-', $question_type ) ) ) : false;
	}

	/**
	 * Get the type of question stored in database by it ID
	 * Also, accepts a value stored in lpr_question meta format
	 *
	 * @param $the_question
	 * @param $args
	 *
	 * @return string
	 */
	public function get_question_type( $the_question, $args = array() ) {
		if ( !empty( $args['type'] ) ) {
			$type = $args['type'];
		} else {
			if ( is_numeric( $the_question ) ) {
				$options = (array) get_post_meta( $the_question, '_lp_type', true );
			} else {
				$options = (array) $the_question;
			}
			if ( isset( $options['type'] ) ) {
				$type = $options['type'];
			} else {
				$type = 'none';
			}
		}
		return $type;
	}

	/**
	 * Get the class for a question from question object
	 *
	 * @param       $the_question
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function get_question_class( $the_question, $args = array() ) {
		$question_id = absint( $the_question->ID );

		if( !empty( $args['type'] ) ){
			$question_type = $args['type'];
		}else {
			$question_type = $this->get_question_type( $question_id, $args );
		}
		$classname = $this->get_classname_from_question_type( $question_type );

		return apply_filters( 'learn_press_question_class', $classname, $question_type, $question_id );
	}

	/**
	 * Get the post of a question from a passed variable
	 *
	 * @param $the_question
	 *
	 * @return mixed
	 */
	public function get_question_object( $the_question ) {
		if ( false === $the_question ) {
			$the_question = $GLOBALS['post'];
		} elseif ( is_numeric( $the_question ) ) {
			$the_question = get_post( $the_question );
		} elseif ( $the_question instanceof LP_Question ) {
			$the_question = get_post( $the_question->id );
		} elseif ( !( $the_question instanceof WP_Post ) ) {
			$the_question = false;
		}

		return apply_filters( 'learn_press_question_object', $the_question );
	}


	public static function instance() {
		if ( !self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Get all type of questions
	 *
	 * @return mixed
	 */
	public static function get_types() {
		$defaults = array(
			'true_or_false',
			'multi_choice',
			'single_choice'
		);
		return apply_filters( 'learn_press_question_types', $defaults );
	}

	/**
	 * Init
	 */
	public static function init() {
		add_action( 'admin_print_scripts', array( __CLASS__, 'register_js_template' ) );
		add_action( 'wp_print_scripts', array( __CLASS__, 'register_js_template' ) );
		add_action( 'learn_press_convert_question_type', array( __CLASS__, 'convert_question' ), 10, 3 );
	}

	/**
	 * Output jsj template for all question types if registered
	 */
	public static function register_js_template() {
		$factory   = self::instance();
		$questions = self::get_types();
		$method    = is_admin() ? 'admin_js_template' : 'frontend_js_template';

		if ( $questions ) foreach ( $questions as $type ) {
			$question = $factory->get_classname_from_question_type( $type );
			if ( is_callable( array( $question, $method ) ) ) {
				call_user_func( array( $question, $method ) );
			}
		}
	}

	static function convert_question( $id, $from, $to ) {
		$question      = LP_Question_Factory::get_question($id );
		$question_meta = (array) get_post_meta( $id, '_lp_question', true );

		switch ( $from ) {
			case 'true_or_false':
			case 'single_choice':
				if ( $to == 'multi_choice' ) {

				}
				break;
			case 'multi_choice':
				$count       = 0;
				$true_option = 0;
				$answers     = $question->answers;
				if ( $to == 'true_or_false' ) {
					$first_option         = reset( $answers );
					$check_seconds_option = false;
					if ( $first_option['is_true'] != 1 ) {
						$check_seconds_option = true;
					}
					foreach ( $answers as $answer ) {
						$count ++;
						if ( $answer['is_true'] == 1 ) {
							$true_option ++;
						}
						if ( $true_option > 1 ) {
							$answer['is_true'] = 0;
						}
						if ( $count == 2 && $check_seconds_option ) {
							$answer['is_true'] = 0;
						}

						if ( $count >= 2 ) {
							break;
						}
					}
				} elseif ( $to == 'single_choice' ) {
					foreach ( $answers as $answer ) {
						if ( $answer['is_true'] == 1 ) {
							$true_option ++;
						}
						if ( $true_option > 2 ) {
							$answer['is_true'] = 0;
						}
					}
				}
				$question_meta['answer'] = $answers;

		}
		$question_meta['type'] = $to;
		update_post_meta( $id, '_lpr_question', $question_meta );
	}
}

LP_Question_Factory::init();