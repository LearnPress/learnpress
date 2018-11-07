<?php
/**
 * Disable error reporting
 *
 * Set this to error_reporting( -1 ) for debugging.
 */
include "header.php";

$wp_scripts     = new WP_Scripts();

// Tell WP Core load their default scripts
wp_default_scripts( $wp_scripts );
// Tell LP load default scripts
LP_Assets::default_scripts( $wp_scripts );
foreach ( $load as $handle ) {
	$handle = 'learn-press-' . $handle;
	if ( !array_key_exists( $handle, $wp_scripts->registered ) )
		continue;
	$path = ABSPATH . $wp_scripts->registered[$handle]->src;
	/**
	 * If debug mode is turn of but min file does not exists
	 * then, try to find file without .min inside
	 */
	if ( !file_exists( $path ) ) {
		$path = preg_replace( '~\.min.~', '.', $path );
	}
	//
	$out .= get_file( $path ) . ";\n";
}
header( 'Content-Type: application/javascript; charset=UTF-8' );
header( 'Expires: ' . gmdate( "D, d M Y H:i:s", time() + $expires_offset ) . ' GMT' );
header( "Cache-Control: public, max-age=$expires_offset" );

if ( $compress && !ini_get( 'zlib.output_compression' ) && 'ob_gzhandler' != ini_get( 'output_handler' ) && isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) {
	header( 'Vary: Accept-Encoding' ); // Handle proxies
	if ( false !== stripos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate' ) && function_exists( 'gzdeflate' ) && !$force_gzip ) {
		header( 'Content-Encoding: deflate' );
		$out = gzdeflate( $out, 3 );
	} elseif ( false !== stripos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' ) && function_exists( 'gzencode' ) ) {
		header( 'Content-Encoding: gzip' );
		$out = gzencode( $out, 3 );
	}
}

echo $out;
exit;
