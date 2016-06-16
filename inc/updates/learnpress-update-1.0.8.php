<?php

if ( LEARN_PRESS_UPDATE_DATABASE ) {
	global $wpdb;

	$r     = array();
	$r     = array_fill( 0, 4, '0000-00-00 00:00:00' );
	$query = $wpdb->prepare( "
			UPDATE {$wpdb->prefix}learnpress_user_lessons
			SET
				start_time = IF((start_time = %s or ISNULL(start_time)) AND start_date <> %s AND !ISNULL(start_date) , start_date, start_time),
				end_time = IF((end_time = %s or ISNULL(end_time)) AND end_date <> %s AND !ISNULL(end_date), end_date, end_time)
		", $r );
	$wpdb->query( $query );

	$query = $wpdb->query( "
		ALTER TABLE {$wpdb->prefix}learnpress_user_lessons DROP COLUMN `start_date`, DROP COLUMN `end_date`;
	" );
}