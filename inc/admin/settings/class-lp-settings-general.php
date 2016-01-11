<?php

/**
 * Class LP_Settings_General
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Settings/Classes
 * @version 1.0
 */
class LP_Settings_General extends LP_Settings_Base {
	/**
	 * Construct
	 */
	function __construct() {
		$this->id   = 'general';
		$this->text = __( 'General', 'learn_press' );
		//add_action( 'learn_press_settings_general', array( $this, 'output' ) );
		//add_action( 'learn_press_settings_save_general', array( $this, 'save' ) );
		parent::__construct();
	}

	function output() {
		$view = learn_press_get_admin_view( 'settings/general.php' );
		include_once $view;
	}

	function get_settings() {
		return apply_filters(
			'learn_press_general_settings',
			array(
				array(
					'title'   => __( 'Instructors registration', 'learn_press' ),
					'desc'    => __( 'Create option for instructors registration.', 'learn_press' ),
					'id'      => $this->get_field_name( 'instructor_registration' ),
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'Auto update post name', 'learn_press' ),
					'desc'    => __( 'The post\'s name will update along with the title when changes title of lesson or quiz  in course curriculum or question in quiz<br />The permalink also is changed, therefore uncheck this if you don\'t want to change the permalink', 'learn_press' ),
					'id'      => $this->get_field_name( 'auto_update_post_name' ),
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'Currency', 'learn_press' ),
					'id'      => $this->get_field_name( 'currency' ),
					'default' => 'USD',
					'type'    => 'select',
					'options' => $this->_get_currency_options()
				),
				array(
					'title'   => __( 'Currency position', 'learn_press' ),
					'id'      => $this->get_field_name( 'currency_pos' ),
					'default' => 'left',
					'type'    => 'select',
					'options' => $this->_get_currency_positions()
				),
				array(
					'title'   => __( 'Thousands Separator', 'learn_press' ),
					'id'      => $this->get_field_name( 'thousands_separator' ),
					'default' => ',',
					'type'    => 'text',
					'options' => $this->_get_currency_positions()
				),
				array(
					'title'   => __( 'Decimals Separator', 'learn_press' ),
					'id'      => $this->get_field_name( 'decimals_separator' ),
					'default' => '.',
					'type'    => 'text',
					'options' => $this->_get_currency_positions()
				),
				array(
					'title'   => __( 'Number of Decimals', 'learn_press' ),
					'id'      => $this->get_field_name( 'number_of_decimals' ),
					'default' => '2',
					'type'    => 'text',
					'options' => $this->_get_currency_positions()
				),
				array(
					'title'   => __( 'Debug mode', 'learn_press' ),
					'id'      => $this->get_field_name( 'debug' ),
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => __( 'Turn on/off debug mode for developer', 'learn_press' )
				)
			)
		);
	}

	private function _get_currency_options() {
		$currencies = array();

		if ( $payment_currencies = learn_press_get_payment_currencies() )
			foreach ( $payment_currencies as $code => $symbol ) {
				$currencies[$code] = $symbol;
			}

		return $currencies;
	}

	private function _get_currency_positions() {
		$positions = array();
		foreach ( learn_press_currency_positions() as $pos => $text ) {
			switch ( $pos ) {
				case 'left':
					$text = sprintf( '%s ( %s%s )', $text, learn_press_get_currency_symbol(), '69.99' );
					break;
				case 'right':
					$text = sprintf( '%s ( %s%s )', $text, '69.99', learn_press_get_currency_symbol() );
					break;
				case 'left_with_space':
					$text = sprintf( '%s ( %s %s )', $text, learn_press_get_currency_symbol(), '69.99' );
					break;
				case 'right_with_space':
					$text = sprintf( '%s ( %s %s )', $text, '69.99', learn_press_get_currency_symbol() );
					break;
			}
			$positions[$pos] = $text;
		}
		return $positions;
	}

	/*function save() {
		$settings = LP_Admin_Settings::instance( 'general' );// $_POST['lpr_settings']['general'];
		$settings->bind( $_POST['learn_press'] );
		$settings->update();
	}*/
}

return new LP_Settings_General();