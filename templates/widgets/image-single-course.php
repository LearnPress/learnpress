<?php
if ( ! is_singular( 'lp_course' ) ) {
	return '';
}

$image = get_the_post_thumbnail_url( get_the_ID(), 'full' );
?>

<?php if ( ! empty( $image ) ) : ?>	
	<div id="image-single-course">
		<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr__( 'image-single-course', 'learnpress' ); ?>" />
	</div>
<?php endif; ?>