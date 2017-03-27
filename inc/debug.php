<?php
/**
 * Search regexp \'[a-z]+ - [a-z]+\'
 */

/**
 * use http://example.com?debug=yes to execute the code in this file
 */
function log_query() {
	global $wpdb;
	foreach ( $wpdb->queries as $i => $q ) {
		echo "\n==============" . $i . "===============";
		echo "\n" . $q[0];
		$t = explode( ",", $q[2] );
		echo "\n" . end( $t );
		echo "\n=======================================\n";
	}
}

//add_filter( 'wp_head', 'log_query' );
add_filter( 'wp_redirect', function ( $url ) {

	$count = learn_press_get_request( 'count', 0 );
	if(!$count){
		LP_Debug::instance()->add( 'xxxxxxx', 'log' . $count, true );
	}
	$count ++;
	if ( $count > 5 ) {
		die();
	}
	LP_Debug::instance()->add( $url, 'log' . $count );
	LP_Debug::instance()->add( debug_backtrace(), 'log' . $count );
	$url = add_query_arg( 'count', $count, $url );
	return $url;
} );

