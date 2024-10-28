<?php
$course_id = $attributes['courseId'] ? (int) $attributes['courseId'] : get_the_ID();
$image     = get_the_post_thumbnail_url( $course_id, 'full' );
?>

<?php if ( ! empty( $image ) ) : ?>	
	<div class="image-single-course">
		<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr__( 'image-single-course', 'learnpress' ); ?>" />
	</div>
<?php endif; ?>