<?php
/**
 * Template for display error wrong name plugin learnpress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! isset( $data ) || ! isset( $data['check'] ) || ! $data['check'] ) {
	return;
}

$info      = is_array( $data['info'] ) ? $data['info'] : [];
$data_info = LP_Admin_Notice::get_data_lp_beta( $info );
?>

<div class="lp-admin-notice lp-mes-beta-version">
	<h3><?php echo wp_kses_post( $data_info['title'] ?? '' ); ?></h3>
	<?php echo wp_kses_post( $data_info['description'] ?? '' ); ?>
	<button type="button" class="notice-dismiss btn-lp-notice-dismiss" data-dismiss="lp-beta-version" data-info="">
		<span class="screen-reader-text">Dismiss this notice.</span>
	</button>
</div>
