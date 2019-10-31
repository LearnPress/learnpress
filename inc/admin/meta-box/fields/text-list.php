<?php
/**
 * @author  leehld
 * @package LearnPress/Classes
 * @version 2.0.8
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Text_List_Field' ) ) {
	class RWMB_Text_List_Field extends RWMB_Field {
		/**
		 * Get field HTML
		 *
		 * @param string $html
		 * @param mixed  $meta
		 * @param mixed  $field
		 *
		 * @return string
		 */
		static function html( $html, $meta, $field = '' ) {
			if ( is_array( $field ) && isset( $field['field_name'] ) ) {
			} else {
				$field = $meta;
				$meta  = $html;
			}

			$value = empty( $meta ) ? $field['std'] : $meta;
			$true  = ! learn_press_is_negative_value( $value );
			$yes   = 'yes';
			$no    = 'no';

			if ( isset( $field['compare'] ) ) {
				if ( in_array( $field['compare'], array( '<>', '!=' ) ) ) {
					$true = ! $true;
					$yes  = 'no';
					$no   = 'yes';
				}
			}

			return sprintf(
				'<input type="hidden" name="%s" value="%s">
				<input type="checkbox" class="rwmb-yes-no" name="%s" id="%s" value="%s" %s>',
				$field['field_name'],
				$no,
				$field['field_name'],
				$field['id'],
				$yes,
				checked( $true, true, false )
			);
		}
	}
}