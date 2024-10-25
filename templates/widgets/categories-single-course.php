<?php
if ( ! is_singular( 'lp_course' ) ) {
	return '';
}

$categories = get_the_terms( get_the_ID(), 'category' );

?>

<?php if ( ! empty( $categories ) ) : ?>
<div id="list-categories-single-course">
	<?php foreach ( $categories as $category ) : ?>
		<span>
			<?php echo esc_html( $category ); ?>
		</span>
	<?php endforeach; ?>
</div>
<?php endif; ?>