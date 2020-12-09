<?php
/**
 * Class LP_Abstract_Settings
 */
abstract class LP_Abstract_Settings {

	/**
	 * LP_Abstract_Settings constructor.
	 */
	public function __construct() {
		add_filter( 'learn-press/update-settings/redirect', array( $this, '_do_save' ) );
	}

	public function _do_save( $url ) {
		$this->save();

		return $url;
	}

	public function save() {
		// This function should be overwritten from it's child
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
		$items   = LP_Admin_Menu::instance()->get_menu_items();
		$section = '';

		if ( ! empty( $items['settings'] ) ) {
			$tab     = $items['settings']->get_active_tab();
			$section = $items['settings']->get_active_section();
		}

		if ( $tab === 'payments' && $section !== 'general' && ! empty( $name ) ) {
			if ( strpos( $name, '[' ) === 0 ) {
				$name = $section . $name;
			} else {
				$name = $section . '_' . $name;
			}
		}

		if ( empty( $name ) ) {
			$name = md5( microtime( true ) );
		}

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
	 *
	 * @version 4.0.0
	 */
	public function admin_option_settings() {
		$settings = $this->get_settings();
		$settings = $this->sanitize_settings( $settings );

		do_action( 'learn-press/settings-render' );

		if ( $settings ) {
			LP_Meta_Box_Helper::output_fields( $settings );
		} else {
			echo esc_html__( 'No setting available.', 'learnpress' );
		}
	}

	/**
	 * Sanitize settings before rendering.
	 * Fill std from database, reformat conditional fields...
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function sanitize_settings( $settings ) {
		if ( $settings ) {
			foreach ( $settings as $k => $field ) {

				// except heading options.
				if ( isset( $field['id'] ) ) {

					$field['id'] = $this->get_admin_field_name( $field['id'] );

					// A field is an array of values, find the real name.
					if ( strpos( $field['id'], '[' ) !== false ) {
						parse_str( $field['id'], $group );
						$keys        = array_keys( $group );
						$option_name = reset( $keys );
					} else {
						$option_name = $field['id'];
					}

					// Get value from option
					if ( false === ( $std = get_option( $option_name ) ) ) {
						$std = array_key_exists( 'default', $field ) ? $field['default'] : '';
					}

					// If the field is an array
					if ( isset( $group ) && is_array( $std ) ) {
						$loop = 0;
						while ( is_array( $group ) && $loop ++ < 10 ) {
							if ( ! empty( $group[ $option_name ] ) ) {
								$option_keys = array_keys( $group[ $option_name ] );
								$option_name = reset( $option_keys );
								$group       = ! empty( $group[ $option_name ] ) ? $group[ $option_name ] : false;
								$std         = ! empty( $std[ $option_name ] ) ? $std[ $option_name ] : false;
							}
						}
					}
					$field['std']                  = apply_filters( 'learn-press/settings/default-field-value', $std, $field );
					$field['learn-press-settings'] = 'yes';
					$this->parse_conditional( $field );
					$settings[ $k ] = $field;
				}
			}
		}

		return $settings;
	}

	public function parse_conditional( &$field ) {
		// Re-format conditional logic fields
		if ( ! empty( $field['visibility'] ) ) {
			$conditional = $field['visibility'];

			if ( ! array_key_exists( 0, $conditional['conditional'] ) ) {
				$conditional['conditional'] = array(
					$conditional['conditional'],
				);
			}

			foreach ( $conditional['conditional'] as $kk => $conditional_field ) {
				$conditional['conditional'][ $kk ]['field'] = $this->get_admin_field_name( $conditional_field['field'] );
			}

			$field['visibility'] = $conditional;
		}

		return $field;
	}

	/**
	 * @param      $option_name
	 * @param null        $default
	 *
	 * @return array|null|string
	 */
	public function get_option( $option_name, $default = null ) {
		if ( strstr( $option_name, '[' ) ) {
			parse_str( $option_name, $option_array );

			// Option name is first key
			$option_name = current( array_keys( $option_array ) );

			// Get value
			$option_values = get_option( $option_name, '' );

			$key = key( $option_array[ $option_name ] );

			if ( isset( $option_values[ $key ] ) ) {
				$option_value = $option_values[ $key ];
			} else {
				$option_value = null;
			}

			// Single value
		} else {
			$option_value = LP()->settings()->get( preg_replace( '!^learn_press_!', '', $option_name ), null );
		}

		if ( is_array( $option_value ) ) {
			$option_value = array_map( 'stripslashes', $option_value );
		} elseif ( ! is_null( $option_value ) ) {
			$option_value = stripslashes( $option_value );
		}

		return $option_value === null ? $default : $option_value;
	}
}
