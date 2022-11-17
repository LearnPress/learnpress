<?php
/**
 * Template for display beta version of LP.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! isset( $data ) || ! isset( $data['check'] ) || ! $data['check'] ) {
	return;
}

$info = is_array( $data['info'] ) ? $data['info'] : [];
if ( empty( $info ) ) {
	echo '<p>No beta version</p>';
	return;
}

$data_info = LP_Admin_Notice::get_data_lp_beta( $info );
?>

<div class="lp-admin-notice lp-mes-beta-version notice notice-info">
	<h3><?php echo wp_kses_post( $data_info['title'] ?? '' ); ?></h3>
	<?php echo wp_kses_post( $data_info['description'] ?? '' ); ?>
	<?php
	if ( isset( $data['dismiss'] ) ) :
		?>
		<button type="button" class="notice-dismiss btn-lp-notice-dismiss" data-dismiss="lp-beta-version" title="Dismiss notice">
			<span class="screen-reader-text">Dismiss this notice.</span>
		</button>
	<?php endif; ?>
</div>
