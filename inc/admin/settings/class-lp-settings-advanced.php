<?php
/**
 * Class LP_Settings_Profile
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes/Settings
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

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
		$this->save_custom_css();

		return true;
	}

	protected function save_custom_css() {
		$colors = ! empty( $_POST['color_schema'] ) ? $_POST['color_schema'] : false;

		if ( ! $colors ) {
			return;
		}

		$colors = $colors[0];

		$old_colors = get_option( 'learn_press_color_schemas' );

		if ( $old_colors ) {
			$old_colors = $old_colors[0];
		} else {
			$old_colors = array();
		}

		$custom_css = get_option( '_lp_custom_css' );
		if ( $custom_css ) {
			$upload     = wp_upload_dir();
			$custom_css = $upload['basedir'] . '/learnpress/' . $custom_css;
			LP_WP_Filesystem::unlink( $custom_css );
			delete_option( '_lp_custom_css' );
		}

		if ( $this->is_default_colors( $colors ) ) {
			return false;
		}

		if ( ! class_exists( 'scssc' ) ) {
			include_once LP_PLUGIN_PATH . '/inc/libraries/scss.inc.php';
		}

		$scss = new scssc();
		$scss->setImportPaths( LP_PLUGIN_PATH . '/assets/scss' );

		$upload       = wp_upload_dir();
		$custom_css   = $upload['basedir'];
		$custom_file  = uniqid( 'lp-custom-css-' ) . '.css';
		$scss_content = LP_WP_Filesystem::get_contents( LP_PLUGIN_PATH . '/assets/scss/learnpress.scss' );
		$valid_colors = array();

		// Rename inline variables to apply the new variables in our settings.
		foreach ( $colors as $name => $value ) {
			if ( ! $value ) {
				continue;
			}

			$valid_colors[ $name ] = $value;
			$scss_content          = str_replace( '$' . $name . ':', '$' . $name . '-' . uniqid() . ':', $scss_content );
		}

		$scss->setVariables( $valid_colors );

		$css_content = $scss_content = $scss->compile( $scss_content );

		LP_WP_Filesystem::put_contents( $custom_css . '/learnpress/' . $custom_file, $css_content );

		update_option( '_lp_custom_css', $custom_file );

		return true;
	}

	/**
	 * Return TRUE if all colors passed is equals with default colors.
	 *
	 * @param $colors
	 *
	 * @return bool
	 */
	protected function is_default_colors( $colors ) {
		$color_schemas = learn_press_get_color_schemas();
		foreach ( $color_schemas as $field ) {
			if ( isset( $colors[ $field['id'] ] ) && $colors[ $field['id'] ] === $field['std'] ) {
				continue;
			}

			return false;
		}

		return true;
	}

	protected function get_upload_path() {
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'] . '/learnpress';
		@mkdir( $upload_dir, '0777', true );

		return $upload_dir;
	}

	public function build_admin_assets( $exclude_libraries = array() ) {
		$write_dir = $this->get_upload_path();

		if ( ! $exclude_libraries || ! sizeof( $exclude_libraries ) ) {
			LP_WP_Filesystem::unlink( $write_dir . '/admin.plugins.all.js' );
			LP_WP_Filesystem::unlink( $write_dir . '/admin.bundle.min.css' );

			return;
		}

		$js = array(
			'vue'           => 'assets/js/vendor/vue/vue',
			'vuex'          => 'assets/js/vendor/vue/vuex',
			'vue-resource'  => 'assets/js/vendor/vue/vue-resource',
			'vue-draggable' => 'assets/js/vendor/vue/vue-draggable',
			'chartjs'       => 'assets/js/vendor/chart.min',
			'jquery-tipsy'  => 'assets/js/vendor/jquery/jquery.tipsy',
		);

		$js_code = array();
		foreach ( $js as $k => $v ) {
			if ( in_array( $k, $exclude_libraries ) ) {
				continue;
			}

			$file      = LP_PLUGIN_PATH . '/' . $v;
			$js_code[] = "/***** {$k}.js *****/";
			$js_code[] = file_exists( "{$file}.min.js" ) ? LP_WP_Filesystem::get_contents( "{$file}.min.js" ) : LP_WP_Filesystem::get_contents( "{$file}.js" );
		}

		if ( sizeof( $js_code ) ) {
			LP_WP_Filesystem::put_contents( $write_dir . '/admin.plugins.all.js', join( "\n", $js_code ) );
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
			$css_code[] = file_exists( "{$file}.css" ) ? LP_WP_Filesystem::get_contents( "{$file}.css" ) : '';
		}

		if ( sizeof( $css_code ) ) {
			LP_WP_Filesystem::put_contents( $write_dir . '/admin.bundle.min.css', join( "\n", $css_code ) );
		}
	}


	public function build_frontend_assets( $exclude_libraries = array() ) {
		$write_dir = $this->get_upload_path();

		if ( ! $exclude_libraries || ! sizeof( $exclude_libraries ) ) {
			LP_WP_Filesystem::unlink( $write_dir . '/plugins.all.js' );
			LP_WP_Filesystem::unlink( $write_dir . '/bundle.min.css' );

			return;
		}

		$js = array(
			'vue'              => 'assets/js/vendor/vue/vue',
			'vuex'             => 'assets/js/vendor/vue/vuex',
			'vue-resource'     => 'assets/js/vendor/vue/vue-resource',
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
			$js_code[] = file_exists( "{$file}.min.js" ) ? LP_WP_Filesystem::get_contents( "{$file}.min.js" ) : LP_WP_Filesystem::get_contents( "{$file}.js" );
		}

		if ( $js_code ) {
			LP_WP_Filesystem::put_contents( $write_dir . '/plugins.all.js', join( "\n", $js_code ) );
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
			$css_code[] = file_exists( "{$file}.css" ) ? LP_WP_Filesystem::get_contents( "{$file}.css" ) : '';
		}

		if ( $css_code ) {
			LP_WP_Filesystem::put_contents( $write_dir . '/bundle.min.css', join( "\n", $css_code ) );
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
					'type'  => 'heading',
					'title' => esc_html__( 'Style', 'learnpress' ),
				),
				array(
					'title'   => esc_html__( 'Color Schema', 'learnpress' ),
					'id'      => 'color_schema',
					'default' => '',
					'type'    => 'color-schema',
				),
				array(
					'title'   => esc_html__( 'Load CSS', 'learnpress' ),
					'id'      => 'load_css',
					'default' => 'yes',
					'type'    => 'yes-no',
					'desc'    => esc_html__( 'Load default stylesheet for LearnPress.', 'learnpress' ),
				),
				array(
					'type'  => 'heading',
					'title' => esc_html__( 'Other', 'learnpress' ),
				),
				array(
					'title'   => esc_html__( 'Gutenberg', 'learnpress' ),
					'id'      => 'enable_gutenberg',
					'type'    => 'checkbox_list',
					'options' => array(
						LP_COURSE_CPT   => esc_html__( 'Course', 'learnpress' ),
						LP_LESSON_CPT   => esc_html__( 'Lesson', 'learnpress' ),
						LP_QUIZ_CPT     => esc_html__( 'Quiz', 'learnpress' ),
						LP_QUESTION_CPT => esc_html__( 'Question', 'learnpress' ),
					),
					'desc'    => esc_html__( 'Enable Gutenberg editor.', 'learnpress' ),
				),
				array(
					'title'   => esc_html__( 'Debug Mode', 'learnpress' ),
					'id'      => 'debug',
					'default' => 'no',
					'type'    => 'yes-no',
					'desc'    => esc_html__( 'Turn on/off debug mode for developer.', 'learnpress' ),
				),
				array(
					'title' => esc_html__( 'Script Libraries', 'learnpress' ),
					'id'    => 'js_css_libraries',
					'type'  => 'html',
					'html'  => learn_press_admin_view_content( 'settings-js-css' ),
					'desc'  => esc_html__( 'Check the checkboxes to disable js/css from LearnPress (It must be loaded in other plugins or theme).', 'learnpress' ),
				),
				array(
					'title'   => esc_html__( 'Hard Cache', 'learnpress' ),
					'id'      => 'enable_hard_cache',
					'default' => 'no',
					'type'    => 'yes-no',
					'desc'    => sprintf( __( 'Enable cache for static content such as content and settings of course, lesson, quiz. <a href="%1$s">%2$s</a>', 'learnpress' ), admin_url( 'admin.php?page=learn-press-tools&tab=cache' ), __( 'Advanced', 'learnpress' ) ),
				),
			)
		);
	}
}

return new LP_Settings_Advanced();
