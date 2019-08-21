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

		add_action( 'learn-press/update-settings/updated', array( $this, 'update' ) );
	}

	public function update() {
		$pages = array( 'frontend', 'admin' );
		foreach ( $pages as $page ) {
			$key               = "{$page}_libraries";
			$exclude_libraries = ! empty( $_REQUEST[ $key ] ) ? array_keys( $_REQUEST[ $key ] ) : '';

			if ( ! $exclude_libraries || ! sizeof( $exclude_libraries ) ) {
				delete_option( 'learn_press_exclude_' . $key );
			} else {
				update_option( 'learn_press_exclude_' . $key, $exclude_libraries );
			}

			call_user_func( array( $this, "build_{$page}_assets" ), $exclude_libraries );
		}
	}

	protected function get_upload_path() {
		$uploadDir = wp_upload_dir();
		$uploadDir = $uploadDir['basedir'] . '/learnpress';
		@mkdir( $uploadDir, '0777', true );

		return $uploadDir;
	}

	public function build_admin_assets( $exclude_libraries = array() ) {

		$writeDir = $this->get_upload_path();

		if ( ! $exclude_libraries || ! sizeof( $exclude_libraries ) ) {
			@unlink( $writeDir . '/admin.plugins.all.js' );
			@unlink( $writeDir . '/admin.bundle.min.css' );

			return;
		}

		$js = array(
			'vue'           => 'assets/js/vendor/vue/vue',
			'vuex'          => 'assets/js/vendor/vue/vuex',
			'vue-resource'  => 'assets/js/vendor/vue/vue-resource',
			'vue-draggable' => 'assets/js/vendor/vue/vue-draggable',

			'chartjs'      => 'assets/js/vendor/chart.min',
			'jquery-tipsy' => 'assets/js/vendor/jquery/jquery.tipsy',
		);

		$js_code = array();
		foreach ( $js as $k => $v ) {
			if ( in_array( $k, $exclude_libraries ) ) {
				continue;
			}

			$file      = LP_PLUGIN_PATH . '/' . $v;
			$js_code[] = "/***** {$k}.js *****/";
			$js_code[] = file_exists( "{$file}.min.js" ) ? file_get_contents( "{$file}.min.js" ) : file_get_contents( "{$file}.js" );
		}

		if ( sizeof( $js_code ) ) {
			@mkdir( $writeDir, '0777', true );
			file_put_contents( $writeDir . '/admin.plugins.all.js', join( "\n", $js_code ) );
		}

		$css = array(
			'font-awesome' => 'assets/css/vendor/font-awesome.min',
			'jquery-tipsy' => '/assets/css/vendor/jquery.tipsy',
		);

		$css_code = array();
		foreach ( $css as $k => $v ) {
			if ( in_array( $k, $exclude_libraries ) ) {
				continue;
			}

			$file       = LP_PLUGIN_PATH . '/' . $v;
			$js_code[]  = "/***** {$k}.css *****/";
			$css_code[] = file_exists( "{$file}.css" ) ? file_get_contents( "{$file}.css" ) : '';
		}

		if ( sizeof( $css_code ) ) {
			file_put_contents( $writeDir . '/admin.bundle.min.css', join( "\n", $css_code ) );
		}
	}


	public function build_frontend_assets( $exclude_libraries = array() ) {
		$writeDir = $this->get_upload_path();

		if ( ! $exclude_libraries || ! sizeof( $exclude_libraries ) ) {
			@unlink( $writeDir . '/plugins.all.js' );
			@unlink( $writeDir . '/bundle.min.css' );

			return;
		}

		$js = array(
			'vue'          => 'assets/js/vendor/vue/vue',
			'vuex'         => 'assets/js/vendor/vue/vuex',
			'vue-resource' => 'assets/js/vendor/vue/vue-resource',

			'jquery-alert'     => 'assets/js/vendor/jquery/jquery-alert',
			'jquery-appear'    => 'assets/js/vendor/jquery/jquery-appear',
			'jquery-scrollto'  => 'assets/js/vendor/jquery/jquery-scrollTo',
			'jquery-scrollbar' => 'assets/js/vendor/jquery/jquery.scrollbar',
			'jquery-tipsy'     => 'assets/js/vendor/jquery/jquery.tipsy',
			'jquery-timer'     => 'assets/js/vendor/jquery/jquery-timer',
			'watch'            => 'assets/js/vendor/watch',
		);

		$js_code = array();
		foreach ( $js as $k => $v ) {
			if ( in_array( $k, $exclude_libraries ) ) {
				continue;
			}

			$file      = LP_PLUGIN_PATH . '/' . $v;
			$js_code[] = "/***** {$k}.js *****/";
			$js_code[] = file_exists( "{$file}.min.js" ) ? file_get_contents( "{$file}.min.js" ) : file_get_contents( "{$file}.js" );
		}

		if ( $js_code ) {
			file_put_contents( $writeDir . '/plugins.all.js', join( "\n", $js_code ) );
		}

		$css = array(
			'font-awesome'     => 'assets/css/vendor/font-awesome.min',
			'jquery-scrollbar' => '/assets/css/vendor/jquery.scrollbar.css',
			'jquery-tipsy'     => '/assets/css/vendor/jquery.tipsy.css',
			'jalert'           => 'assets/css/vendor/jalert',
		);

		$css_code = array();
		foreach ( $css as $k => $v ) {
			if ( in_array( $k, $exclude_libraries ) ) {
				continue;
			}

			$file       = LP_PLUGIN_PATH . '/' . $v;
			$js_code[]  = "/***** {$k}.css *****/";
			$css_code[] = file_exists( "{$file}.css" ) ? file_get_contents( "{$file}.css" ) : '';
		}

		if ( $css_code ) {
			file_put_contents( $writeDir . '/bundle.min.css', join( "\n", $css_code ) );
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
					'title' => __( 'JS and CSS libraries', 'learnpress' ),
					'id'    => 'js_css_libraries',
					'type'  => 'html',
					'html'  => learn_press_admin_view_content( 'settings-js-css' ),
					'desc'  => __( 'Check the checkboxes to disable js/css from LearnPress (It must be loaded in other plugins or theme).', 'learnpress' )
				),
				array(
					'title' => __( 'Hard cache', 'learnpress' ),
					'type'  => 'heading',
				),
				array(
					'title'   => __( 'Enable hard cache', 'learnpress' ),
					'id'      => 'enable_hard_cache',
					'default' => 'no',
					'type'    => 'yes-no',
					'desc'    => sprintf( __( 'Enable cache for static content such as content and settings of course, lesson, quiz. <a href="%s">%s</a>', 'learnpress' ), admin_url( 'admin.php?page=learn-press-tools&tab=cache' ), __( 'Advanced', 'learnpress' ) )
				),
				array(
					'title' => __( 'Others', 'learnpress' ),
					'type'  => 'heading',
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