<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Register bbPress addon
 */
function learn_press_register_bbpress() {
	if ( ! learn_press_bbpress_is_active() ) {
		$m = __( '<br /><small>bbPress is not installed/activated. Please install/active it to use this addon.</small>' );
	}else{
		$m = null;
	}
	$bbpress = array(
		'bbpress-add-on' => array(
			'name'              => __( 'bbPress Integration', 'learn_press' ),
			'description'       => sprintf( __( 'Using the forum for courses provided by bbPress.%s', 'learn_press' ), $m ),
			'author'            => 'foobla',
			'author_url'        => 'http://thimpress.com',
			'file'              => LPR_PLUGIN_PATH . '/inc/core-addons/bbpress/bbp-courses.php',
			'category'          => 'courses',
			'tag'               => 'core',
			'settings-callback' => '',
		)
	);
	$bbpress = apply_filters( 'learn_press_bbpress', $bbpress );
	learn_press_addon_register( 'bbpress-add-on', $bbpress['bbpress-add-on'] );
}
add_action( 'learn_press_register_add_ons', 'learn_press_register_bbpress' );