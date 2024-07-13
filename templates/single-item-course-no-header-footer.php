<?php
/**
 * Template display single item of Course without display Header, Footer
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0.0
 * @since 4.2.6.9
 */

defined( 'ABSPATH' ) || exit;

if ( ! wp_is_block_theme() ) {
	get_header();
}
/**
 * Layout
 *
 * @see SingleItemCourseTemplate::sections_no_header_footer()
 */
do_action( 'learn-press/single-item-of-course/layout-no-header-footer' );

if ( ! wp_is_block_theme() ) {
	get_footer();
}
