<?php

/**
 * Class LP_Question_Factory
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
class LP_Question_Factory {

	/**
	 * Hold the javascript template
	 *
	 * @var array
	 */
	protected static $_templates = array();

	/**
	 * Hold the list of question instances we have got
	 *
	 * @var array
	 */
	protected static $_instances = array();

	/**
	 * Get the class instance for question
	 *
	 * @param bool  $the_question
	 * @param array $args
	 * @return bool
	 */
	public static function get_question( $the_question = false, $args = array() ) {
		$the_question = self::get_question_object( $the_question );
		if ( !$the_question ) {
			return false;
		}

		if( empty( self::$_instances[ $the_question->ID ] ) ) {

			if( empty( $args['question_type'] ) ){
				$args['question_type'] = get_post_meta( $the_question->ID, '_lp_type', true );
			}

			$class_name = self::get_question_class( $the_question, $args );

			if ( !class_exists( $class_name ) ) {
				$class_name = 'LP_Question_None';
			}
			self::$_instances[ $the_question->id ] = new $class_name( $the_question, $args );
		}
		return self::$_instances[ $the_question->id ];
	}

	/**
	 * @param  string
	 * @return string|false
	 */
	private static function get_class_name_from_question_type( $type ) {
		return $type ? 'LP_Question_' . implode( '_', array_map( 'ucfirst', explode( '-', $type ) ) ) : false;
	}

	/**
	 * Get the question class name
	 *
	 * @param  WP_Post $the_question
	 * @param  array   $args (default: array())
	 * @return string
	 */
	private static function get_question_class( $the_question, $args = array() ) {
		$question_id = absint( $the_question->ID );
		$post_type = $the_question->post_type;
		if ( LP()->question_post_type === $post_type ) {
			if ( isset( $args['question_type'] ) ) {
				$question_type = $args['question_type'];
			} else {
				$question_type = false;
			}
		} else {
			$question_type = false;
		}

		$class_name = self::get_class_name_from_question_type( $question_type );

		// Filter class name so that the class can be overridden if extended.
		return apply_filters( 'learn_press_question_class', $class_name, $question_type, $post_type, $question_id );
	}

	/**
	 * Get the question object
	 *
	 * @param  mixed $the_question
	 *
	 * @uses   WP_Post
	 * @return WP_Post|bool false on failure
	 */
	private static function get_question_object( $the_question ) {
		if ( false === $the_question ) {
			$the_question = $GLOBALS['post'];
		} elseif ( is_numeric( $the_question ) ) {
			$the_question = get_post( $the_question );
		} elseif ( $the_question instanceof LP_Question ) {
			$the_question = get_post( $the_question->id );
		} elseif ( isset( $the_question->ID ) ){
			$the_question = get_post( $the_question->ID );
		} elseif ( !( $the_question instanceof WP_Post ) ) {
			$the_question = false;
		}

		return apply_filters( 'learn_press_question_object', $the_question );
	}

	static function init(){

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets') );
			add_action( 'save_post', array( __CLASS__, 'save' ) );
			add_action( 'admin_print_footer_scripts', array( __CLASS__, 'admin_template' ) );
			add_action( 'learn_press_convert_question_type', array( __CLASS__, 'convert_question' ), 5, 3 );
		} else {


		}
	}

	static function admin_assets(){
		LP_Admin_Assets::enqueue_style( 'learnpress-question', learn_press_plugin_url( 'assets/css/admin/question.css' ) );
		LP_Admin_Assets::enqueue_script( 'learnpress-question', learn_press_plugin_url( 'assets/js/admin/question.js' ), array( 'jquery', 'jquery-ui-sortable') );
	}
	static function admin_template(){
		if( !self::$_templates ) {
			return;
		}

		foreach( self::$_templates as $id => $content ){
			printf( '<script id="tmpl-%s" type="text/html">%s</script>', $id, $content );
		}
	}

	static function save(){
		if( ! empty( $_POST['learn_press_question'] ) ){
			foreach( $_POST['learn_press_question'] as $the_id => $post_data ){
				( $question = self::get_question( $the_id ) ) && $question->save( $post_data );
			}
		}
	}

	static function add_template( $id, $content ){
		self::$_templates[ $id ] = $content;
	}

	static function convert_question( $id, $from, $to ){
		global $wpdb;
		$question = self::get_question( $id );
		switch( $from ){
			case 'true_or_false':
			case 'single_choice':
				if( $to == 'multi_choice' ){

				}
				break;
			case 'multi_choice':
				$count = 0;
				$true_option = 0;
				if( $to == 'true_or_false' ){
					$first_option = reset( $question->answers );
					$check_seconds_option = false;
					if( $first_option['is_true'] != 'yes' ){
						$check_seconds_option = true;
					}
					foreach( $question->answers as $answer ){
						$count++;
						if( $answer['is_true'] == 'yes' ){
							$true_option++;
						}
						if( $true_option > 1 ){
							$answer['is_true'] = 'no';
						}
						if( $count == 2 && $check_seconds_option ){
							$answer['is_true'] = 'yes';
						}
						$wpdb->update(
							$wpdb->learnpress_question_answers,
							array(
								'answer_data' => maybe_serialize($answer)
							),
							array( 'question_answer_id' => $answer['id'] ),
							array( '%s' )
						);

						if( $count >= 2 ){
							break;
						}
					}
				}elseif( $to == 'single_choice' ){
					foreach( $question->answers as $answer ){
						if( $answer['is_true'] == 'yes' ){
							$true_option++;
						}
						if( $true_option > 2 ){
							$answer['is_true'] = 'no';
						}

						$wpdb->update(
							$wpdb->learnpress_question_answers,
							array(
								'answer_data' => maybe_serialize($answer)
							),
							array( 'question_answer_id' => $answer['id'] ),
							array( '%s' )
						);
					}
				}
		}
		update_post_meta( $question->id, '_lp_type', $to );
	}
}

LP_Question_Factory::init();