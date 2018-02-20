<?php
/**
 * Search regexp \'[a-z]+ - [a-z]+\'
 */

/**
 * use http://example.com?debug=yes to execute the code in this file
 */

add_filter( 'wp_head', function ( $url ) {
	global $wp, $wp_rewrite;
	flush_rewrite_rules();
	learn_press_debug($wp, $wp_rewrite);
} );

