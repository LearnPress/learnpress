<?php
/**
 * @author  leehld
 * @package LearnPress/Classes
 * @version 2.0.8
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Html_Field' ) ) {
	class RWMB_Html_Field extends RWMB_Field {
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

			return $field['html'];
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
			return ! empty( $new ) && $new == 'yes' ? 'yes' : 'no';
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