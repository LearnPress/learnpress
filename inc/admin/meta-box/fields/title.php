<?php
if ( ! empty( $value['title'] ) ) {
	echo '<h2>' . esc_html( $value['title'] ) . '</h2>';
}
if ( ! empty( $value['desc'] ) ) {
	echo '<div class="lp-metabox__desc">' . wp_kses_post( $value['desc'] ) . '</div>';
}

echo '<table class="form-table lp-metabox__table">';

if ( ! empty( $value['id'] ) ) {
	do_action( 'lp_metabox_settings_' . sanitize_title( $value['id'] ) );
}

