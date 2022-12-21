<?php
/**
 * Class LP_Abstract_Settings_Page
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

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
		parent::__construct();
	}

	/**
	 * Display admin page in LP4.
	 *
	 * @param string $section
	 * @param string $tab
	 * @version 4.0.0
	 */
	public function admin_page_settings( $section = null, $tab = '' ) {
		$settings = $this->get_settings( $section, $tab );
		$settings = $this->sanitize_settings( $settings );

		do_action( 'learn-press/settings-render' );

		if ( $settings ) {
			LP_Meta_Box_Helper::output_fields( $settings );
		} else {
			echo esc_html__( 'No settings available.', 'learnpress' );
		}
	}

	/**
	 * Save option in LP4.
	 *
	 * @param string $section
	 * @param string $tab
	 * @version 4.0.0
	 */
	public function save_settings( $section = null, $tab = '' ) {
		$settings = apply_filters( 'learn-press/admin/get-settings/admin-options-' . $section, $this->get_settings( $section, $tab ) );
		$settings = $this->sanitize_settings( $settings );

		if ( $settings ) {
			LP_Meta_Box_Helper::save_fields( $settings, $_POST );
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

	public function get_sections() {
		return array();
	}

	/**
	 * @param string $section
	 * @param string $tab
	 *
	 * @return bool|mixed
	 */
	public function get_settings( $section = '', $tab = '' ) {
		if ( ! $section ) {
			$section = $this->get_sections();
			$section = array_keys( $section );
		}

		settype( $section, 'array' );

		$return = array();

		foreach ( $section as $sec ) {
			if ( is_callable( array( $this, 'get_settings_' . $sec ) ) ) {
				$settings = call_user_func( array( $this, 'get_settings_' . $sec ) );

				if ( $settings ) {
					$return = array_merge( $return, $settings );
				}
			}
		}

		return $return;
	}
}
