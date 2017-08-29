<?php
/**
 * Template for display content of lesson
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or die;

$item = LP_Global::course_item();
print_r($_REQUEST);
if ( array_key_exists( 'security', $_REQUEST ) ) {
	if ( $item->verify_nonce( $_REQUEST['security'], 'complete' ) ) {
		echo 'Completed';
	} else {
		echo 'Failed';
	}
}
?>

<div class="content-item-summary">

	<?php

	/**
	 *
	 */
	do_action( 'learn-press/before-content-item-summary/' . $item->get_item_type() );

	do_action( 'learn-press/content-item-summary/' . $item->get_item_type() );

	do_action( 'learn-press/after-content-item-summary/' . $item->get_item_type() );

	?>

</div>
