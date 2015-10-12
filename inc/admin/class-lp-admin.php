<?php

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

			// redirect
			$this->_redirect();
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
				'plugin_url' => LearnPress()->plugin_url()
			);
			echo '<script type="text/javascript">var LearnPress_Settings = ' . json_encode( $js ) . '</script>';
			$did = true;
		}

		/**
		 * Redirect to admin settings page
		 */
		private function _redirect() {
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

			// Update LearnPress from 0.9.x to 1.0
			LP()->_include( 'updates/learnpress-update-1.0.php' );
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

	new LP_Admin;
}