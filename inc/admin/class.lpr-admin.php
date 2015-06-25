<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'LPR_Admin' ) ) {
    /**
     * Class LPR_Admin
     */
	class LPR_Admin {
		/**
		 *  Constructor
		 */
		public function __construct() {
            // Common function used in admin
            require_once( dirname( __FILE__ ) . '/lpr-admin-functions.php' );

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
        function plugin_js_settings(){
            static $did = false;
            if( $did ) return;
            $js = array(
                'ajax' => admin_url( 'admin-ajax.php'),
                'plugin_url' => LearnPress()->plugin_url()
            );
            echo '<script type="text/javascript">var LearnPress_Settings = ' . json_encode( $js ) . '</script>';
            $did = true;
        }

        /**
         * Redirect to admin settings page
         */
        private function _redirect(){
            $page           = isset( $_GET['page'] ) ? $_GET['page'] : '';
            if( 'learn_press_settings' == $page ) {
                $current_tab = isset($_GET['tab']) ? $_GET['tab'] : '';
                $tabs = learn_press_settings_tabs_array();

                if (!$current_tab || ($tabs && empty($tabs[$current_tab]))) {
                    if ($tabs) {
                        $tab_keys = array_keys($tabs);
                        $current_tab = reset($tab_keys);
                        wp_redirect(admin_url('options-general.php?page=learn_press_settings&tab=' . $current_tab));
                        exit();
                    }
                }
            }
        }

		/**
		 * Include any classes, functions we need within admin
		 */
		public function includes() {
			//Ajax Class
			include_once( 'class-admin-ajax.php' );
		}

		/**
		 * Enqueue admin scripts
		 */
		public function admin_scripts() {

            /**
             * Tipsy tooltip assets
             */
            LPR_Admin_Assets::enqueue_style('jquery-tipsy', LPR_CSS_URL . 'tipsy.css' );
            LPR_Admin_Assets::enqueue_script('jquery-tipsy', LPR_JS_URL . 'jquery.tipsy.js' );
		}

	}

	new LPR_Admin;
}