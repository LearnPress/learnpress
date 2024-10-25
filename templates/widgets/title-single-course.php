<?php
if ( ! is_singular( 'lp_course' ) ) {
	return '';
}

$title = get_the_title();

?>

<div id="title-single-course">
	<?php echo esc_html( $title ); ?>
</div>