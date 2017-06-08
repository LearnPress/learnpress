<?php
/**
 * Class LP_Abstract_Settings_Page
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Abstract_Settings_Page extends LP_Abstract_Settings {

	/**
	 * Tab's ID
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Tab's text
	 *
	 * @var string
	 */
	public $text = '';

	/**
	 * Constructor
	 */
	public function __construct() {

	}

	/**
	 * Display admin page.
	 *
	 * @param string $section
	 * @param string $tab
	 */
	public function admin_page( $section = null, $tab = '' ) {
		$settings = $this->get_settings( $section, $tab );
		$settings = $this->sanitize_settings( $settings );
		do_action( 'learn-press/settings-render' );
		LP_Meta_Box_Helper::render_fields( $settings );
	}


	/**
	 * Get name for field
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	public function get_field_name( $name ) {
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
	public function get_field_id( $name ) {
		return preg_replace( array( '!\[|(\]\[)!', '!\]!' ), array( '_', '' ), $this->get_field_name( $name ) );
	}

	/**
	 * Get settings fields
	 *
	 * @param null $section
	 * @param null $tab
	 *
	 * @return array
	 */
	public function get_settings( $section = null, $tab = null ) {
		return array();
	}

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
			$option_value = LP()->settings->get( preg_replace( '!^learn_press_!', '', $option_name ), null );
		}

		if ( is_array( $option_value ) ) {
			$option_value = array_map( 'stripslashes', $option_value );
		} elseif ( ! is_null( $option_value ) ) {
			$option_value = stripslashes( $option_value );
		}

		return $option_value === null ? $default : $option_value;
	}

}
