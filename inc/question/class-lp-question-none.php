<?php

/**
 * Class LP_Question_None
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 * @extend  LP_Question
 */

defined( 'ABSPATH' ) || exit();

class LP_Question_None extends LP_Question {

	protected $_type = 'none';
	/**
	 * Construct
	 *
	 * @param mixed
	 * @param array
	 */
	public function __construct( $the_question = null, $options = null ) {
		parent::__construct( $the_question, $options );
	}
}