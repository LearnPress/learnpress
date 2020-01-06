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

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
/**
 * @var string $wrap_before
 * @var string $wrap_after
 * @var string $before
 * @var string $after
 * @var string $delimiter
 */

if ( empty( $breadcrumb ) ) {
	return;
}
echo $wrap_before;

foreach ( $breadcrumb as $key => $crumb ) {

	echo $before;

	echo '<li>';
	if ( ! empty( $crumb[1] ) && sizeof( $breadcrumb ) !== $key + 1 ) {
		echo '<a href="' . esc_url( $crumb[1] ) . '"><span>' . esc_html( $crumb[0] ) . '</span></a>';
	} else {
		echo '<span>' . esc_html( $crumb[0] ) . '</span>';
	}
	echo '</li>';

	echo $after;

	if ( sizeof( $breadcrumb ) !== $key + 1 ) {
		echo $delimiter;
	}

}

echo $wrap_after;