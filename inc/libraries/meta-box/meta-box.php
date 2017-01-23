<?php
/*
Plugin Name: Meta Box
Plugin URI: http://www.deluxeblogtips.com/meta-box
Description: Create meta box for editing pages in WordPress. Compatible with custom post types since WP 3.0
Version: 4.4.1
Author: Rilwis
Author URI: http://www.deluxeblogtips.com
License: GPL2+
*/

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( !defined( 'RWMB_VER' ) ) {

	// Script version, used to add version for scripts and styles
	define( 'RWMB_VER', '4.4.1' );

	// Define plugin URLs, for fast enqueuing scripts and styles
	if ( !defined( 'RWMB_URL' ) ) {
		define( 'RWMB_URL', plugin_dir_url( __FILE__ ) );
	}
	define( 'RWMB_JS_URL', trailingslashit( RWMB_URL . 'js' ) );
	define( 'RWMB_CSS_URL', trailingslashit( RWMB_URL . 'css' ) );

	// Plugin paths, for including files
	if ( !defined( 'RWMB_DIR' ) ) {
		define( 'RWMB_DIR', plugin_dir_path( __FILE__ ) );
	}
	define( 'RWMB_INC_DIR', trailingslashit( RWMB_DIR . 'inc' ) );
	define( 'RWMB_FIELDS_DIR', trailingslashit( RWMB_INC_DIR . 'fields' ) );

	// Optimize code for loading plugin files ONLY on admin side
	// @see http://www.deluxeblogtips.com/?p=345

	// Helper function to retrieve meta value
	require_once RWMB_INC_DIR . 'helpers.php';

}
if ( !defined( 'LP_METABOX_INC' ) ) {
	define( 'LP_METABOX_INC', LP_PLUGIN_PATH . 'inc/libraries/meta-box/inc/' );
}

if ( is_admin() ) {
	//require_once RWMB_INC_DIR . 'common.php';
	if ( !class_exists( 'RWMB_Field' ) ) {
		require_once LP_METABOX_INC . 'field.php';
	}
	// Field classes
	foreach ( glob( RWMB_FIELDS_DIR . '*.php' ) as $file ) {
		require_once $file;
	}

	// Main file
	require_once LP_METABOX_INC . 'meta-box.php';
	if ( !function_exists( 'rwmb_register_meta_boxes' ) ) {
		require_once LP_METABOX_INC . 'init.php';
	}
}
do_action( 'learn_press_meta_box_loaded' );
