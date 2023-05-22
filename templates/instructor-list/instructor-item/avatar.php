<?php
if ( ! isset( $data ) ) {
	return;
}
?>
<div class="instructor-avatar">
	<img src="<?php echo esc_url_raw( $data['avatar_url'] ); ?>" alt="<?php echo esc_html( $data['display_name'] ); ?>">
</div>
