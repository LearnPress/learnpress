<?php
if ( ! class_exists( 'LP_Meta_Box_Helper' ) ) {
	/**
	 * Class LP_Meta_Box_Helper
	 */
	class LP_Meta_Box_Helper {
		/**
		 * @var array
		 */
		protected static $types = array();

		/**
		 * @var array
		 */
		protected static $conditional_logic = array();

		/**
		 * @var null
		 */
		protected static $_screen = null;

		public static function output_fields( $options ) {
			foreach ( $options as $value ) {
				if ( ! isset( $value['type'] ) ) {
					continue;
				}
				if ( ! isset( $value['id'] ) ) {
					$value['id'] = '';
				}
				if ( ! isset( $value['title'] ) ) {
					$value['title'] = isset( $value['name'] ) ? $value['name'] : '';
				}
				if ( ! isset( $value['class'] ) ) {
					$value['class'] = '';
				}
				if ( ! isset( $value['css'] ) ) {
					$value['css'] = '';
				}
				if ( ! isset( $value['default'] ) ) {
					$value['default'] = '';
				}
				if ( ! isset( $value['desc'] ) ) {
					$value['desc'] = '';
				}
				if ( ! isset( $value['desc_tip'] ) ) {
					$value['desc_tip'] = false;
				}
				if ( ! isset( $value['placeholder'] ) ) {
					$value['placeholder'] = '';
				}
				if ( ! isset( $value['suffix'] ) ) {
					$value['suffix'] = '';
				}
				if ( ! isset( $value['value'] ) ) {
					$value['value'] = self::get_option( $value['id'], $value['default'] );
				}

				$custom_attributes = array();

				if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
					foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
						$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
					}
				}

				$field_description = self::get_field_description( $value );
				$description       = $field_description['description'];
				$tooltip_html      = $field_description['tooltip_html'];

				switch ( $value['type'] ) {
					case 'text':
					case 'password':
					case 'datetime':
					case 'datetime-local':
					case 'date':
					case 'month':
					case 'time':
					case 'week':
					case 'number':
					case 'email':
					case 'url':
					case 'tel':
						include LP_PLUGIN_PATH . 'inc/admin/meta-box/fields/text.php';
						break;
					case 'select':
					case 'multiselect':
						include LP_PLUGIN_PATH . 'inc/admin/meta-box/fields/select.php';
						break;
					case 'image_advanced':
						include LP_PLUGIN_PATH . 'inc/admin/meta-box/fields/image-advanced.php';
						break;
					case 'checkbox':
					case 'yes-no':
						include LP_PLUGIN_PATH . 'inc/admin/meta-box/fields/checkbox.php';
						break;
					default:
						$file_meta_box_custom = LP_PLUGIN_PATH . 'inc/admin/meta-box/fields/' . $value['type'] . '.php';
						$file_meta_box_custom = apply_filters( 'learnpress/meta-box/field-custom', $file_meta_box_custom );

						$pattern_find_match = '/\/admin\/meta-box\/fields\//';
						preg_match( $pattern_find_match, $file_meta_box_custom, $match );

						if ( empty( $match ) ) {
							echo sprintf( '<p class="lp-error">Path file "%s" not valid. Format must is /admin/meta-box/fields</p>', $value['type'] );
						}

						if ( file_exists( $file_meta_box_custom ) ) {
							include $file_meta_box_custom;
						} else {
							echo sprintf( '<p class="lp-error">File meta box "%s" not exists</p>', $value['type'] );
						}

						break;
				}
			}
		}

		public static function save_fields( $options, $data = null ) {
			if ( empty( $data ) ) {
				return false;
			}

			$update_options   = array();
			$autoload_options = array();

			foreach ( $options as $option ) {
				if ( ! isset( $option['id'] ) || ! isset( $option['type'] ) || ( isset( $option['is_option'] ) && false === $option['is_option'] ) ) {
					continue;
				}

				if ( strstr( $option['id'], '[' ) ) {
					parse_str( $option['id'], $option_name_array );
					$option_name  = current( array_keys( $option_name_array ) );
					$setting_name = key( $option_name_array[ $option_name ] );
					$raw_value    = isset( $data[ $option_name ][ $setting_name ] ) ? wp_unslash( $data[ $option_name ][ $setting_name ] ) : null;
				} else {
					$option_name  = $option['id'];
					$setting_name = '';
					$raw_value    = isset( $data[ $option['id'] ] ) ? wp_unslash( $data[ $option['id'] ] ) : null;
				}

				switch ( $option['type'] ) {
					case 'checkbox':
					case 'yes-no':
						$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
						break;
					case 'textarea':
						$value = trim( $raw_value );
						break;
					case 'multiselect':
					case 'multi_select_countries':
						$value = array_filter( array_map( 'learnpress_clean', (array) $raw_value ) );
						break;
					case 'image-dimensions':
						$value = array();
						if ( isset( $raw_value['width'] ) ) {
							$value['width']  = learnpress_clean( $raw_value['width'] );
							$value['height'] = learnpress_clean( $raw_value['height'] );
							$value['crop']   = isset( $raw_value['crop'] ) ? 1 : 0;
						} else {
							$value['width']  = $option['default'][0];
							$value['height'] = $option['default'][1];
							$value['crop']   = $option['default'][2];
						}
						break;
					case 'select':
						$allowed_values = empty( $option['options'] ) ? array() : array_map( 'strval', array_keys( $option['options'] ) );
						if ( empty( $option['default'] ) && empty( $allowed_values ) ) {
							$value = null;
							break;
						}
						$default = ( empty( $option['default'] ) ? $allowed_values[0] : $option['default'] );
						$value   = in_array( $raw_value, $allowed_values, true ) ? $raw_value : $default;
						break;
					case 'custom-fields':
						$value = array();

						if ( is_array( $raw_value ) && ! empty( $raw_value ) ) {
							foreach ( $raw_value as $feilds ) {
								foreach ( $option['options'] as $cfkey => $cfoption ) {
									if ( $cfoption['type'] === 'checkbox' ) {
										$feilds[ $cfkey ] = ( isset( $feilds[ $cfkey ] ) && ( $feilds[ $cfkey ] === '1' || $feilds[ $cfkey ] === 'yes' ) ) ? 'yes' : 'no';
									}
								}

								$cfsort = $feilds['sort'];
								unset( $feilds['sort'] );
								$value[ $cfsort ] = $feilds;
							}
						}

						//$value = LP_Helper::sanitize_params_submitted( $value );
						break;

					case 'image_advanced':
						$value = ! empty( $raw_value ) ? array_filter( explode( ',', learnpress_clean( $raw_value ) ) ) : array();
						break;
					case 'image':
						$value = ! empty( $raw_value ) ? absint( learnpress_clean( $raw_value ) ) : '';
						break;
					case 'email-content':
						$value = ! empty( $raw_value ) ? $raw_value : array();
						//$value = LP_Helper::sanitize_params_submitted( $value, 'html' );
						break;
					case 'url':
						$value = ! empty( $raw_value ) ? esc_url_raw( $raw_value ) : '';
						break;
					default:
						$value = $raw_value;
						break;
				}

				/**
				 * Sanitize the value of an option.
				 *
				 * @since 4.0.0
				 */
				$value = apply_filters( 'learnpress_metabox_settings_sanitize_option', $value, $option, $raw_value );

				/**
				 * Sanitize the value of an option by option name.
				 *
				 * @since 4.0.0
				 */
				$value = apply_filters( "learnpress_metabox_settings_sanitize_option_$option_name", $value, $option, $raw_value );

				if ( is_null( $value ) ) {
					continue;
				}

				if ( $option_name && $setting_name ) {
					if ( ! isset( $update_options[ $option_name ] ) ) {
						$update_options[ $option_name ] = get_option( $option_name, array() );
					}
					if ( ! is_array( $update_options[ $option_name ] ) ) {
						$update_options[ $option_name ] = array();
					}
					$update_options[ $option_name ][ $setting_name ] = $value;
				} else {
					$update_options[ $option_name ] = $value;
				}

				$autoload_options[ $option_name ] = isset( $option['autoload'] ) ? (bool) $option['autoload'] : true;

				do_action( 'learnpress_update_metabox_option', $option );

				// Save all options in our array.
				foreach ( $update_options as $name => $value ) {
					update_option( $name, $value, $autoload_options[ $name ] ? 'yes' : 'no' );
				}
			}
		}

		public static function get_field_description( $value ) {
			$description  = '';
			$tooltip_html = '';

			if ( true === $value['desc_tip'] ) {
				$tooltip_html = $value['desc'];
			} elseif ( ! empty( $value['desc_tip'] ) ) {
				$description  = $value['desc'];
				$tooltip_html = $value['desc_tip'];
			} elseif ( ! empty( $value['desc'] ) ) {
				$description = $value['desc'];
			}

			if ( $description && in_array( $value['type'], array( 'textarea', 'radio' ), true ) ) {
				$description = '<p style="margin-top:0">' . wp_kses_post( $description ) . '</p>';
			} elseif ( $description && in_array( $value['type'], array( 'checkbox' ), true ) ) {
				$description = wp_kses_post( $description );
			} elseif ( $description ) {
				$description = '<p class="description">' . wp_kses_post( $description ) . '</p>';
			}

			if ( $tooltip_html && in_array( $value['type'], array( 'checkbox' ), true ) ) {
				$tooltip_html = '<p class="description">' . $tooltip_html . '</p>';
			} elseif ( $tooltip_html ) {
				$tooltip_html = learn_press_quick_tip( $tooltip_html, false );
			}

			return array(
				'description'  => $description,
				'tooltip_html' => $tooltip_html,
			);
		}

		public static function get_option( $option_name, $default = '' ) {
			if ( strstr( $option_name, '[' ) ) {
				parse_str( $option_name, $option_array );

				// Option name is first key
				$option_name = current( array_keys( $option_array ) );

				// Get value
				$option_values = get_option( $option_name, '' );

				$key = key( $option_array[ $option_name ] );

				if ( is_array( $option_array[ $option_name ][ $key ] ) ) {
					$key_child = key( $option_array[ $option_name ][ $key ] );
				}

				if ( isset( $option_values[ $key ] ) ) {
					$option_value = $option_values[ $key ];

					if ( is_array( $option_value ) && isset( $key_child )
						&& array_key_exists( $key_child, $option_value ) ) {
						$option_value = $option_value[ $key_child ];
					}
				} else {
					$option_value = null;
				}
			} else {
				// Single value
				$option_value = LP_Settings::instance()->get( preg_replace( '!^learn_press_!', '', $option_name ), null );
			}

			if ( ! is_array( $option_value ) && ! is_null( $option_value ) ) {
				$option_value = stripslashes( $option_value );
			}

			return $option_value === null ? $default : $option_value;
		}
	}
}
