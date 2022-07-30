<?php
/**
 * Template for displaying archive courses breadcrumb.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/breadcrumb.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( empty( $breadcrumb ) ) {
	return;
}
echo wp_kses_post( $wrap_before );

foreach ( $breadcrumb as $key => $crumb ) {

	echo wp_kses_post( $before );

	echo '<li>';

	if ( ! empty( $crumb[1] ) && sizeof( $breadcrumb ) !== $key + 1 ) {
		echo '<a href="' . esc_url_raw( $crumb[1] ) . '"><span>' . esc_html( $crumb[0] ) . '</span></a>';
	} else {
		echo '<span>' . esc_html( $crumb[0] ) . '</span>';
	}

	echo '</li>';

	echo wp_kses_post( $after );

	if ( sizeof( $breadcrumb ) !== $key + 1 ) {
		echo wp_kses_post( $delimiter );
	}
}

echo wp_kses_post( $wrap_after );
