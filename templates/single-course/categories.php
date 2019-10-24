<?php
/**
 * Template for displaying categories of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/categories.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.x.x
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! isset( $categories ) ) {
	return;
}
?>
<div class="course-categories">
	<?php foreach ( $categories as $category ) { ?>
        <a href="<?php echo esc_attr( get_term_link( $category ) ); ?>"><?php echo $category->name; ?></a>
	<?php } ?>
</div>