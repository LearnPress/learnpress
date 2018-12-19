<?php

if ( LEARN_PRESS_UPDATE_DATABASE ) {

	error_reporting( 0 );

	global $wpdb;
	$null_date = '0000-00-00 00:00:00';

	$table = $wpdb->prefix . 'learnpress_user_lessons';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
		// add column start_time, end_time if is not available
		$query = $wpdb->prepare( "
                    ALTER TABLE {$wpdb->prefix}learnpress_user_lessons ADD COLUMN `start_time` DATETIME NOT NULL DEFAULT %s,
                    ADD COLUMN `end_time` DATETIME NOT NULL DEFAULT %s;
            ", array( $null_date ) );
		$wpdb->query( $query );

		// copy data from start_date, end_date to start_time, end_time column
		$r     = array_fill( 0, 4, $null_date );
		$query = $wpdb->prepare( "
                            UPDATE {$wpdb->prefix}learnpress_user_lessons
                            SET
                                    start_time = IF((start_time = %s or ISNULL(start_time)) AND start_date <> %s AND !ISNULL(start_date) , start_date, start_time),
                                    end_time = IF((end_time = %s or ISNULL(end_time)) AND end_date <> %s AND !ISNULL(end_date), end_date, end_time)
                    ", $r );
		$wpdb->query( $query );

		// delete columns start_date, end_date
		$query = $wpdb->query( "
                    ALTER TABLE {$wpdb->prefix}learnpress_user_lessons DROP COLUMN `start_date`, DROP COLUMN `end_date`;
            " );
	}

	$table = $wpdb->prefix . 'learnpress_user_course_items';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
		// delete columns start_date, end_date
		$query = $wpdb->query( "
			ALTER TABLE {$wpdb->prefix}learnpress_user_course_items DROP COLUMN `start_date`, DROP COLUMN `end_date`;
		" );
	}
	learn_press_update_log( '1.0.8', array( 'time' => time() ) );
}
delete_option( 'learnpress_updater_step' );
delete_option( 'learnpress_updater' );
LP_Install::update_db_version('1.0.8');
return array( 'done' => true, 'percent' => 100 );