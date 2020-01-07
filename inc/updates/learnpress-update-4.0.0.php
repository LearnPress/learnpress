<?php
/**
 * Todo: update emails
 */

include_once dirname( __FILE__ ) . '/learnpress-update-base.php';

/**
 * Class LP_Update_400
 *
 * Helper class for updating database to 4.0.0
 */
class LP_Update_400 extends LP_Update_Base {

	public function __construct() {
		$this->version = '4.0.0';
		$this->steps   = array(
			'alter_datetime_default_value',
			'alter_tables',
			'update_time_field_from_time_gmt',
			'update_expiration_time',
			'update_item_graduation',
			//'remove_time_gmt',
			'update_question_answers',
			'update_quiz_settings',
			'update_settings'
			//'delete_table_columns'
		);

		parent::__construct();
	}

	public function update_settings() {
		if ( $profile_settings = get_option( 'learn_press_profile_endpoints' ) ) {
			$new_profile_settings = array();
			foreach ( $profile_settings as $k => $v ) {
				$new_k = preg_replace( '/^profile-/', '', $k );

				if ( $new_k === 'dashboard' ) {
					$new_k = 'overview';
				}

				$new_profile_settings[ $new_k ] = $v;
			}

			update_option( 'learn_press_profile_endpoints', $new_profile_settings );
		}
		return true;
	}

	/**
	 * Alter table user_items to change the default values for datetime fields
	 * from '0000-00-00 00:00:00' to NULL.
	 *
	 * @return bool
	 */
	public function alter_datetime_default_value() {
		global $wpdb;

		$query = "
			ALTER TABLE `{$wpdb->prefix}learnpress_user_items` 
			CHANGE `start_time` `start_time` DATETIME NULL DEFAULT NULL, 
			CHANGE `start_time_gmt` `start_time_gmt` DATETIME NULL DEFAULT NULL, 
			CHANGE `end_time` `end_time` DATETIME NULL DEFAULT NULL, 
			CHANGE `end_time_gmt` `end_time_gmt` DATETIME NULL DEFAULT NULL
		";

		$wpdb->query( $query );

		return true;
	}

	/**
	 * Add new columns to user_items.
	 *
	 * @return bool
	 */
	public function alter_tables() {

		$this->alter_order_itemmeta();
		$this->alter_order_items();
		$this->alter_question_answermeta();
		$this->alter_question_answers();
		$this->alter_quiz_questions();
		$this->alter_user_itemmeta();
		$this->alter_user_items();
		$this->alter_sections();
		$this->alter_section_items();

		return true;
	}

