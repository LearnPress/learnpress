<?php
/**
 * Plugin Name: Meta Box
 * Plugin URI: https://metabox.io
 * Description: Create custom meta boxes and custom fields in WordPress.
 * Version: 4.15.7
 * Author: MetaBox.io
 * Author URI: https://metabox.io
 * License: GPL2+
 * Text Domain: meta-box
 * Domain Path: /languages/
 *
 * @package Meta Box
 */

if ( defined( 'ABSPATH' ) && ! defined( 'RWMB_VER' ) ) {
	if ( ! defined( 'LP_METABOX_INC' ) ) {
		define( 'LP_METABOX_INC', LP_PLUGIN_PATH . 'inc/libraries/meta-box/inc/' );
	}
	if ( ! class_exists( 'RWMB_Field' ) ) {
		require_once LP_METABOX_INC . 'field.php';
	}

	if ( defined( 'RWMB_FIELDS_DIR' ) ) {
		// Field classes
		foreach ( glob( RWMB_FIELDS_DIR . '*.php' ) as $file ) {
			require_once $file;
		}
	}

	require_once dirname( __FILE__ ) . '/inc/loader.php';
	$loader = new RWMB_Loader();
	$loader->init();
}
// Field classes
foreach ( glob( LP_PLUGIN_PATH . 'inc/admin/meta-box/fields/*.php' ) as $file ) {
	require_once $file;
}

do_action('learn-press/meta-box-loaded');
