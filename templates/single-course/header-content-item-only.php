<?php
/**
 * Add signal <!-- LEARN-PRESS-REMOVE-UNWANTED-PARTS --> into footer
 * before calling footer in order to remove unwanted sections
 */
function learn_press_footer_content_item_only() {
	echo '<!-- LEARN-PRESS-REMOVE-UNWANTED-PARTS -->';
	/**
	 * Added in 2.0.5 to fix issue with some server does not
	 * output the header
	 */
	remove_action( 'wp_footer', 'learn_press_footer_content_item_only', - 1000 );
}
add_action( 'wp_footer', 'learn_press_footer_content_item_only', - 1000 );

/**
 * Add 'content-item-only' to body's classes
 *
 * @param $classes
 *
 * @return array
 */
function learn_press_footer_content_item_only_body_class( $classes ) {
	$classes[] = 'content-item-only';
	return $classes;
}
add_filter( 'body_class', 'learn_press_footer_content_item_only_body_class' );

ob_start();
get_header();
$header = ob_get_clean();

// Get start tag of <body .*>
preg_match( '!(<body.*>)!', $header, $matches );

// Split and remove all section after <body />
$header_parts = preg_split( '!(<body.*>)!', $header );

// Output header with unwanted sections has removed
echo $header_parts[0] . $matches[0];