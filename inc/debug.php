<?php
/**
 * Search regexp \'[a-z]+ - [a-z]+\'
 */

/**
 * use http://example.com?debug=yes to execute the code in this file
 */

add_filter( 'wp_redirect', function ( $url ) {
	$count = learn_press_get_request( 'count', 0 );
	$count ++;
	if ( $count > 5 ) {
		die();
	}
	LP_Debug::instance()->add( $url, 'log' . $count );
	LP_Debug::instance()->add( debug_backtrace(), 'log' . $count );
	$url = add_query_arg( 'count', $count, $url );
	return $url;
} );

