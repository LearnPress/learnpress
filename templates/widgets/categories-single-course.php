<?php
$course_id  = $attributes['courseId'] ? (int) $attributes['courseId'] : get_the_ID();
$categories = get_the_terms( $course_id, 'course_category' );
?>

<?php if ( ! empty( $categories ) ) : ?>
<div class="list-categories-single-course">
	<?php foreach ( $categories as $category ) : ?>
		<span>
			<?php echo esc_html( $category->name ); ?>
		</span>
	<?php endforeach; ?>
</div>
<?php endif; ?>