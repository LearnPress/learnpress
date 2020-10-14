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
						include LP_PLUGIN_PATH . '/inc/admin/meta-box/fields/text.php';
						break;

					case 'select':
					case 'multiselect':
						include LP_PLUGIN_PATH . '/inc/admin/meta-box/fields/select.php';
						break;

					case 'image_advanced':
						include LP_PLUGIN_PATH . '/inc/admin/meta-box/fields/image-advanced.php';
						break;

					default:
						include LP_PLUGIN_PATH . '/inc/admin/meta-box/fields/' . $value['type'] . '.php';
						break;
				}
			}
		}

		public static function save_fields( $options, $data = null ) {
			if ( is_null( $data ) ) {
				$data = $_POST;
			}

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
						$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
						break;
					case 'textarea':
						$value = wp_kses_post( trim( $raw_value ) );
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
						break;

					case 'image_advanced':
						$value = ! empty( $raw_value ) ? array_filter( explode( ',', learnpress_clean( $raw_value ) ) ) : array();
						break;
					case 'image':
						$value = ! empty( $raw_value ) ? absint( learnpress_clean( $raw_value ) ) : '';
						break;
					case 'email-content':
						$value = ! empty( $raw_value ) ? $raw_value : array();
						break;
					default:
						$value = learnpress_clean( $raw_value );
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

				if ( isset( $option_values[ $key ] ) ) {
					$option_value = $option_values[ $key ];
				} else {
					$option_value = null;
				}
			} else {
				// Single value
				$option_value = LP()->settings->get( preg_replace( '!^learn_press_!', '', $option_name ), null );
			}

			if ( ! is_array( $option_value ) && ! is_null( $option_value ) ) {
				$option_value = stripslashes( $option_value );
			}

			return $option_value === null ? $default : $option_value;
		}

		/**
		 * Show field
		 *
		 * @param $field
		 */
		public static function show_field( $field ) {
			$fields     = RW_Meta_Box::normalize_fields( array( $field ) );
			$field      = $fields[0];
			$class_name = self::include_field( $field );

			if ( $class_name ) {
				self::parse_conditional_logic( $field );

				$field_title = self::get_field_title( $field );

				if ( isset( $field['required'] ) && $field['required'] ) {
					$field_name = sprintf( '%s <span class="asterisk">*</span>', $field_title );
				}

				$field['name'] = apply_filters( 'learn-press/meta-box/field-name', $field_title, $field );
				$field['id']   = apply_filters( 'learn-press/meta-box/field-id', $field['id'], $field );

				RWMB_Field::call( 'admin_enqueue_scripts', $field );
				ob_start();
				RWMB_Field::call( 'show', $field, true, 0 );
				$output = ob_get_clean();

				if ( preg_match( '/class="(.*)"/iSU', $output, $matches ) ) {
					if ( preg_match( '/required/', $matches[0] ) ) {
						$class  = preg_replace( '/\s+/', ' ', str_replace( 'required', '', $matches[0] ) );
						$output = str_replace( $matches[0], $class, $output );
					}
				}

				echo $output;
				RWMB_Field::call( 'add_actions', $field );
			}
		}

		/**
		 * Find the field's name
		 */
		public static function get_field_title( $field ) {
			$field_name = '';
			foreach ( array( 'title', 'name' ) as $value ) {
				if ( isset( $field[ $value ] ) ) {
					$field_name = $field[ $value ];
					break;
				}
			}

			return $field_name;
		}

		protected static function sanitize_name( $name ) {
			return preg_replace( array( '!\[|(\]\[)!', '!\]!' ), array( '_', '' ), $name );
		}

		/**
		 * Parse conditional logic of a field
		 *
		 * @param $field
		 */
		public static function parse_conditional_logic( $field ) {
			if ( empty( $field['visibility'] ) ) {
				return;
			}

			$conditional = $field['visibility'];
			if ( empty( $conditional['conditional'] ) ) {
				return;
			}

			$id = self::sanitize_name( $field['id'] );
			if ( empty( self::$conditional_logic[ $id ] ) ) {
				self::$conditional_logic[ $id ] = array(
					'state'          => ! empty( $conditional['state'] ) ? $conditional['state'] : 'show',
					'state_callback' => ! empty( $conditional['state_callback'] ) ? $conditional['state_callback'] : 'conditional_logic_gray_state',
					'conditional'    => array(),
				);
			}

			// If there is an indexed key consider the has more than one conditional field
			if ( array_key_exists( 0, $conditional['conditional'] ) ) {
				foreach ( $conditional['conditional'] as $conditional_field ) {
					self::$conditional_logic[ $id ]['conditional'][] = wp_parse_args(
						$conditional_field,
						array(
							'field'   => '',
							'compare' => '',
							'value'   => '',
						)
					);
				}
			} else {
				self::$conditional_logic[ $id ]['conditional'][] = wp_parse_args(
					$conditional['conditional'],
					array(
						'field'   => '',
						'compare' => '',
						'value'   => '',
					)
				);
			}
		}

		/**
		 * Search field class/path and include if it does not load.
		 * Return true when class is loaded, otherwise false.
		 *
		 * @param array $field
		 *
		 * @return bool
		 */
		public static function include_field( $field ) {
			$field = RWMB_Field::map_types( $field );

			if ( is_array( $field ) && ! empty( $field['type'] ) ) {
				$type = $field['type'];
			} else {
				$type = $field;
			}

			if ( empty( self::$types[ $type ] ) ) {
				$class = str_replace( ' ', '_', ucwords( preg_replace( '~[_|-]+~', ' ', $type ) ) );
				$class = "RWMB_{$class}_Field";

				if ( ! class_exists( $class ) ) {
					$file = LP_PLUGIN_PATH . '/inc/admin/meta-box/fields-v3/' . $type . '.php';
					if ( file_exists( $file ) ) {
						include_once $file;
					}
				}
				self::$types[ $type ] = $class;
			} else {
				$class = self::$types[ $type ];
			}

			return class_exists( $class ) ? $class : false;
		}

		/**
		 * Init hooks
		 */
		public static function init() {
			add_action( 'rwmb_before', array( __CLASS__, 'prepare_fields' ) );
			add_action( 'admin_footer', array( __CLASS__, 'output_data' ) );
			add_action( 'learn-press/meta-box-loaded', array( __CLASS__, 'load' ) );

			add_filter( 'rwmb_wrapper_html', array( __CLASS__, 'wrapper_html' ), 10, 3 );
			add_filter( 'rwmb_html', array( __CLASS__, 'begin_html' ), 10, 3 );
			add_filter( 'rwmb_field_meta', array( __CLASS__, 'field_meta' ), 10, 3 );

			if ( ! class_exists( 'RW_Meta_Box' ) ) {
				require_once LP_PLUGIN_PATH . 'inc/libraries/meta-box/meta-box.php';
			}
		}

		public static function begin_html( $html, $field, $meta ) {
			return $html . ( ! empty( $field['after_input'] ) ? $field['after_input'] : '' );
		}

		public static function field_meta( $meta, $field, $saved ) {
			if ( array_key_exists( 'saved', $field ) ) {
				$meta = $field['saved'];
			}

			return $meta;
		}

		/**
		 * Load a fake meta box instance
		 */
		public static function load() {
			include_once 'class-lp-meta-box.php';

			new LP_Meta_Box(
				array(
					'id'     => 'fake_metabox',
					'title'  => '',
					'fields' => array(),
				)
			);
		}

		/**
		 * Prepare conditional logic fields.
		 *
		 * @hook rwmb_before
		 *
		 * @param RW_Meta_Box $box
		 */
		public static function prepare_fields( $box ) {
			$fields = $box->fields;

			if ( $fields ) {
				foreach ( $fields as $field ) {
					self::parse_conditional_logic( $field );
				}
			}
		}

		/**
		 * Output ID of the field into a hidden field for js.
		 *
		 * @hook rwmb_wrapper_html
		 *
		 * @param $begin
		 * @param $field
		 * @param $meta
		 *
		 * @return string
		 */
		public static function wrapper_html( $begin, $field, $meta ) {
			return $begin . '<input type="hidden" class="rwmb-field-name" value="' . self::sanitize_name( $field['id'] ) . '" /><div class="field-overlay"></div>';
		}

		/**
		 * Output conditional logic fields for js.
		 *
		 * @hook admin_footer
		 */
		public static function output_data() {
			if ( ! self::$conditional_logic ) {
				return;
			}

			foreach ( self::$conditional_logic as $id => $conditional ) {
				foreach ( $conditional['conditional'] as $k => $field ) {
					self::$conditional_logic[ $id ]['conditional'][ $k ]['field'] = self::sanitize_name( $field['field'] );
				}
			}

			$min = learn_press_is_debug() ? '' : '.min';

			wp_enqueue_script( 'lp-conditional-logic', LP()->plugin_url( 'assets/js/admin/conditional-logic' . $min . '.js' ) );
			wp_localize_script( 'lp-conditional-logic', 'lp_conditional_logic', self::$conditional_logic );
		}

		/**
		 * Use for Type: custom_fields in LP4.
		 *
		 * @param [type] $value
		 * @param [type] $values
		 * @param [type] $key
		 * @return void
		 */
		public static function lp_metabox_custom_fields( $value, $values, $key ) {
			?>
			<tr>
				<td class="sort">
					<input class="count" type="hidden" value="<?php echo $key; ?>" name="<?php echo esc_attr( $value['id'] ) . '[' . $key . ']' . '[sort]'; ?>">
				</td>
				<?php
				if ( $value['options'] ) {
					foreach ( $value['options'] as $cfk => $val ) {
						$name = $value['id'] . '[' . $key . ']' . '[' . $cfk . ']';

						switch ( $val['type'] ) {
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
								?>
								<td>
									<input name="<?php echo esc_attr( $name ); ?>" type="<?php echo $val['type']; ?>" class="input-text" placeholder="<?php echo isset( $val['placeholder'] ) ? $val['placeholder'] : ''; ?>" value="<?php echo ! empty( $values[ $cfk ] ) ? $values[ $cfk ] : ''; ?>">
								</td>
								<?php
								break;

							case 'select':
								?>
								<td>
									<select name="<?php echo esc_attr( $name ); ?>">
										<?php
										if ( isset( $val['options'] ) ) {
											foreach ( $val['options'] as $cfks => $cfselect ) {
												?>
												<option value="<?php echo $cfks; ?>" <?php echo ! empty( $values[ $cfk ] ) ? selected( $values[ $cfk ], (string) $cfks ) : ''; ?>><?php echo $cfselect; ?></option>
												<?php
											}
										}
										?>
									</select>
								</td>
								<?php
								break;

							case 'checkbox':
								?>
								<td>
									<input name="<?php echo esc_attr( $name ); ?>" type="checkbox" name="" value="1" <?php echo ! empty( $values[ $cfk ] ) ? checked( $values[ $cfk ], 'yes' ) : ''; ?>>
								</td>
								<?php
								break;
						}
					}
				}
				?>
				<td width="2%"><a href="#" class="delete"></a></td>
			</tr>
			<?php
		}
	}

	LP_Meta_Box_Helper::init();
}
