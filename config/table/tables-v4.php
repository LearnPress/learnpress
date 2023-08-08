<?php
/**
 * List tables for the database
 *
 * @version 4
 * @since 4.1.6.4
 * @version 1.0.0
 */

$lp_db = LP_Database::getInstance();

$collate = $lp_db->wpdb->has_cap( 'collation' ) ? $lp_db->wpdb->get_charset_collate() : '';

// Max DB index length. See wp_get_db_schema().
$max_index_length = 191;

return array(
	$lp_db->tb_lp_order_items         => "
		CREATE TABLE IF NOT EXISTS {$lp_db->tb_lp_order_items} (
			order_item_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			order_item_name longtext NOT NULL,
			order_id bigint(20) unsigned NOT NULL DEFAULT 0,
			item_id bigint(20) unsigned NOT NULL DEFAULT 0,
			item_type varchar(45) NOT NULL DEFAULT '',
			PRIMARY KEY (order_item_id),
		    KEY order_id (order_id),
		    KEY item_id (item_id),
		    KEY item_type (item_type)
		) $collate;
	",
	$lp_db->tb_lp_order_itemmeta      => "
		CREATE TABLE IF NOT EXISTS {$lp_db->tb_lp_order_itemmeta} (
			meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			learnpress_order_item_id bigint(20) unsigned NOT NULL DEFAULT '0',
			meta_key varchar(255) NOT NULL DEFAULT '',
			meta_value varchar(255) NULL,
			extra_value longtext,
			PRIMARY KEY (meta_id),
	        KEY learnpress_order_item_id (learnpress_order_item_id),
	        KEY meta_key (meta_key({$max_index_length})),
	        KEY meta_value (meta_value({$max_index_length}))
		) $collate;
	",
	$lp_db->tb_lp_question_answers    => "
		CREATE TABLE IF NOT EXISTS {$lp_db->tb_lp_question_answers} (
			question_answer_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			question_id bigint(20) unsigned NOT NULL DEFAULT '0',
			title text NOT NULL,
			`value` varchar(32) NOT NULL,
			`order` bigint(20) unsigned NOT NULL DEFAULT '1',
			is_true varchar(3),
			PRIMARY KEY (question_answer_id),
			KEY question_id (question_id)
		) $collate;
	",
	$lp_db->tb_lp_question_answermeta => "
		CREATE TABLE IF NOT EXISTS {$lp_db->tb_lp_question_answermeta} (
			meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			learnpress_question_answer_id bigint(20) unsigned NOT NULL,
			meta_key varchar(255) NOT NULL DEFAULT '',
			meta_value longtext NULL,
			PRIMARY KEY (meta_id),
			KEY question_answer_meta (learnpress_question_answer_id, meta_key(150))
		) $collate;
	",
	$lp_db->tb_lp_quiz_questions      => "
		CREATE TABLE IF NOT EXISTS {$lp_db->tb_lp_quiz_questions} (
			quiz_question_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			quiz_id bigint(20) unsigned NOT NULL DEFAULT '0',
			question_id bigint(20) unsigned NOT NULL DEFAULT '0',
			question_order bigint(20) unsigned NOT NULL DEFAULT '1',
			PRIMARY KEY (quiz_question_id),
			KEY quiz_id (quiz_id),
			KEY question_id (question_id)
		) $collate;
	",
	$lp_db->tb_lp_section_items       => "
		CREATE TABLE IF NOT EXISTS {$lp_db->tb_lp_section_items} (
			section_item_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			section_id bigint(20) unsigned NOT NULL DEFAULT '0',
			item_id bigint(20) unsigned NOT NULL DEFAULT '0',
			item_order bigint(20) unsigned NOT NULL DEFAULT '0',
			item_type varchar(45),
			PRIMARY KEY (section_item_id),
			KEY section_item (`section_id`, `item_id`)
		) $collate;
	",
	$lp_db->tb_lp_sections            => "
		CREATE TABLE IF NOT EXISTS {$lp_db->tb_lp_sections} (
			section_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			section_name varchar(255) NOT NULL DEFAULT '',
			section_course_id bigint(20) unsigned NOT NULL DEFAULT '0',
			section_order bigint(10) unsigned NOT NULL DEFAULT '1',
			section_description longtext NOT NULL,
			PRIMARY KEY (section_id),
			KEY section_course_id (section_course_id)
		) $collate;
	",
	$lp_db->tb_lp_sessions            => "
		CREATE TABLE IF NOT EXISTS {$lp_db->tb_lp_sessions} (
			session_id bigint(20) NOT NULL AUTO_INCREMENT,
			session_key char(32) NOT NULL,
			session_value longtext NOT NULL,
			session_expiry bigint(20) NOT NULL,
			UNIQUE KEY session_id (session_id),
			PRIMARY KEY (session_key)
		) $collate;
	",
	$lp_db->tb_lp_user_items          => "
		CREATE TABLE IF NOT EXISTS {$lp_db->tb_lp_user_items} (
			user_item_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT '0',
			item_id bigint(20) unsigned NOT NULL DEFAULT '0',
			start_time datetime NULL DEFAULT NULL,
			end_time datetime NULL DEFAULT NULL,
			item_type varchar(45) NOT NULL DEFAULT '',
			status varchar(45) NOT NULL DEFAULT '',
			graduation varchar(20) NULL DEFAULT NULL,
			access_level int(3) NOT NULL DEFAULT 50,
			ref_id bigint(20) unsigned NOT NULL DEFAULT '0',
			ref_type varchar(45) DEFAULT '',
			parent_id bigint(20) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY (user_item_id),
			KEY parent_id (parent_id),
			KEY user_id (user_id),
			KEY item_id (item_id),
			KEY item_type (item_type),
			KEY ref_id (ref_id),
			KEY ref_type (ref_type),
			KEY status (status)
		) $collate;
	",
	$lp_db->tb_lp_user_itemmeta       => "
		CREATE TABLE IF NOT EXISTS {$lp_db->tb_lp_user_itemmeta} (
			meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			learnpress_user_item_id bigint(20) unsigned NOT NULL,
			meta_key varchar(255) NOT NULL DEFAULT '',
			meta_value varchar(255) NULL,
			extra_value longtext NULL,
			PRIMARY KEY (meta_id),
			KEY learnpress_user_item_id (learnpress_user_item_id),
            KEY meta_key (meta_key({$max_index_length})),
            KEY meta_value (meta_value({$max_index_length}))
		) $collate;
	",
	$lp_db->tb_lp_user_item_results   => "
		CREATE TABLE IF NOT EXISTS {$lp_db->tb_lp_user_item_results} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_item_id bigint(20) unsigned NOT NULL,
			result longtext NULL,
			PRIMARY KEY (id),
			KEY user_item_id (user_item_id)
		) $collate;
	",
	$lp_db->tb_lp_files	=> "
		CREATE TABLE IF NOT EXISTS {$lp_db->tb_lp_files} (
			file_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			file_name varchar(191) NOT NULL DEFAULT '',
			file_type varchar(10) NOT NULL DEFAULT '',
			item_id bigint(20) unsigned NOT NULL DEFAULT '0',
			item_type varchar(100) NOT NULL DEFAULT '',
			method varchar(10) NOT NULL DEFAULT 'upload' CHECK ( method IN ( 'upload', 'external' ) ),
			file_path varchar(255) NOT NULL DEFAULT '',
			orders int(4) NOT NULL DEFAULT '0',
			created_at datetime NULL DEFAULT NULL,
			PRIMARY KEY (file_id),
			KEY file_name (file_name),
			KEY item_id (item_id),
			KEY item_type (item_type)
		) $collate;
	",
);

