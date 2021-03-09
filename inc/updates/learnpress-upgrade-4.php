<?php
/**
 * Todo: update emails
 */

require_once dirname( __FILE__ ) . '/learnpress-update-base.php';

/**
 * Class LP_Upgrade_4
 *
 * Helper class for updating database to 4.0.0
 */
class LP_Upgrade_4 extends LP_Handle_Upgrade_Steps {
	/**
	 * @var LP_Upgrade_4
	 */
	protected static $instance = null;

	/**
	 * @return LP_Upgrade_4
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * LP_Upgrade_4 constructor.
	 */
	protected function __construct() {
		$this->version     = '4.0.0';
		$this->group_steps = apply_filters(
			'lp/upgrade/4/steps',
			array(
				'learnpress_user_items' => new LP_Group_Step(
					'learnpress_user_items',
					'Merge table learnpress_user_items',
					array(
						new LP_Step( 'clone_tables', 'Backup Tables',
							'learnpress_user_items, learnpress_user_itemmeta' ),
						new LP_Step( 'create_tables', 'Create tables', 'learnpress_user_item_results' ),
						new LP_Step( 'convert_data', 'Convert Datas',
							'Modify table learnpress_user_items, move the result of students on table learnpress_uset_itemmeta to learnpress_user_item_results' ),
						new LP_Step( 'convert_result_courses', 'Convert Data Result courses',
							'' ),
						new LP_Step( 'convert_result_questions', 'Convert Data Result questions',
							'' ),
					)
				),
			)
		);
	}

