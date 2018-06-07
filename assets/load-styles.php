<?php

/**
 * Disable error reporting
 *
 * Set this to error_reporting( -1 ) for debugging
 */

include "header.php";

$wp_styles = new WP_Styles();

wp_default_styles( $wp_styles );
// Tell LP load default styles
LP_Assets::default_styles( $wp_styles );
if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) === $wp_version ) {
	$protocol = $_SERVER['SERVER_PROTOCOL'];
	if ( ! in_array( $protocol, array( 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0' ) ) ) {
		$protocol = 'HTTP/1.0';
	}
	//header( "$protocol 304 Not Modified" );
	//exit();
}
foreach ( $load as $handle ) {
	$handle = 'learn-press-' . $handle;
	if ( ! array_key_exists( $handle, $wp_styles->registered ) ) {
		continue;
	}

	$path = ABSPATH . $wp_styles->registered[ $handle ]->src;
	/**
	 * If debug mode is turn of but min file does not exists
	 * then, try to find file without .min inside
	 */
	if ( ! file_exists( $path ) ) {
		$path = preg_replace( '~\.min.~', '.', $path );
	}
	$content = get_file( $path ) . "\n";
	if ( strpos( $path, '/learnpress/assets/' ) !== false ) {
		$content = str_replace( '../../images/', 'images/', $content );
		$content = str_replace( '../fonts/', 'fonts/', $content );
		$out     .= $content;
	} else {
		$out .= $content;
	}

}
header( "Etag: $wp_version" );
header( 'Content-Type: text/css; charset=UTF-8' );
header( 'Expires: ' . gmdate( "D, d M Y H:i:s", time() + $expires_offset ) . ' GMT' );
header( "Cache-Control: public, max-age=$expires_offset" );

if ( $compress && ! ini_get( 'zlib.output_compression' ) && 'ob_gzhandler' != ini_get( 'output_handler' ) && isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) {
	header( 'Vary: Accept-Encoding' ); // Handle proxies
	if ( false !== stripos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate' ) && function_exists( 'gzdeflate' ) && ! $force_gzip ) {
		header( 'Content-Encoding: deflate' );
		$out = gzdeflate( $out, 3 );
	} elseif ( false !== stripos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' ) && function_exists( 'gzencode' ) ) {
		header( 'Content-Encoding: gzip' );
		$out = gzencode( $out, 3 );
	}
}

echo $out;
exit;
