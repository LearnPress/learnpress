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
		if ( $settings ) {
			LP_Meta_Box_Helper::render_fields( $settings );
		} else {
			echo __( 'There is no settings.', 'learnpress' );
		}
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
	 * @param string $section
	 * @param string $tab
	 *
	 * @return bool|mixed
	 */
	public function get_settings( $section = '', $tab = '' ) {
		if ( is_callable( array( $this, 'get_settings_' . $section ) ) ) {
			return call_user_func( array( $this, 'get_settings_' . $section ) );
		}

		return false;
	}
}
