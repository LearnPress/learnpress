<?php

if ( LEARN_PRESS_UPDATE_DATABASE ) {

	error_reporting( 0 );

	global $wpdb;

	$wpdb->query( "START TRANSACTION;" );

	try {
		$wpdb->query( "TRUNCATE {$wpdb->prefix}learnpress_user_items" );
		$wpdb->query( "TRUNCATE {$wpdb->prefix}learnpress_user_itemmeta" );
		$query = $wpdb->prepare( "
		INSERT INTO {$wpdb->prefix}learnpress_user_items(`user_item_id`, `user_id`, `item_id`, `item_type`, `start_time`, `end_time`, `status`, `ref_id`, `ref_type`)
		(
			SELECT uq.user_quiz_id, uq.user_id, uq.quiz_id, %s, FROM_UNIXTIME(uqm1.meta_value) as start_date, FROM_UNIXTIME(uqm2.meta_value) as start_date, uqm3.meta_value as status, `course_id`, %s
			FROM {$wpdb->prefix}learnpress_user_quizzes uq
			INNER JOIN {$wpdb->prefix}learnpress_user_quizmeta uqm1 ON uq.user_quiz_id = uqm1.learnpress_user_quiz_id AND uqm1.meta_key = %s
			INNER JOIN {$wpdb->prefix}learnpress_user_quizmeta uqm2 ON uq.user_quiz_id = uqm2.learnpress_user_quiz_id AND uqm2.meta_key = %s
			INNER JOIN {$wpdb->prefix}learnpress_user_quizmeta uqm3 ON uq.user_quiz_id = uqm3.learnpress_user_quiz_id AND uqm3.meta_key = %s
		)
	", 'lp_quiz', 'lp_course', 'start', 'end', 'status' );
		$wpdb->query( $query );
		LP_Debug::instance()->add( $wpdb );

		$query = $wpdb->prepare( "
		INSERT INTO {$wpdb->prefix}learnpress_user_itemmeta(`learnpress_user_item_id`, `meta_key`, `meta_value`)
		SELECT learnpress_user_quiz_id, meta_key, meta_value
		FROM {$wpdb->prefix}learnpress_user_quizmeta
		WHERE meta_key <> %s AND meta_key <> %s AND meta_key <> %s
	", 'start', 'end', 'status' );
		$wpdb->query( $query );
		LP_Debug::instance()->add( $wpdb );

		$query = $wpdb->prepare( "
		INSERT INTO {$wpdb->prefix}learnpress_user_items(`user_id`, `item_id`, `item_type`, `start_time`, `end_time`, `status`, `ref_id`, `ref_type`)
		SELECT `user_id`, `course_id`, %s, `start_time`, `end_time`, `status`, `order_id`, %s
		FROM {$wpdb->prefix}learnpress_user_courses
	", 'lp_course', 'lp_order' );
		$wpdb->query( $query );
		LP_Debug::instance()->add( $wpdb );

		$query = $wpdb->prepare( "
		INSERT INTO {$wpdb->prefix}learnpress_user_items(`user_id`, `item_id`, `item_type`, `start_time`, `end_time`, `status`, `ref_id`)
		SELECT user_id, lesson_id, ,if(item_type, item_type, %s), if(start_time, start_time, %s), if(end_time, end_time, %s), status, course_id
		FROM {$wpdb->prefix}learnpress_user_lessons w;
	", 'lp_lesson', '0000-00-00 00:00:00', '0000-00-00 00:00:00' );
		$wpdb->query( $query );
		LP_Debug::instance()->add( $wpdb );

		// remove auto-increment
		//$query = "ALTER TABLE {$wpdb->prefix}learnpress_user_courses` MODIFY COLUMN `user_course_item_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0;";


		// remove auto-increment
		$query = "ALTER TABLE {$wpdb->prefix}learnpress_user_course_items` MODIFY COLUMN `user_course_item_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0;";
		$wpdb->query( $query );
	} catch ( Exception $ex ) {
		$wpdb->query( "ROLLBACK;" );
	}
}