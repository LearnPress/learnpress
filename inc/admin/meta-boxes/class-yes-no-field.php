<?php
/**
 * @author  leehld
 * @package LearnPress/Classes
 * @version 2.0.8
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Yes_No_Field' ) ) {
	/**
	 * Class RWMB_Yes_No_Field
	 */
	class RWMB_Yes_No_Field extends RWMB_Field {
		/**
		 * Get field HTML
		 *
		 * @param mixed $meta
		 * @param mixed $field
		 *
		 * @return string
		 */
		static function html( $meta, $field = '' ) {

			$value = empty( $meta ) ? $field['std'] : $meta;
			$true  = ! learn_press_is_negative_value( $value );

			return sprintf(
				'<input type="hidden" name="%s" value="no">
				<input type="checkbox" class="rwmb-yes-no" name="%s" id="%s" value="yes" %s>',
				$field['field_name'],
				$field['field_name'],
				$field['id'],
				checked( $true, true, false )
			);
		}

		static function begin_html( $html, $meta, $field = '' ) {
			if ( is_array( $field ) && isset( $field['field_name'] ) ) {
				return RW_Meta_Box::begin_html( $html, $meta, $field );
			} else {
				return RWMB_Field::begin_html( $html, $meta );
			}
		}
	}
}