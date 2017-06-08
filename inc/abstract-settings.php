<?php

/**
 * Class LP_Abstract_Settings
 */
abstract class LP_Abstract_Settings {

	/**
	 * LP_Abstract_Settings constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return bool
	 */
	public function get_settings() {
		return false;
	}

	/**
	 * Get name for field
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	public function get_admin_field_name( $name ) {
		$field_name = apply_filters( 'learn_press_settings_field_name_' . $name, "learn_press_{$name}" );

		return $field_name;
	}

	/**
	 * Get ID for field
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	public function get_admin_field_id( $name ) {
		return preg_replace( array( '!\[|(\]\[)!', '!\]!' ), array( '_', '' ), $this->get_field_name( $name ) );
	}

	/**
	 * Print admin fields options.
	 */
	public function admin_options() {
		$settings = $this->get_settings();
		$settings = $this->sanitize_settings( $settings );
		LP_Meta_Box_Helper::render_fields( $settings );
	}

	public function sanitize_settings( $settings ) {
		if ( $settings ) {
			foreach ( $settings as $k => $field ) {
				$field['id'] = $this->get_admin_field_name( $field['id'] );
				if ( strpos( $field['id'], '[' ) !== false ) {
					parse_str( $field['id'], $group );
					$keys        = array_keys( $group );
					$option_name = reset( $keys );
				} else {
					$option_name = $field['id'];
				}

				if ( false === ( $std = get_option( $option_name ) ) ) {
					$std = array_key_exists( 'default', $field ) ? $field['default'] : '';
				}
				if ( isset( $group ) && is_array( $std ) ) {

					$loop = 0;
					while ( is_array( $group ) && $loop ++ < 10 ) {
						$option_keys = array_keys( $group[ $option_name ] );
						$option_name = reset( $option_keys );
						$group       = $group[ $option_name ];
						$std         = $std[ $option_name ];
					}
				}
				$field['std']                  = apply_filters( 'learn-press/settings/default-field-value', $std, $field );
				$field['learn-press-settings'] = 'yes';

				//
//				$id = preg_replace( '~[-]+~', '_', $field['id'] );
//				if ( $this->stored ) {
//					$getter = array( $this, "get_{$id}" );
//					if ( is_callable( $getter ) ) {
//						$field['std'] = call_user_func( $getter );
//					} elseif ( property_exists( $this, $id ) ) {
//						$field['std'] = $this->{$id};
//					}
//				}


				if ( ! empty( $field['visibility'] ) ) {
					$conditional = $field['visibility'];

					if ( ! array_key_exists( 0, $conditional['conditional'] ) ) {
						$conditional['conditional'] = array(
							$conditional['conditional']
						);
					}
					foreach ( $conditional['conditional'] as $kk => $conditional_field ) {
						$conditional['conditional'][ $kk ]['field'] = $this->get_admin_field_name( $conditional_field['field'] );
					}

					$field['visibility'] = $conditional;
				}

				///$settings[ $k ] = $field;

				$settings[ $k ]                = $field;
			}
		}

		return $settings;
	}
}