<?php

$course_id = $attributes['courseId'] ? (int) $attributes['courseId'] : 0;
$title     = get_the_title( $course_id ) ?? '';
?>
<?php if ( ! empty( $title ) ) : ?>
	<div class="title-single-course">
		<h3>
			<?php echo esc_html( $title ); ?>
		</h3>
	</div>
<?php endif; ?>