	/**
	 * Step Clone Tables
	 *
	 * @param array $data | keys: clone_table .
	 *
	 * @return LP_Step
	 */
	public function clone_tables( array $data ): LP_Step {
		$response        = new LP_Step( __FUNCTION__, '' );
		$key_clone_table = 'clone_table';
		$clone_table     = '';

		try {
			// All tables need clone.
			$lp_db                   = LP_Database::getInstance();
			$clone_tables            = array(
				$lp_db->tb_lp_user_items,
				$lp_db->tb_lp_user_itemmeta,
			);
			$total_tables_need_clone = count( $clone_tables );

			// Check table need clone.
			if ( empty( $data ) ) {
				$clone_table = $clone_tables[0];
			} elseif ( ! empty( $data[ $key_clone_table ] )
			           && in_array( $data[ $key_clone_table ], $clone_tables, true ) ) {
				$clone_table = $data[ $key_clone_table ];
			}

			if ( empty( $clone_table ) ) {
				// Finish this step.
				$response->percent = 100;
				$response->status  = 'finished';
				$response->message = 'Clone tables done';
			} else {
				// Clone table.
				$lp_db->clone_table( $clone_table );
				if ( $lp_db->wpdb->last_error ) {
					throw new Exception( 'Has error, please try again or contact supporter!' );
				}

				// Set param to clone table next.
				$index   = array_search( $clone_table, $clone_tables );
				$percent = ( $index + 1 ) * 100 / $total_tables_need_clone;
				++ $index;

				if ( ! empty( $clone_tables[ $index ] ) ) {
					$response->status            = 'success';
					$response->percent           = $percent;
					$response->data->clone_table = $clone_tables[ $index ];
					$response->message           = 'Table "' . $clone_table . '" cloned';
				} else {
					// Finish this step.
					$response->status  = 'finished';
					$response->percent = 100;
					$response->message = 'Table "' . $clone_table . '" cloned - Clone tables success';
				}
			}
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Step Create Tables
	 *
	 * @param array $data
	 *
	 * @return LP_Step
	 */
	public function create_tables( array $data ): LP_Step {
		$response         = new LP_Step( __FUNCTION__, '' );
		$key_create_table = 'create_table';
		$create_table     = '';

		try {
			/**
			 * All tables need create.
			 * key: name table
			 * value: function handle create this table
			 *
			 * @see LP_Database::create_tb_lp_user_item_results
			 */
			$lp_db                    = LP_Database::getInstance();
			$create_tables            = array(
				'tb_lp_user_item_results',
			);
			$total_tables_need_create = count( $create_tables );

			// Check table need create.
			if ( empty( $data ) ) {
				$create_table = $create_tables[0];
			} elseif ( ! empty( $data[ $key_create_table ] )
			           && in_array( $data[ $key_create_table ], $create_tables, true ) ) {
				$create_table = $data[ $key_create_table ];
			}

			if ( empty( $create_table ) ) {
				// Finish this step.
				$response->percent = 100;
				$response->status  = 'finished';
				$response->message = 'Create tables done';
			} else {
				// Create table.
				$name_method = 'create_' . $create_table;
				if ( ! method_exists( $lp_db, $name_method ) ) {
					throw new Exception( 'Not found method ' . $name_method . ' of Class ' . get_class( $lp_db ) );
				}
				$lp_db->{$name_method}();

				if ( $lp_db->wpdb->last_error ) {
					return $response;
				}

				// Set param to clone table next.
				$index = array_search( $create_table, $create_tables );

				$percent = ( $index + 1 ) * 100 / $total_tables_need_create;
				++ $index;

				if ( ! empty( $create_tables[ $index ] ) ) {
					$response->status            = 'success';
					$response->percent           = $percent;
					$response->data->clone_table = $create_tables[ $index ];
					$response->message           = 'Table "' . $create_table . '" created';
				} else {
					// Finish this step.
					$response->status  = 'finished';
					$response->percent = 100;
					$response->message = 'Table "' . $create_table . '" created - Create tables success';
				}
			}
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Step Create Tables
	 *
	 * @param array $data
	 *
	 * @return LP_Step
	 */
	public function convert_data( array $data ): LP_Step {
		$response         = new LP_Step( __FUNCTION__, '' );
		$key_convert_data = 'convert_data';
		$handle           = '';

		try {
			/**
			 * All tables need create.
			 * key: name table
			 * value: function handle create this table
			 *
			 * @see LP_Database::create_tb_lp_user_item_results
			 */
			$lp_db        = LP_Database::getInstance();
			$handles      = array(
				'modify_table_learnpress_user_items',
			);
			$total_handle = count( $handles );

			// Check table need create.
			if ( empty( $data ) ) {
				$handle = $handles[0];
			} elseif ( ! empty( $data[ $key_convert_data ] )
			           && in_array( $data[ $key_convert_data ], $handles, true ) ) {
				$handle = $data[ $key_convert_data ];
			}

			if ( empty( $handle ) ) {
				// Finish this step.
				$response->percent = 100;
				$response->status  = 'finished';
				$response->message = 'Create tables done';
			} else {
				// handles.
				switch ( $handle ) {
					case 'modify_table_learnpress_user_items' :
						$this->modify_tb_learnpress_user_items( $lp_db );
						break;
				}

				// Set param to handle next.
				$index = array_search( $handle, $handles );

				$percent = ( $index + 1 ) * 100 / $total_handle;
				++ $index;

				if ( ! empty( $handles[ $index ] ) ) {
					$response->status            = 'success';
					$response->percent           = $percent;
					$response->data->clone_table = $handles[ $index ];
					$response->message           = $handle . '" success';
				} else {
					// Finish this step.
					$response->status  = 'finished';
					$response->percent = 100;
					$response->message = $handle . '" success - Handles success';
				}
			}
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Create table learnpress_user_item_results
	 *
	 * @param LP_Database $lp_db .
	 *
	 * @return bool|int
	 */
	public function modify_tb_learnpress_user_items( LP_Database $lp_db ) {
		$lp_db->wpdb->query( "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'" );

		// Add column graduation.
		$query = $lp_db->wpdb->prepare( "
				ALTER TABLE $lp_db->tb_lp_user_items
				ADD COLUMN IF NOT EXISTS graduation varchar(20) AFTER `status`;
			", 1
		);
		$lp_db->wpdb->query( $query );

		// Add column access level.
		$query = $lp_db->wpdb->prepare( "
				ALTER TABLE $lp_db->tb_lp_user_items
				ADD COLUMN IF NOT EXISTS access_level int(3);
			", 1
		);
		$lp_db->wpdb->query( $query );

		// Convert type column start_time, end_time.
		$this->alter_datetime_default_value( $lp_db );

		/**
		 * Check column start_time_gmt exists.
		 *
		 * Copy value start_time_gmt to start_time
		 *
		 * Drop column start_time_gmt
		 */
		$check_col_start_time_gmt = $lp_db->check_col_table( $lp_db->tb_lp_user_items, 'start_time_gmt' );
		if ( $check_col_start_time_gmt ) {
			$query = $lp_db->wpdb->prepare(
				"
				UPDATE $lp_db->tb_lp_user_items SET start_time = start_time_gmt WHERE user_item_id > 0;
				", 1
			);
			$lp_db->wpdb->query( $query );
			$lp_db->drop_col_table( $lp_db->tb_lp_user_items, 'start_time_gmt' );
		}

		/**
		 * Check column end_time_gmt exists.
		 *
		 * Copy value end_time_gmt to end_time
		 *
		 * Drop column end_time_gmt
		 */
		$check_col_end_time_gmt = $lp_db->check_col_table( $lp_db->tb_lp_user_items, 'end_time_gmt' );
		if ( $check_col_end_time_gmt ) {
			$query = $lp_db->wpdb->prepare(
				"
				UPDATE $lp_db->tb_lp_user_items SET end_time = end_time_gmt WHERE user_item_id > 0;
				", 1
			);

			$lp_db->wpdb->query( $query );
			$lp_db->drop_col_table( $lp_db->tb_lp_user_items, 'end_time_gmt' );
		}

		// Copy value of meta_key "grade" table learnpress_user_itemmeta LP3 to column graduation LP4 table learnpress_user_items
		$query = $lp_db->wpdb->prepare(
			"
					UPDATE $lp_db->tb_lp_user_items AS u
					SET u.graduation = (
					    SELECT u_meta.meta_value
					    FROM $lp_db->tb_lp_user_itemmeta AS u_meta
					    WHERE u.user_item_id = u_meta.learnpress_user_item_id
					    AND u_meta.meta_key = 'grade'
					)
					WHERE u.user_item_id > 0;
				", 1
		);

		$lp_db->wpdb->query( $query );

		// Copy value results evalue table learnpress_user_itemmeta LP3 to column graduation LP4 table learnpress_user_items

	}

	/**
	 * Convert value of meta_key "course_results_evaluate_{type}" table learnpress_user_itemmeta LP3 to column graduation LP4 table learnpress_user_item_results
	 *
	 * @return LP_Step
	 */
	public function convert_result_courses( array $data ): LP_Step {
		$response = new LP_Step( __FUNCTION__, '' );
		$offset   = 0;

		try {
			// Check table need create.
			if ( empty( $data ) ) {
				// Check total rows.
				$lp_db = LP_Database::getInstance();
				$query = $lp_db->wpdb->prepare(
					"
				SELECT COUNT(DISTINCT learnpress_user_item_id) FROM $lp_db->tb_lp_user_itemmeta
				WHERE meta_key LIKE 'course_results_evaluate_%'
				", 1
				);

				$response->data->total_rows = (int) $lp_db->wpdb->get_var( $query );
			} else {
				$offset = 5 * $data['p'];
			}

			// Convert 5 rows.
			$lp_db        = LP_Database::getInstance();
			$query        = $lp_db->wpdb->prepare(
				"
				SELECT learnpress_user_item_id AS user_item_id, meta_value FROM $lp_db->tb_lp_user_itemmeta
				WHERE meta_key LIKE 'course_results_evaluate_%'
				LIMIT 5 offset %d
			", $offset
			);
			$result_query = $lp_db->wpdb->get_results( $query );

			if ( 0 === count( $result_query ) ) {
				$response->message = 'Convert result success';
				$response->status  = 'finished';

				return $response;
			}

			foreach ( $result_query as $result ) {
				$result_unserialize = unserialize( $result->meta_value );
				$result_json        = json_encode( $result_unserialize );

				// Check exists user_item_id on table learnpress_user_item_results.
				$check = $lp_db->wpdb->get_var(
					$lp_db->wpdb->prepare(
						"
						SELECT user_item_id FROM $lp_db->tb_lp_user_item_results
						WHERE user_item_id = %d
						", $result->user_item_id
					)
				);

				if ( empty( $check ) ) {
					$lp_db->wpdb->insert( $lp_db->tb_lp_user_item_results,
						array( 'result' => $result_json, 'user_item_id' => $result->user_item_id ) );
				} else {
					// Update.
				}

				$response->status  = 'success';
				$response->message = 'Insert success';
				$response->data->p = ++ $offset;
			}
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}


		return $response;
	}

	/**
	 * Convert value of meta_key "_question_answers" table learnpress_user_itemmeta LP3 to column graduation LP4 table learnpress_user_item_results
	 * Convert value of meta_key "_question_answers" table learnpress_user_itemmeta LP3 to column graduation LP4 table learnpress_user_item_results
	 *
	 * @return LP_Step
	 */
	public function convert_result_questions( array $data ): LP_Step {
		$response = new LP_Step( __FUNCTION__, '' );
		$offset   = 0;

		try {
			if ( empty( $data ) ) {
				// Check total rows.
				$lp_db = LP_Database::getInstance();
				$query = $lp_db->wpdb->prepare(
					"
				SELECT COUNT(learnpress_user_item_id) FROM $lp_db->tb_lp_user_itemmeta
				WHERE meta_key = '_question_answers'
				ORDER BY learnpress_user_item_id
				", 1
				);

				$response->data->total_rows = (int) $lp_db->wpdb->get_var( $query );
			} else {
				$offset = 5 * $data['p'];
			}

			// Convert 5 rows.
			$lp_db                    = LP_Database::getInstance();
			$query                    = $lp_db->wpdb->prepare(
				"
				SELECT learnpress_user_item_id AS user_item_id, meta_value AS answered
				FROM $lp_db->tb_lp_user_itemmeta
				WHERE meta_key = '_question_answers'
				LIMIT 5 offset %d
				", $offset
			);
			$quizs_questions_answered = $lp_db->wpdb->get_results( $query );

			if ( 0 === count( $quizs_questions_answered ) ) {
				$response->message = 'Convert result question success';
				$response->status  = 'finished';

				return $response;
			}

			foreach ( $quizs_questions_answered as $quiz_question_answered ) {
				$query_quiz_result = $lp_db->wpdb->prepare(
					"
					SELECT meta_value AS quiz_result FROM $lp_db->tb_lp_user_itemmeta
					WHERE learnpress_user_item_id = %d
					AND meta_key = %s
					", $quiz_question_answered->user_item_id, 'results'
				);
				$quiz_result       = unserialize( $lp_db->wpdb->get_var( $query_quiz_result ) );

				$questions_answered = unserialize( $quiz_question_answered->answered );

				foreach ( $questions_answered as $question_id => $question_answered ) {
					if ( isset( $quiz_result['questions'][ $question_id ] ) ) {
						$quiz_result['questions'][ $question_id ]['answered'] = $question_answered;
					}
				}

				$result_json = json_encode( $quiz_result );


				// Check exists user_item_id on table learnpress_user_item_results.
				$check = $lp_db->wpdb->get_var(
					$lp_db->wpdb->prepare(
						"
						SELECT user_item_id FROM $lp_db->tb_lp_user_item_results
						WHERE user_item_id = %d
						", $quiz_question_answered->user_item_id
					)
				);

				if ( empty( $check ) ) {
					$lp_db->wpdb->insert( $lp_db->tb_lp_user_item_results,
						array( 'result' => $result_json, 'user_item_id' => $quiz_question_answered->user_item_id ) );
				} else {
//					$lp_db->wpdb->update( $lp_db->tb_lp_user_item_results,
//						array( 'result' => $result_json ),
//						array( 'user_item_id' => $quiz_question_answered->user_item_id ) );
				}


				$response->status  = 'success';
				$response->message = 'Insert success';
				$response->data->p = ++ $offset;
			}
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}


		return $response;
	}

	public function create_table_index(): LP_Step {

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
	 * @param LP_Database $lp_db .
	 *
	 * @return bool|int
	 */
	public function alter_datetime_default_value( LP_Database $lp_db ) {
		$query = $lp_db->wpdb->prepare(
			"
				ALTER TABLE $lp_db->tb_lp_user_items
				MODIFY start_time datetime default null,
				MODIFY end_time datetime default null;
			", 1
		);

		return $lp_db->wpdb->query( $query );
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
				$wpdb->query(
					"
					ALTER TABLE {$wpdb->learnpress_sections}
					DROP INDEX `{$index}`;
				"
				);
			} catch ( Exception $ex ) {
			}
		}

		$wpdb->query(
			"
			ALTER TABLE {$wpdb->learnpress_user_items}
			ADD INDEX `section_course_id` (`section_course_id` ASC),
			ADD INDEX `section_id` (`section_id` ASC),
			ADD INDEX `item_id` (`item_id` ASC);
		"
		);
	}

	protected function alter_section_items() {
		global $wpdb;

		foreach ( array( 'section_course_id', 'section_id', 'item_id' ) as $index ) {
			try {
				$wpdb->query(
					"
					ALTER TABLE {$wpdb->learnpress_sections}
					DROP INDEX `{$index}`;
				"
				);
			} catch ( Exception $ex ) {
			}
		}

		$wpdb->query(
			"
			ALTER TABLE {$wpdb->learnpress_user_items}
			ADD INDEX `section_course_id` (`section_course_id` ASC),
			ADD INDEX `section_id` (`section_id` ASC),
			ADD INDEX `item_id` (`item_id` ASC);
		"
		);
	}

	protected function alter_order_itemmeta() {
		global $wpdb;

		// wp_learnpress_order_itemmeta
		$wpdb->query(
			"
			ALTER TABLE {$wpdb->learnpress_order_itemmeta}
			CHANGE COLUMN `meta_key` `meta_key` VARCHAR(255) NOT NULL DEFAULT '' ;
		"
		);

		foreach ( array( 'learnpress_order_item_id', 'meta_key' ) as $index ) {
			$wpdb->query(
				"
				ALTER TABLE {$wpdb->learnpress_order_itemmeta}
				DROP INDEX `{$index}`;
			"
			);
		}

		$wpdb->query(
			"
			ALTER TABLE {$wpdb->learnpress_order_itemmeta}
			ADD INDEX `learnpress_order_item_id` (`learnpress_order_item_id` ASC),
			ADD INDEX `meta_key` (`meta_key`(191) ASC);
		"
		);
	}

	protected function alter_order_items() {
		global $wpdb;

		$query = "
     		 ALTER TABLE `{$wpdb->prefix}learnpress_order_items`
     		 ADD `order_item_type` varchar(200) NULL DEFAULT NULL AFTER `order_item_name`;
		";
		$wpdb->query( $query );

		foreach ( array( 'order_id' ) as $index ) {
			$wpdb->query(
				"
				ALTER TABLE {$wpdb->learnpress_order_items}
				DROP INDEX `{$index}`;
			"
			);
		}

		$wpdb->query(
			"
			ALTER TABLE {$wpdb->learnpress_order_items}
			ADD INDEX `order_id` (`order_id` ASC);
		"
		);
	}

	protected function alter_question_answermeta() {
		global $wpdb;

		$wpdb->query(
			"
			ALTER TABLE {$wpdb->learnpress_question_answermeta}
			CHANGE COLUMN `meta_key` `meta_key` VARCHAR(255) NOT NULL DEFAULT '',
			CHANGE COLUMN `meta_value` `meta_value` longtext NULL DEFAULT '';
		"
		);

		foreach ( array( 'learnpress_question_answer_id', 'meta_key' ) as $index ) {
			$wpdb->query(
				"
				ALTER TABLE {$wpdb->learnpress_question_answermeta}
				DROP INDEX `{$index}`;
			"
			);
		}

		$wpdb->query(
			"
			ALTER TABLE {$wpdb->learnpress_question_answermeta}
			ADD INDEX `learnpress_question_answer_id` (`learnpress_question_answer_id` ASC),
			ADD INDEX `meta_key` (`meta_key`(191) ASC);
		"
		);
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
			$wpdb->query(
				"
				ALTER TABLE {$wpdb->learnpress_question_answers}
				DROP INDEX `{$index}`;
			"
			);
		}

		$wpdb->query(
			"
			ALTER TABLE {$wpdb->learnpress_question_answers}
			ADD INDEX `question_id` (`question_id` ASC);
		"
		);
	}

	protected function alter_user_itemmeta() {
		global $wpdb;

		$wpdb->query(
			"
			ALTER TABLE {$wpdb->learnpress_user_itemmeta}
			CHANGE COLUMN `meta_key` `meta_key` VARCHAR(255) NOT NULL DEFAULT '' ;
		"
		);

		foreach ( array( 'learnpress_user_item_id', 'meta_key' ) as $index ) {
			$wpdb->query(
				"
				ALTER TABLE {$wpdb->learnpress_user_itemmeta}
				DROP INDEX `{$index}`;
			"
			);
		}

		$wpdb->query(
			"
			ALTER TABLE {$wpdb->learnpress_user_itemmeta}
			ADD INDEX `learnpress_user_item_id` (`learnpress_user_item_id` ASC),
			ADD INDEX `meta_key` (`meta_key`(191) ASC);
		"
		);
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
			$wpdb->query(
				"
				ALTER TABLE {$wpdb->learnpress_user_items}
				DROP INDEX `{$index}`;
			"
			);
		}

		$wpdb->query(
			"
			ALTER TABLE {$wpdb->learnpress_user_items}
			ADD INDEX `parent_id` (`parent_id` ASC),
			ADD INDEX `user_id` (`user_id` ASC),
			ADD INDEX `item_id` (`item_id` ASC),
			ADD INDEX `ref_id` (`ref_id` ASC);
		"
		);
	}

	protected function alter_quiz_questions() {
		global $wpdb;

		foreach ( array( 'quiz_id', 'question_id' ) as $index ) {
			$wpdb->query(
				"
				ALTER TABLE {$wpdb->learnpress_quiz_questions}
				DROP INDEX `{$index}`;
			"
			);
		}

		$wpdb->query(
			"
			ALTER TABLE {$wpdb->learnpress_quiz_questions}
			ADD INDEX `quiz_id` (`quiz_id` ASC),
			ADD INDEX `question_id` (`question_id` ASC);
		"
		);
	}

	/**
	 * Update expiration time for all user items
	 */
	public function update_expiration_time() {
		global $wpdb;
		// $query = $wpdb->prepare( "
		// SELECT p.ID id, pm.meta_value duration
		// FROM {$wpdb->posts} p
		// INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
		// WHERE
		// p.post_type IN(%s, %s) AND meta_value NOT LIKE %s
		// ", '_lp_duration', LP_COURSE_CPT, LP_QUIZ_CPT, $wpdb->esc_like( '0 ' ) . '%' );

		// if ( ! $posts = $wpdb->get_results( $query ) ) {
		// return true;
		// }
		//
		// LP_Debug::instance()->add( $query, 'upgrade' );
		// LP_Debug::instance()->add( $posts, 'upgrade' );

		try {
			$offset = absint( get_transient( 'lp_upgrade_user_items_offset' ) );
			$limit  = 500;

			/**
			 * Join user-items with post-meta to get user-items and duration.
			 */
			// $query = $wpdb->prepare( "
			// SELECT ui.*, pm.meta_value as duration
			// FROM {$wpdb->learnpress_user_items} ui
			// INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = ui.item_id AND pm.meta_key = %s
			// WHERE ui.start_time <> %s AND ui.start_time <> %s
			// ORDER BY user_item_id ASC
			// LIMIT %d, %d
			// ", '_lp_duration', '0000-00-00 00:00:00', '', $offset, $limit );

			$query = $wpdb->prepare(
				"
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
			",
				'_lp_duration',
				'0000-00-00 00:00:00',
				'',
				$offset,
				$limit
			);

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
					$expiration_time = learn_press_date_end_from( $duration->get(),
						strtotime( $user_item->start_time ) );
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
		} catch ( Exception $ex ) {
			LP_Debug::rollbackTransaction();
		}

		return false;
	}

	public function update_item_graduation() {
		global $wpdb;

		$query = $wpdb->prepare(
			"
			UPDATE {$wpdb->learnpress_user_items} ui
			SET graduation = (
				SELECT meta_value
				FROM {$wpdb->learnpress_user_itemmeta}
				WHERE meta_key = %s
				AND learnpress_user_item_id = ui.user_item_id
			)
			WHERE ui.u = %d
		",
			'grade',
			1
		);
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

		$query = $wpdb->prepare(
			"
			UPDATE {$wpdb->learnpress_user_items}
			SET
				start_time = (@temp:=start_time), start_time = start_time_gmt, start_time_gmt = @temp,
				end_time = (@temp:=end_time), end_time = end_time_gmt, end_time_gmt = @temp
			WHERE %d
		",
			1
		);

		$wpdb->query( $query );

		return true;
	}

	/**
	 * @return bool
	 */
	public function remove_time_gmt() {
		global $wpdb;

		// $wpdb->query( "ALTER TABLE {$wpdb->learnpress_user_items} DROP `start_time_gmt`, DROP `end_time_gmt`" );

		return true;
	}

	public function update_question_answers() {
		global $wpdb;

		$offset = absint( get_transient( 'lp_upgrade_question_answers_offset' ) );
		$limit  = 500;

		$query = $wpdb->prepare(
			"
			SELECT *
			FROM {$wpdb->learnpress_question_answers}
			ORDER BY question_answer_id ASC
			LIMIT %d, %d
		",
			$offset,
			$limit
		);

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
						'is_true' => '',
					)
				);
				$update_sql = $wpdb->prepare(
					"
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
		} catch ( Exception $ex ) {
			LP_Debug::rollbackTransaction();
			error_log( sprintf( 'update_question_answers [%s]: %s', LEARNPRESS_VERSION, $ex->getMessage() ) );
		}

		return false;
	}

	public function update_quiz_settings() {
		global $wpdb;

		$offset = absint( get_transient( 'lp_upgrade_quiz_settings_offset' ) );
		$limit  = 100;

		$query = $wpdb->prepare(
			"
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type=%s
			AND post_status IN(%s, %s)
			ORDER BY ID ASC
			LIMIT $offset, $limit
		",
			'lp_quiz',
			'publish',
			'draft'
		);

		if ( ! $rows = $wpdb->get_col( $query ) ) {
			return true;
		}

		try {
			LP_Debug::startTransaction();

			foreach ( $rows as $post_id ) {
				$query = $wpdb->prepare(
					"
					SELECT *
					FROM {$wpdb->postmeta}
					WHERE post_id=%d
				",
					$post_id
				);

				if ( ! $metas = $wpdb->get_results( $query ) ) {
					continue;
				}

				$update_meta = array();

				foreach ( $metas as $meta ) {
					switch ( $meta->meta_key ) {
						case '_lp_review_questions':
							$update_meta['_lp_review'] = $wpdb->prepare(
								'(%d, %s, %s)',
								$meta->post_id,
								'_lp_review',
								$meta->meta_value === 'yes' ? 'yes' : 'no'
							);
							break;

						case '_lp_show_check_answer':
							$update_meta['_lp_instant_check'] = $wpdb->prepare(
								'(%d, %s, %s)',
								$meta->post_id,
								'_lp_instant_check',
								$meta->meta_value === '0' ? 'no' : 'yes'
							);
							break;
					}
				}

				if ( $update_meta ) {
					$wpdb->query( "INSERT INTO {$wpdb->postmeta}(`post_id`, `meta_key`, `meta_value`) VALUES" . join( ',',
							$update_meta ) );
				}

				$query = $wpdb->prepare(
					"
					DELETE FROM {$wpdb->postmeta}
					WHERE post_id = %d
				  	AND post_type IN(%s, %s, %s, %s)
				",
					array(
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
						'_lp_show_hint',
					)
				);
				error_log( 'update_quiz_settings [delete meta]' . $query );
				// $wpdb->query( $query );
			}

			set_transient( 'lp_upgrade_quiz_settings_offset', $offset + $limit, DAY_IN_SECONDS );

			LP_Debug::commitTransaction();
		} catch ( Exception $ex ) {
			LP_Debug::rollbackTransaction();
			error_log( sprintf( 'update_quiz_settings [%s]: %s', LEARNPRESS_VERSION, $ex->getMessage() ) );
		}

		return false;
	}

	public function delete_table_columns() {
		global $wpdb;

		return true;
		$wpdb->query(
			'
			ALTER TABLE
			DROP COLUMN `answer_data`,
			DROP COLUMN `answer_order`
		'
		);

		return true;
	}
}

LP_Upgrade_4::get_instance();
