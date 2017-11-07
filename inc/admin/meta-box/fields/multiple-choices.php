<?php
/**
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Multiple_Choices_Field' ) ) {
	class RWMB_Multiple_Choices_Field extends RWMB_Input_List_Field {
		/**
		 * Normalize parameters for field.
		 *
		 * @param array $field Field parameters.
		 * @return array
		 */
		public static function normalize( $field ) {
			$field['multiple'] = true;
			$field = parent::normalize( $field );

			return $field;
		}
	}
}