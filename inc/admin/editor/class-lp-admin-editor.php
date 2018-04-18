<?php

/**
 * Class LP_Admin_Editor
 *
 * @since 3.0.2
 */
class LP_Admin_Editor {

	/**
	 * @var array
	 */
	protected static $editors = array();

	/**
	 * @var bool
	 */
	protected $result = false;

	/**
	 * Get admin editor type
	 *
	 * @param string $type
	 *
	 * @return bool|mixed
	 */
	public static function get( $type ) {

		if ( empty( self::$editors[ $type ] ) ) {
			$suffix = preg_replace( '~\s+~', '_', ucfirst( str_replace( '-', ' ', $type ) ) );
			$class  = "LP_Admin_Editor_{$suffix}";

			if ( ! class_exists( $class ) ) {
				$file = "class-lp-admin-editor-{$type}.php";
				include_once $file;
			}

			if ( class_exists( $class ) ) {
				self::$editors[ $type ] = new $class();
			}
		}

		return ! empty( self::$editors[ $type ] ) ? self::$editors[ $type ] : false;
	}

	public function call( $type, $args = array() ) {
		$func     = str_replace( '-', '_', $type );
		$callback = array( $this, $func );
		if ( is_callable( $callback ) ) {
			LP_Hard_Cache::flush();

			return call_user_func_array( $callback, $args );
		}

		return false;
	}

	public function heartbeat( $args = array() ) {
		$this->result = true;
	}

	/**
	 * @return mixed|WP_Error
	 */
	public function get_result() {
		return $this->result;
	}

	/**
	 * @return bool|WP_Error
	 */
	public function dispatch() {
		return false;
	}

	/**
	 * @return LP_Admin_Editor_Course
	 */
	public static function get_editor_course() {
		return self::get( 'course' );
	}

	/**
	 * @return LP_Admin_Editor_Quiz
	 */
	public static function get_editor_quiz() {
		return self::get( 'quiz' );
	}

	/**
	 * @return LP_Admin_Editor_Question
	 */
	public static function get_editor_question() {
		return self::get( 'question' );
	}
}