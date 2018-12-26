<?php
/**
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Pages_Dropdown_Field' ) ) {
	class RWMB_Pages_Dropdown_Field extends RWMB_Field {
		/**
		 * Get field HTML
		 *
		 * @param mixed $meta
		 * @param mixed $field
		 *
		 * @return string
		 */
		static function html( $meta, $field = '' ) {
			if( $field['std'] && function_exists('icl_object_id') ) {
				$field_std = icl_object_id($field['std'],'page', false,ICL_LANGUAGE_CODE);
				if( $field_std ) {
					$field['std'] = $field_std;
				}
			}

			$args = array(
				'echo'     => false,
				'name'     => $field['id'],
				'selected' => $field['std']
			);
			return learn_press_pages_dropdown( $args );
		}
	}
}