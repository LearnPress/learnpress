<?php

/**
 * Class LP_Template
 *
 * @since 3.3.0
 */
class LP_Template {
	/**
	 * @var LP_Template
	 */
	protected static $instance = null;

	/**
	 * @var LP_Template_Course
	 */
	public $profile = null;

	/**
	 * @var LP_Template_Profile
	 */
	public $course = null;

	/**
	 * @var LP_Template_General
	 */
	public $general = null;

	public function __construct() {
		$this->course  = include_once 'templates/class-lp-template-course.php';
		$this->profile = include_once 'templates/class-lp-template-profile.php';
		$this->general = include_once 'templates/class-lp-template-general.php';
	}

	/**
	 * @return LP_Template
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}