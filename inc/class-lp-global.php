<?php

/**
 * Class LP_Global
 */
class LP_Global {
	/**
	 * @var array
	 */
	public static $users = array();

	/**
	 * @var array
	 */
	public static $courses = array();

	/**
	 * @var array
	 */
	public static $lessons = array();

	/**
	 * @var array
	 */
	public static $quizzes = array();

	/**
	 * @var array
	 */
	public static $questions = array();

	/**
	 * @return LP_Quiz|LP_Lesson
	 */
	public static function course_item() {
		global $lp_course_item;

		return $lp_course_item;
	}

	/**
	 * @return LP_Course
	 */
	public static function course() {
		global $lp_course;

		return $lp_course;
	}

	/**
	 * @return LP_User
	 */
	public static function user() {
		global $lp_user;

		return $lp_user;
	}

	/**
	 * Alias of course item for highlighting in dev
	 *
	 * @return LP_Quiz|bool
	 */
	public static function course_item_quiz() {
		$item = self::course_item();

		return $item instanceof LP_Quiz ? $item : false;
	}

	/**
	 * @return LP_Question
	 */
	public static function quiz_question() {
		global $lp_quiz_question;

		return $lp_quiz_question;
	}
}
