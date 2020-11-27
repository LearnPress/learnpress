<?php

if ( LEARN_PRESS_UPDATE_DATABASE ) {

	error_reporting( 0 );

	global $wpdb;

	$wpdb->query( "START TRANSACTION;" );

	try {
		/*$table = $wpdb->prefix . 'learnpress_user_items';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
			//$wpdb->query( "TRUNCATE {$table}" );
		}
		$table = $wpdb->prefix . 'learnpress_user_itemmeta';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
			//$wpdb->query( "TRUNCATE {$table}" );
		}*/

		$table = $wpdb->prefix . 'learnpress_user_quizzes';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
			$query = $wpdb->prepare( "
				INSERT INTO {$wpdb->prefix}learnpress_user_items(`user_item_id`, `user_id`, `item_id`, `item_type`, `start_time`, `end_time`, `status`, `ref_id`, `ref_type`)
				(
					SELECT uq.user_quiz_id, uq.user_id, uq.quiz_id, %s, FROM_UNIXTIME(uqm1.meta_value) as start_date, FROM_UNIXTIME(uqm2.meta_value) as start_date, uqm3.meta_value as status, 0, %s
					FROM {$wpdb->prefix}learnpress_user_quizzes uq
					INNER JOIN {$wpdb->prefix}learnpress_user_quizmeta uqm1 ON uq.user_quiz_id = uqm1.learnpress_user_quiz_id AND uqm1.meta_key = %s
					INNER JOIN {$wpdb->prefix}learnpress_user_quizmeta uqm2 ON uq.user_quiz_id = uqm2.learnpress_user_quiz_id AND uqm2.meta_key = %s
					INNER JOIN {$wpdb->prefix}learnpress_user_quizmeta uqm3 ON uq.user_quiz_id = uqm3.learnpress_user_quiz_id AND uqm3.meta_key = %s
				)
			", 'lp_quiz', 'lp_course', 'start', 'end', 'status' );
			$wpdb->query( $query );

			$query = $wpdb->prepare( "
				INSERT INTO {$wpdb->learnpress_user_itemmeta}(`learnpress_user_item_id`, `meta_key`, `meta_value`)
				SELECT learnpress_user_quiz_id, meta_key, meta_value
				FROM {$wpdb->prefix}learnpress_user_quizmeta
				WHERE meta_key <> %s AND meta_key <> %s AND meta_key <> %s
			", 'start', 'end', 'status' );
			$wpdb->query( $query );
		}
		// update meta_key name
		/*
		$args  = array( 'current_question', '_quiz_question', 'questions', '_quiz_questions', 'question_answers', '_quiz_question_answers', 'current_question', 'questions', 'question_answers' );
		$query = $wpdb->prepare( "
			UPDATE {$wpdb->prefix}learnpress_user_itemmeta
			SET meta_key = CASE
				WHEN meta_key = %s THEN %s
				WHEN meta_key = %s THEN %s
				WHEN meta_key = %s THEN %s
			END
			WHERE meta_key IN(%s, %s, %s)
		", $args );*/

		//fix course_id is empty in quiz item
		$query = $wpdb->prepare( "
			SELECT user_item_id, item_id
			FROM {$wpdb->learnpress_user_items}
			WHERE ref_id = %d
		", 0 );
		if ( $item_empty_course = $wpdb->get_results( $query ) ) {

			$q_vars = array();
			foreach ( $item_empty_course as $r ) {
				$q_vars[] = $r->item_id;
			}
			$in    = array_fill( 0, sizeof( $q_vars ), '%d' );
			$query = $wpdb->prepare( "
				SELECT section_course_id as course_id, item_id
				FROM {$wpdb->learnpress_section_items} si
				INNER JOIN {$wpdb->learnpress_sections} s ON si.section_id = s.section_id
				WHERE item_id IN(" . join( ',', $in ) . ")
			", $q_vars );

			if ( $item_courses = $wpdb->get_results( $query ) ) {
				foreach ( $item_courses as $row ) {
					$wpdb->update(
						$wpdb->learnpress_user_items,
						array( 'ref_id' => $row->course_id ),
						array( 'ref_id' => 0, 'item_id' => $row->item_id ),
						array( '%d' ),
						array( '%d', '%d' )
					);
				}
			}
		}

		$table = $wpdb->prefix . 'learnpress_user_courses';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
			$query = $wpdb->prepare( "
				INSERT INTO {$wpdb->learnpress_user_items}(`user_id`, `item_id`, `item_type`, `start_time`, `end_time`, `status`, `ref_id`, `ref_type`)
				SELECT `user_id`, `course_id`, %s, `start_time`, `end_time`, `status`, `order_id`, %s
				FROM {$wpdb->prefix}learnpress_user_courses
			", 'lp_course', 'lp_order' );
			$wpdb->query( $query );
		}

		$table = $wpdb->prefix . 'learnpress_user_lessons';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
			$query = $wpdb->prepare( "
				INSERT INTO {$wpdb->learnpress_user_items}(`user_id`, `item_id`, `item_type`, `start_time`, `end_time`, `status`, `ref_id`, `ref_type`)
				SELECT user_id, lesson_id, %s, if(start_time, start_time, %s), if(end_time, end_time, %s), status, course_id, %s
				FROM {$wpdb->prefix}learnpress_user_lessons
			", 'lp_lesson', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'lp_course' );
			$wpdb->query( $query );
		}
		// remove auto-increment
		//$query = "ALTER TABLE {$wpdb->prefix}learnpress_user_courses` MODIFY COLUMN `user_course_item_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0;";

		// remove auto-increment
		$table = $wpdb->prefix . 'learnpress_user_course_items';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
			$query = "ALTER TABLE {$wpdb->prefix}learnpress_user_course_items MODIFY COLUMN `user_course_item_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0;";
			$wpdb->query( $query );
		}

		$old_tables = array( 'user_courses', 'user_course_items', 'user_course_itemmeta', 'user_quizzes', 'user_quizmeta', 'user_lessons' );
		foreach ( $old_tables as $old_table ) {
			$table = $wpdb->prefix . 'learnpress_' . $old_table;
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
				$query_rename_tables = "RENAME table {$table} TO __{$table};";
				// query for renaming unused tables to backup
				// do not remove it permanently
				@$wpdb->query( $query_rename_tables );
			}
		}

		$query = "ALTER TABLE {$wpdb->prefix}learnpress_user_items ADD COLUMN `parent_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER `ref_type`;";
		if ( @$wpdb->query( $query ) ) {
			update_option( 'learn_press_upgrade_table_20', 'yes' );
		}

		// This line has added in version 2.0.5 to fix issue with bug can not do anything after active LP
		$wpdb->query( "COMMIT;" );

		learn_press_update_log( '2.0', array( 'time' => time() ) );

		do_action( 'learn_press_upgrade_database', '2.0' );
	} catch ( Exception $ex ) {
		$wpdb->query( "ROLLBACK;" );
	}
}
delete_option( 'learnpress_updater_step' );
delete_option( 'learnpress_updater' );
LP_Install::update_db_version('2.0');
return array( 'done' => true, 'percent' => 100 );