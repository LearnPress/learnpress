<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'RWMB_Course_Tabs_Field' ) ) {
	/**
	 * Thim Theme
	 *
	 * Manage Course, Lesson, Quiz
	 *
	 * @class      RWMB_Course_lesson_Quiz_Field
	 */
	class RWMB_Course_Tabs_Field extends RWMB_Field {
		public static function html( $meta, $field ) {
			ob_start();
			learn_press_admin_view( 'meta-boxes/course/tabs.php' );
			return ob_get_clean();
		}
	}
}