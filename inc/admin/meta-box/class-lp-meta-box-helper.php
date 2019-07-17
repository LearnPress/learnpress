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

		/**
		 * @param $fields
		 */
		public static function render_fields( $fields ) {
			foreach ( $fields as $field ) {
				// except heading options
				if ( isset( $field['id'] ) ) {
					$origin_id = $field['id'];
				}

				LP_Meta_Box_Helper::show_field( $field );
			}
		}

		/**
		 * Show field
		 *
		 * @param $field
		 */
		public static function show_field( $field ) {
			$fields = RW_Meta_Box::normalize_fields( array( $field ) );
			$field  = $fields[0];
			if ( $class_name = self::include_field( $field ) ) {
				self::parse_conditional_logic( $field );

				$field_title = self::get_field_title( $field );

				// Add the "asterisk" to required field
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
						$output = preg_replace( '/class="(.*)"/iSU', $class, $output );
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
					'conditional'    => array()
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
							'value'   => ''
						)
					);
				}
			} else {
				self::$conditional_logic[ $id ]['conditional'][] = wp_parse_args(
					$conditional['conditional'],
					array(
						'field'   => '',
						'compare' => '',
						'value'   => ''
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
					$file = LP_PLUGIN_PATH . '/inc/admin/meta-box/fields/' . $type . '.php';
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
			//add_filter( 'rwmb_outer_html', array( __CLASS__, 'outer_html' ), 10, 3 );
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
					'fields' => array()
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
			if ( $fields = $box->fields ) {
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

			// Enqueue js and localize settings.
			wp_enqueue_script( 'lp-conditional-logic', LP()->plugin_url( 'assets/js/admin/conditional-logic' . $min . '.js' ) );
			wp_localize_script( 'lp-conditional-logic', 'lp_conditional_logic', self::$conditional_logic );
		}
	}

	// Init
	LP_Meta_Box_Helper::init();
}