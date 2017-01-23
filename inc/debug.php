<?php
/**
 * Search regexp \'[a-z]+ - [a-z]+\'
 */

/**
 * use http://example.com?debug=yes to execute the code in this file
 */

add_action( 'get_header', function () {
	global $wpdb;
	$course_id = 1688;
	$user_id   = 1;
	$null_time = '0000-00-00 00:00:00';
	$query     = $wpdb->prepare( "
			SELECT user_item_id
			FROM {$wpdb->prefix}learnpress_user_items
			WHERE user_id = %d
				AND item_id = %d
				AND start_time <> %s AND end_time <> %s
		", $user_id, $course_id, $null_time, $null_time );
	$return    = $wpdb->get_var( $query );
	do_action( 'learn_press_user_finish_course', $course_id, $user_id, $return );
} );