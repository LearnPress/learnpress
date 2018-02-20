<?php

/**
 * Class LP_Question_None
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 * @extend  LP_Abstract_Question
 */

defined( 'ABSPATH' ) || exit();

class LP_Question_None extends LP_Question {

	/**
	 * Construct
	 * @param mixed
	 * @param array
	 */
	public function __construct( $the_question = null, $options = null ) {
		parent::__construct( $the_question, $options );
	}

	public function admin_interface( $args = array() ) {
		ob_start();
		$view = learn_press_get_admin_view( 'meta-boxes/question/none.php' );
		include $view;
		$output = ob_get_clean();

		if ( !isset( $args['echo'] ) || ( isset( $args['echo'] ) && $args['echo'] === true ) ) {
			echo $output;
		}
		return $output;
	}
}