<?php
if ( ! empty( $value['id'] ) ) {
	do_action( 'lp_metabox_settings_' . sanitize_title( $value['id'] ) . '_end' );
}

echo '</table>';

if ( ! empty( $value['id'] ) ) {
	do_action( 'lp_metabox_settings_' . sanitize_title( $value['id'] ) . '_after' );
}
