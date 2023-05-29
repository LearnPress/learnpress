<?php
if ( ! isset( $data ) ) {
	return;
}
?>

<div class="view-profile">
	<a href="<?php echo esc_url_raw( $data['view_instructor'] ?? '' ); ?>"><?php esc_html_e( 'View Profile', 'learnpress' ); ?></a>
</div>
