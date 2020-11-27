<?php
/**
 * Template for displaying thumbnail of single course.
 *
 * Do not use in LP4.
 * Will remove after LearnPress and Eduma and all guest update 4.0.0
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

global $post;

$course = learn_press_get_course();

if ( ! has_post_thumbnail() ) {
	return;
}
?>

<div class="course-thumbnail">
	<?php
	$image_title   = get_the_title( get_post_thumbnail_id() ) ? esc_attr( get_the_title( get_post_thumbnail_id() ) ) : '';
	$image_caption = get_post( get_post_thumbnail_id() ) ? esc_attr( get_post( get_post_thumbnail_id() )->post_excerpt ) : '""';
	$image_link    = wp_get_attachment_url( get_post_thumbnail_id() );
	$image         = get_the_post_thumbnail(
		$post->ID,
		apply_filters( 'single_course_image_size', 'single_course' ),
		array(
			'title' => $image_title,
			'alt'   => $image_title,
		)
	);

	echo apply_filters(
		'learn_press_single_course_image_html',
		sprintf( '<a href="%s" itemprop="image" class="learn-press-single-thumbnail" title="%s">%s</a>', $image_link, $image_caption, $image ),
		$post->ID
	);
	?>
</div>
