<?php

$course_id = $attributes['courseId'] ? (int) $attributes['courseId'] : get_the_ID();
$tags      = get_the_terms( $course_id, 'course_tag' );

?>

<?php if ( ! empty( $tags ) ) : ?>
	<div class="tags-single-course">
		<?php foreach ( $tags as $tag ) : ?>
			<span>
				<?php echo esc_html( $tag->name ); ?>
			</span>
		<?php endforeach; ?>
	</div>
<?php endif; ?>