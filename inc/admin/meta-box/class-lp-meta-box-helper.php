<?php

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
	 * @param $fields
	 */
	public static function render_fields( $fields ) {

		foreach ( $fields as $field ) {
			$origin_id = $field['id'];

			LP_Meta_Box_Helper::show_field( $field );
		}
	}

	/**
	 * Show field
	 *
	 * @param $field
	 */
	public static function show_field( $field ) {
		if ( ! class_exists( 'RW_Meta_Box' ) ) {
			require_once LP_PLUGIN_PATH . 'inc/libraries/meta-box/meta-box.php';
		}
		$fields = RW_Meta_Box::normalize_fields( array( $field ) );
		$field  = $fields[0];
		if ( self::include_field( $field ) ) {
			self::parse_conditional_logic( $field );
			$field['name']       = apply_filters( 'learn-press/meta-box/field-name', $field['title'], $field );
			$field['field_name'] = apply_filters( 'learn-press/meta-box/field-field_name', $field['id'], $field );
			$field['id'] = apply_filters( 'learn-press/meta-box/field-id', $field['id'], $field );
			//$field['value']      = md5( $field['std'] );
			// Try to include extended fields if they are not loaded before rendering.
			RWMB_Field::call( 'show', $field, true, 0 );
		}
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

		return class_exists( $class );
	}

	public static function init() {
		//add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'rwmb_wrapper_html', array( __CLASS__, 'wrapper_html' ), 10, 3 );

		add_action( 'admin_footer', array( __CLASS__, 'output_data' ) );
	}

	public static function wrapper_html( $begin, $field, $meta ) {
		return $begin . '<input type="hidden" class="rwmb-field-name" value="' . self::sanitize_name( $field['id'] ) . '" />';
	}

	public static function output_data() {
		if ( ! self::$conditional_logic ) {
			return;
		}
		foreach ( self::$conditional_logic as $id => $conditional ) {
			foreach ( $conditional['conditional'] as $k => $field ) {
				self::$conditional_logic[ $id ]['conditional'][ $k ]['field'] = self::sanitize_name( $field['field'] );
			}
		}
		wp_enqueue_script( 'lp-conditional-logic', LP()->plugin_url( 'assets/js/admin/conditional-logic.js' ) );
		wp_localize_script( 'lp-conditional-logic', 'lp_conditional_logic', self::$conditional_logic );
	}
}

LP_Meta_Box_Helper::init();