<?php

$course_id   = $attributes['courseId'] ? (int) $attributes['courseId'] : null;
$description = get_the_excerpt( $course_id );

?>

<?php if ( ! empty( $description ) ) : ?>
<div class="description-single-course">
	<?php echo esc_html( $description ); ?>
</div>
<?php endif; ?>