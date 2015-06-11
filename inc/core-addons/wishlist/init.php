<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Register wishlist addon
 */
function learn_press_register_wishlist() {

	$wishlist = array(
		'wishlist-add-on' => array(
			'name'              => __( 'Courses Wishlist', 'learn_press' ),
			'description'       => __( 'Wishlist feature', 'learn_press' ),
			'author'            => 'foobla',
			'author_url'        => 'http://thimpress.com',
			'file'              => LPR_PLUGIN_PATH . '/inc/core-addons/wishlist/wishlist.php',
			'category'          => 'courses',
			'tag'               => 'core',
			'settings-callback' => '',
		)
	);
	$wishlist = apply_filters( 'learn_press_wishlist', $wishlist );
	learn_press_addon_register( 'wishlist-add-on', $wishlist['wishlist-add-on'] );
}
add_action( 'learn_press_register_add_ons', 'learn_press_register_wishlist' );