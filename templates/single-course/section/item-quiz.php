<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! isset( $item ) ) {
	return;
}
?>
<span class="item-name">
	<?php echo $item->get_title(); ?>
</span>