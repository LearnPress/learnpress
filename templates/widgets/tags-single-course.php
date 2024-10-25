<?php
if ( ! is_singular( 'lp_course' ) ) {
	return '';
}

$tags = get_the_terms( get_the_ID(), 'post_tag' );
?>

<?php if ( ! empty( $tags ) ) : ?>
	<div id="tags-single-course">
		<?php foreach ( $tags as $tag ) : ?>
			<span>
				<?php echo esc_html( $tag ); ?>
			</span>
		<?php endforeach; ?>
	</div>
<?php endif; ?>