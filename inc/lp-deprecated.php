<?php
/**
 * Handle renamed/removed hooks
 *
 */
global $lp_map_deprecated_filters;

$lp_map_deprecated_filters = array(
	'learn_press_register_add_ons' => 'learn_press_loaded'
);

foreach ( $lp_map_deprecated_filters as $old => $new ) {
	add_filter( $old, 'lp_deprecated_filter_mapping', 9999999 );
}

function lp_deprecated_filter_mapping( $data, $arg_1 = '', $arg_2 = '', $arg_3 = '' ) {
	global $lp_map_deprecated_filters, $wp_filter;
	$filter = current_filter();
	if ( !empty( $wp_filter[$filter] ) && count( $wp_filter[$filter] ) > 1 ) {
		_deprecated_function( 'The ' . $filter . ' hook', '1.0', $lp_map_deprecated_filters[$filter] );
	}

	return $data;
}