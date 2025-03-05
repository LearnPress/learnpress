<?php
$class = 'lp-archive-courses';

if ( $attributes['style'] === 'modern' ) {
	$class = 'lp-single-course';
}
?>
<div class="<?php echo esc_attr( $class ); ?>">
	<?php
	if ( ! empty( $inner_content ) ) {
		echo $inner_content;
	}
	?>
</div>