<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'RWMB_Duration_Field' ) ) {
	class RWMB_Duration_Field extends RWMB_Field {
		/**
		 * Get field HTML
		 *
		 * @param mixed $meta
		 * @param array $field
		 *
		 * @return string
		 */
		static function html( $meta, $field ) {
			$duration      = learn_press_get_course_duration_support();
			$duration_keys = array_keys( $duration );
			$default_time  = !empty( $field['default_time'] ) ? $field['default_time'] : end( $duration_keys );
			if ( preg_match_all( '!([0-9]+)\s*(' . join( '|', $duration_keys ) . ')?!', $meta, $matches ) ) {
				$a1 = $matches[1][0];
				$a2 = in_array( $matches[2][0], $duration_keys ) ? $matches[2][0] : $default_time;
			} else {
				$a1 = absint( $meta );
				$a2 = $default_time;
			}
			$html_option = '';
			foreach ( $duration as $k => $v ) {
				$html_option .= sprintf( '<option value="%s" %s>%s</option>', $k, selected( $k, $a2, false ), $v );
			}

			return sprintf(
				'<input type="number" class="rwmb-number" name="%s[]" id="%s" value="%s" step="%s" min="%s" placeholder="%s"/>',
				$field['field_name'],
				empty( $field['clone'] ) ? $field['id'] : '',
				$a1,
				$field['step'],
				$field['min'],
				$field['placeholder']
			) . sprintf(
				'<select name="%s[]" id="%s">%s</select>',
				$field['field_name'],
				empty( $field['clone'] ) ? $field['id'] . '_select' : '',
				$html_option
			);
		}

		/**
		 * Normalize parameters for field
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		static function normalize_field( $field ) {
			return self::normalize( $field );
		}

		/**
		 * Normalize parameters for field
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		static function normalize( $field ) {
			if ( is_callable( 'parent::normalize' ) ) {
				$field = parent::normalize( $field );
			}
			$field = wp_parse_args( $field, array(
				'step' => 1,
				'min'  => 0,
			) );
			return $field;
		}

		static function value( $new, $old, $post_id, $field ) {
			return join( ' ', $new );
		}
	}
}
