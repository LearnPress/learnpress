<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if ( !class_exists( 'LP_Admin_Assets' ) ) {
	/**
	 * Class LP_Admin_Assets
	 */
	class LP_Admin_Assets {

		// styles
		private static $styles = array();

		// scripts
		private static $scripts = array();

		// localize
		private static $wp_localize_scripts = array();

		static function init() {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );
			add_action( 'admin_print_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );
			add_action( 'admin_print_footer_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );

			self::add_localize(
				array(
					'confirm_delete' => __( 'Delete?', 'learn_press' )
				)
			);
		}

		/**
		 * register script
		 *
		 * @param string  $handle
		 * @param string  $src
		 * @param array   $deps
		 * @param string  $version
		 * @param boolean $in_footer
		 */
		static function add_script( $handle, $src, $deps = array( 'jquery' ), $version = LEARNPRESS_VERSION, $in_footer = true ) {
			self::$scripts[] = $handle;
			wp_register_script( $handle, $src, $deps, $version, $in_footer );
		}

		/**
		 * register style
		 *
		 * @param string $handle
		 * @param string $src
		 * @param array  $deps
		 * @param string $version
		 * @param string $media
		 */
		static function add_style( $handle, $src, $deps = array(), $version = LEARNPRESS_VERSION, $media = 'all' ) {
			self::$styles[] = $handle;
			wp_register_style( $handle, $src, $deps, $version, $media );
		}

		/**
		 * enqueue script
		 *
		 * @param string  $handle
		 * @param string  $src
		 * @param array   $deps
		 * @param string  $version
		 * @param boolean $in_footer
		 */
		static function enqueue_script( $handle, $src = '', $deps = array( 'jquery' ), $version = LEARNPRESS_VERSION, $in_footer = true ) {
			if ( !in_array( $handle, self::$scripts ) && $src ) {
				self::add_script( $handle, $src, $deps, $version, $in_footer );
			}
			wp_enqueue_script( $handle );
		}

		/**
		 * enqueue style
		 *
		 * @param string $handle
		 * @param string $src
		 * @param array  $deps
		 * @param string $version
		 * @param string $media
		 */
		static function enqueue_style( $handle, $src, $deps = array(), $version = LEARNPRESS_VERSION, $media = 'all' ) {
			if ( !in_array( $handle, self::$styles ) && $src ) {
				self::add_style( $handle, $src, $deps, $version, $media );
			}
			wp_enqueue_style( $handle );
		}

		/**
		 * add translate text
		 *
		 * @param array $localize
		 */
		static function add_localize( $key, $localize = null, $handle = 'learnpress-admin-js' ) {
			if ( is_array( $key ) ) {
				foreach ( $key as $k => $v ) {
					self::add_localize( $k, $v, $handle );
				}
			} elseif ( is_string( $key ) && strlen( $key ) ) {

				if ( !$handle ) $handle = 'learnpress-admin-js';

				if ( empty( self::$wp_localize_scripts[$handle] ) ) self::$wp_localize_scripts[$handle] = array();

				self::$wp_localize_scripts[$handle][$key] = $localize;

				//self::$wp_localize_scripts[$key] = $localize;
			}
		}

		private static function localize_script( $handle ) {
			$data = !empty( self::$wp_localize_scripts[$handle] ) ? self::$wp_localize_scripts[$handle] : false;
			if ( wp_script_is( $handle ) && $data ) {
				$name = str_replace( '-', '_', $handle ) . '_localize';
				unset( self::$wp_localize_scripts[$handle] );
				wp_localize_script( $handle, $name, apply_filters( $name, $data ) );
			}
		}

        /**
         * Load necessary styles + scripts for admin
         */
		static function load_scripts() {
			self::enqueue_style( 'learnpress-admin-css', LP_CSS_URL . 'learnpress-admin.css' );
			self::enqueue_script( 'learnpress-admin-js', LP_JS_URL . 'learnpress-admin.js' );
            self::enqueue_script( 'learnpress-block-ui', LP_JS_URL . 'jquery.block-ui.js' );
		}

        /**
         * Translate the text if needed
         */
		static function localize_printed_scripts() {
			if ( self::$scripts ) foreach ( self::$scripts as $handle ) {
				self::localize_script( $handle );
			}
		}
	}

}
LP_Admin_Assets::init();