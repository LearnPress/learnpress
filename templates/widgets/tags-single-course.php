<?php
if ( ! is_singular( 'lp_course' ) ) {
	return '';
}

$tags = get_the_terms( get_the_ID(), 'course_tag' );

?>

<?php if ( ! empty( $tags ) ) : ?>
	<div id="tags-single-course">
		<?php foreach ( $tags as $tag ) : ?>
			<span>
				<?php echo esc_html( $tag->name ); ?>
			</span>
		<?php endforeach; ?>
	</div>
<?php endif; ?>