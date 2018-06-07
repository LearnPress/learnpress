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

class LP_Settings_Advanced extends LP_Abstract_Settings_Page {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id   = 'advanced';
		$this->text = __( 'Advanced', 'learnpress' );

		parent::__construct();

		//add_action( 'learn-press/update-settings/updated', array( $this, 'update' ) );
	}

//	public function update() {
//		if ( ! empty( $_REQUEST['color_schema'] ) ) {
//			update_option( 'learn_press_color_schemas', $_REQUEST['color_schema'] );
//		}
//	}

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
					'type'    => 'yes-no',
					'desc'    => __( 'Load default stylesheet for LearnPress.', 'learnpress' )
				),
				array(
					'title'   => __( 'Debug mode', 'learnpress' ),
					'id'      => 'debug',
					'default' => 'yes',
					'type'    => 'yes-no',
					'desc'    => __( 'Turn on/off debug mode for developer.', 'learnpress' )
				),
				array(
					'title'   => __( 'Hard cache', 'learnpress' ),
					'type'    => 'heading',
				),
				array(
					'title'   => __( 'Enable hard cache', 'learnpress' ),
					'id'      => 'enable_hard_cache',
					'default' => 'no',
					'type'    => 'yes-no',
					'desc'    => sprintf( __( 'Enable cache for static content such as content and settings of course, lesson, quiz. <a href="%s">%s</a>', 'learnpress' ), admin_url('admin.php?page=learn-press-tools&tab=cache'), __('Advanced', 'learnpress'))
				),
				array(
					'title'   => __( 'Others', 'learnpress' ),
					'type'    => 'heading',
				),
				array(
					'title'   => __( 'Enable lesson video', 'learnpress' ),
					'id'      => 'enable_lesson_video',
					'default' => 'no',
					'type'    => 'yes-no',
					'desc'    => __( 'When this option is enabled, the first video embed in lesson content will be detected and move to the top.', 'learnpress' )
				),
			)
		);
	}
}

return new LP_Settings_Advanced();