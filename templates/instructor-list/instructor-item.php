<?php
if ( ! isset( $data ) ) {
	return;
}

?>
<li class="<?php echo esc_attr( apply_filters( 'learnpress/instructor-item/class', 'lp_instructor' ) ); ?>">
	<div class="instructor-item">
		<?php
		do_action( 'learnpress/layout/instructor-item/items', $data );
		?>
	</div>
</li>
