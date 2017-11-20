<?php
/**
 * Template for displaying categories of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/categories.php.
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

<?php $term_list = get_the_term_list( get_the_ID(), 'course_category', '', ', ', '' ); ?>

<?php if ( $term_list ) {
	printf( '<span class="cat-links">%s</span>', $term_list );
}
