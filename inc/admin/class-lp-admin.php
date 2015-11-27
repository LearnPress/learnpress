<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Classes
 * @version 1.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'LP_Admin' ) ) {
	/**
	 * Class LP_Admin
	 */
	class LP_Admin {
		/**
		 *  Constructor
		 */
		public function __construct() {

			$this->includes();


			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'admin_print_scripts', array( $this, 'plugin_js_settings' ) );
			add_action( 'init', array( $this, 'include_update' ) );
			add_action( 'template_redirect', array( $this, '_redirect' ) );

		}

		function include_update(){
			// Update LearnPress from 0.9.x to 1.0
			if ( version_compare( LP()->db_version, '1.0', '<' ) ) {
				learn_press_include( 'updates/learnpress-update-1.0.php' );
			}
		}

		/**
		 * Output common js settings in admin
		 *
		 * @since 0.9.4
		 */
		function plugin_js_settings() {
			static $did = false;
			if ( $did ) return;
			$js = array(
				'ajax'       => admin_url( 'admin-ajax.php' ),
				'plugin_url' => LearnPress()->plugin_url(),
				'siteurl' => home_url()
			);
			echo '<script type="text/javascript">var LearnPress_Settings = ' . json_encode( $js ) . '</script>';
			$did = true;
		}

		/**
		 * Redirect to admin settings page
		 */
		function _redirect() {
			$page = isset( $_GET['page'] ) ? $_GET['page'] : '';
			if ( 'learn_press_settings' == $page ) {
				$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';
				$tabs        = learn_press_settings_tabs_array();

				if ( !$current_tab || ( $tabs && empty( $tabs[$current_tab] ) ) ) {
					if ( $tabs ) {
						$tab_keys    = array_keys( $tabs );
						$current_tab = reset( $tab_keys );
						wp_redirect( admin_url( 'options-general.php?page=learn_press_settings&tab=' . $current_tab ) );
						exit();
					}
				}
			}
		}

		/**
		 * Include all classes and functions used for admin
		 */
		public function includes() {
			// Common function used in admin
			include_once( 'lp-admin-functions.php' );
			//Ajax Class
			include_once( 'class-lp-admin-ajax.php' );
			// Admin menu
			include_once( 'class-lp-admin-menu.php' );


		}

		/**
		 * Enqueue admin scripts
		 */
		public function admin_scripts() {

			/**
			 * Tipsy tooltip assets
			 */
			LP_Admin_Assets::enqueue_style( 'jquery-tipsy', LP_CSS_URL . 'tipsy.css' );
			LP_Admin_Assets::enqueue_script( 'jquery-tipsy', LP_JS_URL . 'jquery.tipsy.js' );
		}

	}
	function learn_press_load_admin(){

	}

	new LP_Admin();
}
add_action( 'plugins_loaded', 'learn_press_load_admin' );