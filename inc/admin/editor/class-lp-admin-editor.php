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
	 * Get admin editor type
	 *
	 * @param string $type
	 *
	 * @return bool|mixed
	 */
	public static function get( $type ) {

		if ( empty( self::$editors[ $type ] ) ) {
			$suffix = preg_replace('~\s+~', '_', ucfirst(str_replace('-', ' ', $type)));
			$class = "LP_Admin_Editor_{$suffix}";

			if ( ! class_exists( $class ) ) {
				$file = "class-lp-admin-editor-{$type}.php";
				include_once $file;
			}

			if(class_exists($class)){
				self::$editors[$type] = new $class();
			}
		}

		return !empty(self::$editors[$type])?self::$editors[$type]:false;
	}

	/**
	 * @return LP_Admin_Editor_Course
	 */
	public static function get_course(){
		return self::get('course');
	}
}