<?php
if ( ! is_singular( 'lp_course' ) ) {
	return '';
}

$description = get_the_excerpt();

?>

<?php if ( ! empty( $description ) ) : ?>
<div id="description-single-course">
	<?php echo esc_html( $description ); ?>
</div>
<?php endif; ?>