	protected function alter_sections() {
		global $wpdb;

		foreach ( array( 'section_course_id', 'section_id', 'item_id' ) as $index ) {
			try {
				$wpdb->query( "
					ALTER TABLE {$wpdb->learnpress_sections}
					DROP INDEX `{$index}`;
				" );
			}
			catch ( Exception $ex ) {
			}
		}

		$wpdb->query( "
			ALTER TABLE {$wpdb->learnpress_user_items}
			ADD INDEX `section_course_id` (`section_course_id` ASC),
			ADD INDEX `section_id` (`section_id` ASC),
			ADD INDEX `item_id` (`item_id` ASC);
		" );
	}

	protected function alter_section_items() {
		global $wpdb;

		foreach ( array( 'section_course_id', 'section_id', 'item_id' ) as $index ) {
			try {
				$wpdb->query( "
					ALTER TABLE {$wpdb->learnpress_sections}
					DROP INDEX `{$index}`;
				" );
			}
			catch ( Exception $ex ) {
			}
		}

		$wpdb->query( "
			ALTER TABLE {$wpdb->learnpress_user_items}
			ADD INDEX `section_course_id` (`section_course_id` ASC),
			ADD INDEX `section_id` (`section_id` ASC),
			ADD INDEX `item_id` (`item_id` ASC);
		" );
	}

	protected function alter_order_itemmeta() {
		global $wpdb;

		// wp_learnpress_order_itemmeta
		$wpdb->query( "
			ALTER TABLE {$wpdb->learnpress_order_itemmeta}
			CHANGE COLUMN `meta_key` `meta_key` VARCHAR(255) NOT NULL DEFAULT '' ;
		" );

		foreach ( array( 'learnpress_order_item_id', 'meta_key' ) as $index ) {
			$wpdb->query( "
				ALTER TABLE {$wpdb->learnpress_order_itemmeta}
				DROP INDEX `{$index}`;
			" );
		}

		$wpdb->query( "
			ALTER TABLE {$wpdb->learnpress_order_itemmeta}
			ADD INDEX `learnpress_order_item_id` (`learnpress_order_item_id` ASC),
			ADD INDEX `meta_key` (`meta_key`(191) ASC);
		" );
	}

	protected function alter_order_items() {
		global $wpdb;

		$query = "
     		 ALTER TABLE `{$wpdb->prefix}learnpress_order_items` 
     		 ADD `order_item_type` varchar(200) NULL DEFAULT NULL AFTER `order_item_name`;
		";
		$wpdb->query( $query );

		foreach ( array( 'order_id' ) as $index ) {
			$wpdb->query( "
				ALTER TABLE {$wpdb->learnpress_order_items}
				DROP INDEX `{$index}`;
			" );
		}

		$wpdb->query( "
			ALTER TABLE {$wpdb->learnpress_order_items}
			ADD INDEX `order_id` (`order_id` ASC);
		" );
	}

	protected function alter_question_answermeta() {
		global $wpdb;

		$wpdb->query( "
			ALTER TABLE {$wpdb->learnpress_question_answermeta}
			CHANGE COLUMN `meta_key` `meta_key` VARCHAR(255) NOT NULL DEFAULT '',
			CHANGE COLUMN `meta_value` `meta_value` longtext NULL DEFAULT '';
		" );

		foreach ( array( 'learnpress_question_answer_id', 'meta_key' ) as $index ) {
			$wpdb->query( "
				ALTER TABLE {$wpdb->learnpress_question_answermeta}
				DROP INDEX `{$index}`;
			" );
		}

		$wpdb->query( "
			ALTER TABLE {$wpdb->learnpress_question_answermeta}
			ADD INDEX `learnpress_question_answer_id` (`learnpress_question_answer_id` ASC),
			ADD INDEX `meta_key` (`meta_key`(191) ASC);
		" );
	}

	protected function alter_question_answers() {
		global $wpdb;

		$query = "
			ALTER TABLE `{$wpdb->prefix}learnpress_question_answers`
			ADD `title` text NULL AFTER `question_id`,
			ADD `value` varchar(32) NULL AFTER `title`,
			ADD `order` bigint(20) NULL DEFAULT 1 AFTER `value`,
			ADD `is_true` varchar(3) NULL AFTER `order`;
		";
		$wpdb->query( $query );

		foreach ( array( 'question_id' ) as $index ) {
			$wpdb->query( "
				ALTER TABLE {$wpdb->learnpress_question_answers}
				DROP INDEX `{$index}`;
			" );
		}

		$wpdb->query( "
			ALTER TABLE {$wpdb->learnpress_question_answers}
			ADD INDEX `question_id` (`question_id` ASC);
		" );
	}

	protected function alter_user_itemmeta() {
		global $wpdb;

		/////
		$wpdb->query( "
			ALTER TABLE {$wpdb->learnpress_user_itemmeta}
			CHANGE COLUMN `meta_key` `meta_key` VARCHAR(255) NOT NULL DEFAULT '' ;
		" );

		foreach ( array( 'learnpress_user_item_id', 'meta_key' ) as $index ) {
			$wpdb->query( "
				ALTER TABLE {$wpdb->learnpress_user_itemmeta}
				DROP INDEX `{$index}`;
			" );
		}

		$wpdb->query( "
			ALTER TABLE {$wpdb->learnpress_user_itemmeta}
			ADD INDEX `learnpress_user_item_id` (`learnpress_user_item_id` ASC),
			ADD INDEX `meta_key` (`meta_key`(191) ASC);
		" );
	}

	protected function alter_user_items() {
		global $wpdb;

		$query = "
			ALTER TABLE {$wpdb->learnpress_user_items}
			
		";
		$wpdb->query( $query );

		$query = "
     		ALTER TABLE `{$wpdb->prefix}learnpress_user_items` 
     		ADD COLUMN `expiration_time` DATETIME NULL DEFAULT NULL AFTER `end_time`,
     		ADD COLUMN `graduation` VARCHAR(20) NULL AFTER `status`,
			ADD COLUMN `access_level` TINYINT(3) NULL DEFAULT 50 AFTER `graduation`, 
			ADD COLUMN `u` TINYINT(3) NULL DEFAULT 0 AFTER `parent_id` 
		";
		$wpdb->query( $query );

		foreach ( array( 'parent_id', 'user_id', 'item_id', 'ref_id' ) as $index ) {
			$wpdb->query( "
				ALTER TABLE {$wpdb->learnpress_user_items}
				DROP INDEX `{$index}`;
			" );
		}

		$wpdb->query( "
			ALTER TABLE {$wpdb->learnpress_user_items}
			ADD INDEX `parent_id` (`parent_id` ASC),
			ADD INDEX `user_id` (`user_id` ASC),
			ADD INDEX `item_id` (`item_id` ASC),
			ADD INDEX `ref_id` (`ref_id` ASC);
		" );
	}

	protected function alter_quiz_questions() {
		global $wpdb;

		foreach ( array( 'quiz_id', 'question_id' ) as $index ) {
			$wpdb->query( "
				ALTER TABLE {$wpdb->learnpress_quiz_questions}
				DROP INDEX `{$index}`;
			" );
		}

		$wpdb->query( "
			ALTER TABLE {$wpdb->learnpress_quiz_questions}
			ADD INDEX `quiz_id` (`quiz_id` ASC),
			ADD INDEX `question_id` (`question_id` ASC);
		" );
	}

	/**
	 * Update expiration time for all user items
	 */
	public function update_expiration_time() {
		global $wpdb;
//		$query = $wpdb->prepare( "
//			SELECT p.ID id, pm.meta_value duration
//			FROM {$wpdb->posts} p
//			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
//			WHERE
//				p.post_type IN(%s, %s) AND meta_value NOT LIKE %s
//		", '_lp_duration', LP_COURSE_CPT, LP_QUIZ_CPT, $wpdb->esc_like( '0 ' ) . '%' );

//		if ( ! $posts = $wpdb->get_results( $query ) ) {
//			return true;
//		}
//
//		LP_Debug::instance()->add( $query, 'upgrade' );
//		LP_Debug::instance()->add( $posts, 'upgrade' );

		try {
			$offset = absint( get_transient( 'lp_upgrade_user_items_offset' ) );
			$limit  = 500;

			/**
			 * Join user-items with post-meta to get user-items and duration.
			 */
//			$query = $wpdb->prepare( "
//				SELECT ui.*, pm.meta_value as duration
//				FROM {$wpdb->learnpress_user_items} ui
//				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = ui.item_id AND pm.meta_key = %s
//				WHERE ui.start_time <> %s AND ui.start_time <> %s
//				ORDER BY user_item_id ASC
//				LIMIT %d, %d
//			", '_lp_duration', '0000-00-00 00:00:00', '', $offset, $limit );

			$query = $wpdb->prepare( "
				SELECT ui.*, pm.meta_value as duration
				FROM ( 
					SELECT user_id, item_id, MAX(user_item_id) max_id 
					FROM {$wpdb->learnpress_user_items} GROUP BY user_id, item_id
				 ) AS X
				INNER JOIN {$wpdb->learnpress_user_items} ui ON ui.user_id = X.user_id AND ui.item_id = X.item_id AND ui.user_item_id = X.max_id 
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = ui.item_id AND pm.meta_key = %s
				WHERE ui.start_time <> %s AND ui.start_time <> %s
				ORDER BY user_item_id ASC
				LIMIT %d, %d
			", '_lp_duration', '0000-00-00 00:00:00', '', $offset, $limit );

			if ( ! $user_items = $wpdb->get_results( $query ) ) {
				return true;
			}

			learn_press_debug_add( $query, 'upgrade-' . $this->version );
			learn_press_debug_add( $user_items, 'upgrade-' . $this->version );
			LP_Debug::startTransaction();

			foreach ( $user_items as $user_item ) {
				$expiration_time = '0000-00-00 00:00:00';
				$query           = "
						UPDATE {$wpdb->learnpress_user_items}
						SET expiration_time = %s,
							access_level = %d,
							u = 1
						WHERE user_item_id = %d
					";

				// Ignore if duration is not set and ensure we have swapped start_time and start_time_gmt values
				if ( $user_item->duration && ! ( ! $user_item->start_time || $user_item->start_time == '0000-00-00 00:00:00' ) ) {
					// Expiration time = Start time + Duration
					$duration        = new LP_Duration( $user_item->duration );
					$expiration_time = learn_press_date_end_from( $duration->get(), strtotime( $user_item->start_time ) );
				}

				$query = $wpdb->prepare(
					$query,
					$expiration_time,
					50, // default is accessible
					$user_item->user_item_id
				);

				learn_press_debug_add( $query, 'upgrade-' . $this->version );

				$wpdb->query( $query );

			}
			set_transient( 'lp_upgrade_user_items_offset', $offset + $limit, DAY_IN_SECONDS );

			LP_Debug::commitTransaction();
		}
		catch ( Exception $ex ) {
			LP_Debug::rollbackTransaction();
		}

		return false;
	}

	public function update_item_graduation() {
		global $wpdb;

		$query = $wpdb->prepare( "
			UPDATE {$wpdb->learnpress_user_items} ui
			SET graduation = (
				SELECT meta_value
				FROM {$wpdb->learnpress_user_itemmeta}
				WHERE meta_key = %s 
				AND learnpress_user_item_id = ui.user_item_id
			)
			WHERE ui.u = %d
		", 'grade', 1 );
		$wpdb->query( $query );

		$wpdb->query( "ALTER TABLE {$wpdb->learnpress_user_items} DROP COLUMN u" );

		return true;
	}

	/**
	 * Swap value between start_time/start_time_gmt and end_time/end_time_gmt
	 * The field start_time_gmt/end_time_gmt are no longer used therefore
	 * we keep them to backup values of start_time/end_time fields.
	 *
	 * @return bool
	 */
	public function update_time_field_from_time_gmt() {
		global $wpdb;

		$query = $wpdb->prepare( "
			UPDATE {$wpdb->learnpress_user_items} 
			SET 
				start_time = (@temp:=start_time), start_time = start_time_gmt, start_time_gmt = @temp,
				end_time = (@temp:=end_time), end_time = end_time_gmt, end_time_gmt = @temp
			WHERE %d
		", 1 );

		$wpdb->query( $query );

		return true;
	}

	/**
	 * @return bool
	 */
	public function remove_time_gmt() {
		global $wpdb;

		//$wpdb->query( "ALTER TABLE {$wpdb->learnpress_user_items} DROP `start_time_gmt`, DROP `end_time_gmt`" );

		return true;
	}

	public function update_question_answers() {
		global $wpdb;

		$offset = absint( get_transient( 'lp_upgrade_question_answers_offset' ) );
		$limit  = 500;

		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->learnpress_question_answers}
			ORDER BY question_answer_id ASC
			LIMIT %d, %d
		", $offset, $limit );

		$rows = $wpdb->get_results( $query );

		if ( ! $rows ) {
			return true;
		}

		try {
			LP_Debug::startTransaction();

			foreach ( $rows as $row ) {
				$answers    = wp_parse_args(
					maybe_unserialize( $row->answer_data ),
					array(
						'text'    => '',
						'value'   => '',
						'is_true' => ''
					)
				);
				$update_sql = $wpdb->prepare( "
					UPDATE {$wpdb->learnpress_question_answers}
					SET `title`=%s,
						`value`=%s,
						`is_true`=%s,
						`order`=%s
					WHERE question_answer_id=%d
				",
					$answers['text'],
					$answers['value'] ? $answers['value'] : learn_press_random_value(),
					$answers['is_true'],
					$row->answer_order,
					$row->question_answer_id
				);
				$wpdb->query( $update_sql );
			}

			set_transient( 'lp_upgrade_question_answers_offset', $offset + $limit, DAY_IN_SECONDS );

			LP_Debug::commitTransaction();
		}
		catch ( Exception $ex ) {
			LP_Debug::rollbackTransaction();
			error_log( sprintf( 'update_question_answers [%s]: %s', LEARNPRESS_VERSION, $ex->getMessage() ) );
		}

		return false;
	}

	public function update_quiz_settings() {
		global $wpdb;

		$offset = absint( get_transient( 'lp_upgrade_quiz_settings_offset' ) );
		$limit  = 100;

		$query = $wpdb->prepare( "
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type=%s
			AND post_status IN(%s, %s)
			ORDER BY ID ASC
			LIMIT $offset, $limit
		", 'lp_quiz', 'publish', 'draft' );

		if ( ! $rows = $wpdb->get_col( $query ) ) {
			return true;
		}

		try {
			LP_Debug::startTransaction();

			foreach ( $rows as $post_id ) {
				$query = $wpdb->prepare( "
					SELECT *
					FROM {$wpdb->postmeta}
					WHERE post_id=%d
				", $post_id );

				if ( ! $metas = $wpdb->get_results( $query ) ) {
					continue;
				}

				$update_meta = array();

				foreach ( $metas as $meta ) {
					switch ( $meta->meta_key ) {
						case '_lp_review_questions':
							$update_meta['_lp_review'] = $wpdb->prepare( '(%d, %s, %s)',
								$meta->post_id,
								'_lp_review',
								$meta->meta_value === 'yes' ? 'yes' : 'no'
							);
							break;

						case '_lp_retake_count':
							$update_meta['_lp_retry'] = $wpdb->prepare( '(%d, %s, %s)',
								$meta->post_id,
								'_lp_retry',
								$meta->meta_value > 0 ? 'yes' : 'no'
							);
							break;

						case '_lp_show_check_answer':
							$update_meta['_lp_instant_check'] = $wpdb->prepare( '(%d, %s, %s)',
								$meta->post_id,
								'_lp_instant_check',
								$meta->meta_value === '0' ? 'no' : 'yes'
							);
							break;
					}
				}

				if ( $update_meta ) {
					$wpdb->query( "INSERT INTO {$wpdb->postmeta}(`post_id`, `meta_key`, `meta_value`) VALUES" . join( ',', $update_meta ) );
				}

				$query = $wpdb->prepare( "
					DELETE FROM {$wpdb->postmeta}
					WHERE post_id = %d
				  	AND post_type IN(%s, %s, %s, %s)
				", array(
					$post_id,
					'_lp_review_questions',
					'_lp_retake_count',
					'_lp_show_check_answer',
					'_lp_show_result',
					'_lp_minus_points',
					'_lp_minus_skip_questions',
					'_lp_retake_count',
					'_lp_archive_history',
					'_lp_show_check_answer',
					'_lp_show_hint'
				) );
				error_log( 'update_quiz_settings [delete meta]' . $query );
				//$wpdb->query( $query );
			}

			set_transient( 'lp_upgrade_quiz_settings_offset', $offset + $limit, DAY_IN_SECONDS );

			LP_Debug::commitTransaction();
		}
		catch ( Exception $ex ) {
			LP_Debug::rollbackTransaction();
			error_log( sprintf( 'update_quiz_settings [%s]: %s', LEARNPRESS_VERSION, $ex->getMessage() ) );
		}

		return false;
	}

	public function delete_table_columns() {
		global $wpdb;

		return true;
		$wpdb->query( "
			ALTER TABLE
			DROP COLUMN `answer_data`,
			DROP COLUMN `answer_order`
		" );

		return true;
	}
}

$updater = new LP_Update_400();
$return  = $updater->update( LP_Request::get( 'force' ) == 'true' );

return array( 'done' => $return, 'percent' => $updater->get_percent() );