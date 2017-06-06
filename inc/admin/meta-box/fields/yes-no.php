<?php
/**
 * @author  leehld
 * @package LearnPress/Classes
 * @version 2.0.8
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'RWMB_Yes_No_Field' ) ) {
	class RWMB_Yes_No_Field extends RWMB_Field {
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
			return sprintf(
				'<input type="checkbox" class="rwmb-yes-no" name="%s" id="%s" value="1" %s>',
				$field['field_name'],
				$field['id'],
				empty( $meta ) ? checked( $field['std'], 'yes', false ) : checked( $meta, 'yes', false )
			);
		}

		/**
		 * Set the value of checkbox to 1 or 0 instead of 'checked' and empty string
		 * This prevents using default value once the checkbox has been unchecked
		 *
		 * @link https://github.com/rilwis/meta-box/issues/6
		 *
		 * @param mixed $new
		 * @param mixed $old
		 * @param int   $post_id
		 * @param array $field
		 *
		 * @return int
		 */
		static function value( $new, $old, $post_id, $field ) {
			return empty( $new ) ? 'no' : 'yes';
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