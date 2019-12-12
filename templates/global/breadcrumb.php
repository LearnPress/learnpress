<?php
/**
 * Template for displaying archive courses breadcrumb.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/breadcrumb.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php

if ( ! empty( $breadcrumb ) ) {

	echo $wrap_before;

	foreach ( $breadcrumb as $key => $crumb ) {

		echo $before;

		echo '<li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">';
		if ( ! empty( $crumb[1] ) && sizeof( $breadcrumb ) !== $key + 1 ) {
			echo '<a href="' . esc_url( $crumb[1] ) . '" itemprop="item"><span itemprop="name">' . esc_html( $crumb[0] ) . '</span></a>';
		} else {
			echo '<span itemprop="name">'.esc_html( $crumb[0] ).'</span>';
		}
		echo '<meta itemprop="position" content="1" />';
		echo '</li>';


		echo $after;

		if ( sizeof( $breadcrumb ) !== $key + 1 ) {
			echo $delimiter;
		}

	}

	echo $wrap_after;

}
