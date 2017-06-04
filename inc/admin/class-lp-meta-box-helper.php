<?php

/**
 * Class LP_Meta_Box_Helper
 */
class LP_Meta_Box_Helper {
	public static function render_fields( $fields ) {
		$post = (object) array( 'ID' => 1, 'post_type' => 'fake-post-type' );
		setup_postdata( $post );
		if ( ! class_exists( 'RW_Meta_Box' ) ) {
			require_once LP_PLUGIN_PATH . 'inc/libraries/meta-box/meta-box.php';
		}

		$fields = RW_Meta_Box::normalize_fields( $fields );

		//$settings = $this->normalize_options();

		foreach ( $fields as $field ) {
			$origin_id           = $field['id'];
			$field['name']       = apply_filters( 'learn-press/meta-box/field-name', $field['title'] );
			$field['field_name'] = apply_filters( 'learn-press/meta-box/field-field_name', $field['id'] );
			$field['id']         = apply_filters( 'learn-press/meta-box/field-id', $field['id'] );
			$field['value']      = md5( $field['std'] );
			//learn_press_debug( $field );
			//$this->map_fields[ $field['id'] ] = $origin_id;
			LP_Meta_Box_Helper::show_field( $field );
		}
		wp_reset_postdata();
	}

	/**
	 * Show field
	 *
	 * @param $field
	 */
	public static function show_field( $field ) {
		$callable = array( 'RW_Meta_Box', 'get_class_name' );
		if ( ! is_callable( $callable ) ) {
			$callable = array( 'RWMB_Field', 'get_class_name' );
		}
		if ( is_callable( $callable ) ) {
			$field_class = call_user_func( $callable, $field );
		} else {
			$field_class = false;
		}
		if ( $field_class ) {
			call_user_func( array( $field_class, 'show' ), $field, true );
		}
	}
}