<?php
/**
 * Template for display a notice in admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div<?php if( ! empty( $id ) ) echo ' id="' . $id . '"';?> class="<?php echo $type;?>">
	<?php echo $message;?>
</div>