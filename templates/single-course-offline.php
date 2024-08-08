<?php
/**
 * Template for displaying content of single course offline.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Header for page
 */
if ( ! wp_is_block_theme() ) {
	do_action( 'learn-press/template-header' );
}

echo 'ofliine';

/**
 * Footer for page
 */
if ( ! wp_is_block_theme() ) {
	do_action( 'learn-press/template-footer' );
}
