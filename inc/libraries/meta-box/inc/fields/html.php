<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Html_Field' ) ) {
	class RWMB_Html_Field extends RWMB_Field {
		static function html( $meta, $field ) {
			return $field['html'];
		}

	}
}
