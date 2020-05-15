<?php
/**
 * Template for displaying thumbnail of course within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/loop/course/thumbnail.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();
?>

<div class="course-thumbnail">

	<?php
	/**
	 * Editor tungnx
	 * Check case $course->get_image return boolean
	 */
	$el_image = $course->get_image( 'course_thumbnail' );

	if ( is_string( $el_image ) ) {
		echo $el_image;
	}
	?>

</div>
