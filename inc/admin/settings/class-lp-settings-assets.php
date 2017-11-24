<?php

/**
 * Class LP_Settings_Profile
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes/Settings
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Settings_Assets extends LP_Abstract_Settings_Page {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id   = 'assets';
		$this->text = __( 'Assets', 'learnpress' );

		parent::__construct();

		add_action( 'learn-press/update-settings/updated', array( $this, 'update' ) );
	}

	public function update() {
		if ( ! empty( $_REQUEST['color_schema'] ) ) {
			update_option( 'learn_press_color_schemas', $_REQUEST['color_schema'] );
		}
	}

	public function output() {
		$view = learn_press_get_admin_view( 'settings/profile.php' );
		include_once $view;
	}

	/**
	 * Return fields for asset settings.
	 *
	 * @param string $section
	 * @param string $tab
	 *
	 * @return mixed
	 */
	public function get_settings( $section = '', $tab = '' ) {
		return apply_filters(
			'learn_press_profile_settings',
			array(
				array(
					'title'   => __( 'Enable custom colors', 'learnpress' ),
					'id'      => 'enable_custom_colors',
					'default' => 'no',
					'type'    => 'yes-no',
					'desc'    => __( 'Use color schema for main colors.', 'learnpress' )
				),
				array(
					'title'   => __( 'Color schema', 'learnpress' ),
					'id'      => 'color_schema',
					'default' => '',
					'type'    => 'color-schema'
				),
				array(
					'title'   => __( 'Load css', 'learnpress' ),
					'id'      => 'load_css',
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => __( 'Load default stylesheet for LearnPress.', 'learnpress' )
				),
				array(
					'title'   => __( 'Debug mode', 'learnpress' ),
					'id'      => 'debug',
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => __( 'Turn on/off debug mode for developer.', 'learnpress' )
				),
			)
		);
	}
}

return new LP_Settings_Assets();