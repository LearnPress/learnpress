<?php
/**
 * Template button dismiss notice.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! isset( $key ) ) {
	return;
}
?>

<button type="button" class="notice-dismiss btn-lp-notice-dismiss" data-dismiss="<?php echo $key; ?>" title="Dismiss notice">
	<span class="screen-reader-text">Dismiss this notice.</span>
</button>
