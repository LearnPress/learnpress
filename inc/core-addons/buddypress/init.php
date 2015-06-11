<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Register BuddyPress addon
 */
function learn_press_register_buddypress() {
	if ( ! learn_press_buddypress_is_active() ) {
		$m = __( '<br /><small>BuddyPress is not installed/activated. Please install/active it to use this addon.</small>' );
	}else{
		$m = null;
	}
	$buddypress = array(
		'buddypress-add-on' => array(
			'name'              => __( 'BuddyPress Integration', 'learn_press' ),
			'description'       => sprintf( __( 'Using the profile system provided by BuddyPress.%s', 'learn_press' ), $m ),
			'author'            => 'foobla',
			'author_url'        => 'http://thimpress.com',
			'file'              => LPR_PLUGIN_PATH . '/inc/core-addons/buddypress/bp-courses.php',
			'category'          => 'courses',
			'tag'               => 'core',
			'settings-callback' => '',
		)
	);
	$buddypress = apply_filters( 'learn_press_buddypress', $buddypress );
	learn_press_addon_register( 'buddypress-add-on', $buddypress['buddypress-add-on'] );
}
add_action( 'learn_press_register_add_ons', 'learn_press_register_buddypress' );