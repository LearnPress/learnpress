<?php
/**
 * Add columns for storing the time in GMT
 * @since 3.x.x
 */
function learn_press_300_add_column_user_items() {
	global $wpdb;
	echo $sql = $wpdb->prepare( "
		ALTER TABLE {$wpdb->learnpress_user_items}
		ADD COLUMN `start_time_gmt` DATETIME NULL DEFAULT %s AFTER `start_time`,
		ADD COLUMN `end_time_gmt` DATETIME NULL DEFAULT %s AFTER `end_time`;
	", '0000-00-00 00:00:00', '0000-00-00 00:00:00' );

	$wpdb->query( $sql );
	if ( $wpdb->last_error !== '' ) {
		learn_press_add_message( $wpdb->last_error, 'error' );
	}
}


//
learn_press_300_add_column_user_items();
