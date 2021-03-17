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
	 *
	 * @see clone_tables
	 * @see create_tables
	 * @see modify_tb_lp_user_items
	 * @see modify_tb_learnpress_user_itemmeta
	 * @see convert_result_graduation_item
	 * @see convert_result_questions
	 * @see modify_tb_lp_user_itemmeta
	 * @see remove_data_lp_user_itemmeta
	 * @see modify_tb_lp_quiz_questions
	 * @see modify_tb_lp_question_answers
	 * @see update_question_answers
	 * @see modify_tb_lp_question_answermeta
	 * @see convert_question_type_fill_in_blank
	 * @see delete_columns_question_answers
	 * @see modify_tb_lp_order_items
	 * @see modify_tb_lp_order_itemmeta
	 * @see modify_tb_lp_sections
	 * @see modify_tb_lp_section_items
	 * @see convert_lp_settings
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
						new LP_Step(
							'modify_tb_lp_user_items',
							'Modify table learnpress_user_items',
							'Modify table learnpress_user_items, move the result of students on table learnpress_uset_itemmeta to learnpress_user_item_results'
						),
						new LP_Step(
							'convert_result_graduation_item',
							'Convert Data Result courses, items\' courses',
							''
						),
						new LP_Step(
							'convert_result_questions',
							'Convert Data Result questions',
							''
						),
						new LP_Step(
							'remove_data_lp_user_itemmeta',
							'Remove data results of lp_user_itemmeta',
							''
						),
						new LP_Step(
							'modify_tb_lp_user_itemmeta',
							'Modify table learnpress_user_itemmeta',
							'Change Column "learnpress_user_item_id" to "user_item_id", Create Index: user_item_id, meta_key, meta_value'
						),
						new LP_Step(
							'modify_tb_lp_quiz_questions',
							'Modify table learnpress_quiz_questions',
							''
						),
						new LP_Step(
							'modify_tb_lp_question_answers',
							'Modify table learnpress_question_answers',
							''
						),
						new LP_Step(
							'update_question_answers',
							'Update data table learnpress_question_answers',
							''
						),
						new LP_Step(
							'modify_tb_lp_question_answermeta',
							'Modify table learnpress_question_answermeta',
							''
						),
						new LP_Step(
							'convert_question_type_fill_in_blank',
							'Update data question type "Fill in blank"',
							''
						),
						new LP_Step(
							'delete_columns_question_answers',
							'Delete data table learnpress_question_answers',
							''
						),
						new LP_Step(
							'modify_tb_lp_order_items',
							'Modify data table learnpress_order_items',
							''
						),
						new LP_Step(
							'modify_tb_lp_order_itemmeta',
							'Modify data table learnpress_order_itemmeta',
							''
						),
						new LP_Step(
							'modify_tb_lp_sections',
							'Modify table learnpress_sections',
							'Create Index'
						),
						new LP_Step(
							'modify_tb_lp_section_items',
							'Modify table learnpress_section_items',
							'Create Index'
						),
						new LP_Step(
							'convert_lp_settings',
							'Convert data settings learnpress',
							'Courses thumbnail dimensions, Profile thumbnail dimensions, Profile rename dashboard to overview, Block course by duration, Block course when finished, Assessment course by quizzes - Evaluate, '
						)
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
	protected function clone_tables( array $data ): LP_Step {
		$response        = new LP_Step( __FUNCTION__, '' );
		$key_clone_table = 'clone_table';
		$clone_table     = '';

		try {
			// All tables need clone.
			$lp_db = LP_Database::getInstance();
			$lp_db->wpdb->query( "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'" );

			$clone_tables = array(
				$lp_db->tb_lp_user_items,
				$lp_db->tb_lp_user_itemmeta,
				$lp_db->tb_lp_question_answers,
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
				$this->finish_step( $response, __FUNCTION__ . ' finished' );
			} else {
				// Clone table.
				$lp_db->clone_table( $clone_table );
				if ( $lp_db->wpdb->last_error ) {
					throw new Exception( 'Has error, please try again or contact supporter!' );
				}

				// Set param to clone table next.
				$index = array_search( $clone_table, $clone_tables );
				++ $index;
				$percent = LP_Helper::progress_percent( $index, 1, $total_tables_need_clone );

				if ( ! empty( $clone_tables[ $index ] ) ) {
					$response->status            = 'success';
					$response->percent           = $percent;
					$response->data->clone_table = $clone_tables[ $index ];
					$response->message           = 'Table "' . $clone_table . '" cloned';
				} else {
					// Finish this step.
					$this->finish_step( $response, __FUNCTION__ . ' finished' );
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
	protected function create_tables( array $data ): LP_Step {
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
				$this->finish_step( $response, __FUNCTION__ . ' finished' );
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
				++ $index;
				$percent = LP_Helper::progress_percent( $index, 1, $total_tables_need_create );

				if ( ! empty( $create_tables[ $index ] ) ) {
					$response->status            = 'success';
					$response->percent           = $percent;
					$response->data->clone_table = $create_tables[ $index ];
					$response->message           = 'Table "' . $create_table . '" created';
				} else {
					// Finish this step.
					$this->finish_step( $response, __FUNCTION__ . ' finished' );
				}
			}
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Modify table learnpress_user_items .
	 *
	 * @return LP_Step
	 */
	protected function modify_tb_lp_user_items() {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		try {
			// Add columns: graduation, access_level .
			$lp_db->wpdb->query(
				"
				ALTER TABLE $lp_db->tb_lp_user_items
				ADD COLUMN IF NOT EXISTS graduation varchar(20) AFTER `status`,
				ADD COLUMN IF NOT EXISTS access_level int(3)
				"
			);

			// Change type columns: start_time, end_time.
			$lp_db->wpdb->query(
				"
				ALTER TABLE $lp_db->tb_lp_user_items
				MODIFY start_time datetime default null,
				MODIFY end_time datetime default null;
				"
			);

			/**
			 * Check column start_time_gmt exists.
			 *
			 * Copy value start_time_gmt to start_time
			 *
			 * Drop column start_time_gmt
			 */
			$check_col_start_time_gmt = $lp_db->check_col_table( $lp_db->tb_lp_user_items, 'start_time_gmt' );
			if ( $check_col_start_time_gmt ) {
				$lp_db->wpdb->query( "UPDATE $lp_db->tb_lp_user_items SET start_time = start_time_gmt WHERE user_item_id > 0" );
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
				$lp_db->wpdb->query( "UPDATE $lp_db->tb_lp_user_items SET end_time = end_time_gmt WHERE user_item_id > 0" );
				$lp_db->drop_col_table( $lp_db->tb_lp_user_items, 'end_time_gmt' );
			}

			// Create index.
			$indexs = array( 'user_id', 'item_id', 'item_type', 'status', 'ref_type', 'ref_id', 'parent_id' );
			$lp_db->add_indexs_table( $lp_db->tb_lp_user_items, $indexs );
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $this->finish_step( $response, 'Step ' . __FUNCTION__ . ' finished' );
	}

	/**
	 * Convert value of meta_key "grade" table learnpress_user_itemmeta LP3 to column graduation LP4 table learnpress_user_item_results
	 *
	 * @param array $data .
	 *
	 * @return LP_Step
	 */
	protected function convert_result_graduation_item( array $data ): LP_Step {
		$response  = new LP_Step( __FUNCTION__, '' );
		$lp_db     = LP_Database::getInstance();
		$page      = 0;
		$offset    = 0;
		$limit     = 100;
		$total_row = 0;

		try {
			if ( empty( $data ) ) {
				// Check total rows.
				$query = $lp_db->wpdb->prepare(
					"
					SELECT COUNT(learnpress_user_item_id) FROM $lp_db->tb_lp_user_itemmeta
					WHERE meta_key = %s
					", 'grade'
				);

				$total_row = $response->data->total_rows = (int) $lp_db->wpdb->get_var( $query );
			} else {
				$page      = $data['p'];
				$offset    = $limit * $page;
				$total_row = $data['total_rows'];
			}

			// Select .
			$query            = $lp_db->wpdb->prepare(
				"
				    SELECT learnpress_user_item_id AS user_item_id, meta_value AS grade
				    FROM $lp_db->tb_lp_user_itemmeta
				    WHERE meta_key = %s
					LIMIT %d, %d
				", 'grade', $offset, $limit
			);
			$user_item_grades = $lp_db->wpdb->get_results( $query );

			$percent = LP_Helper::progress_percent( $offset, $limit, $total_row );

			if ( empty( $user_item_grades ) || 100 === $percent ) {
				return $this->finish_step( $response, __FUNCTION__ . ' finished' );
			}

			/**
			 * Copy value of meta_key "grade" table learnpress_user_itemmeta LP3
			 * to column "graduation" LP4 table learnpress_user_items
			 */
			foreach ( $user_item_grades as $user_item_grade ) {
				$query = $lp_db->wpdb->prepare(
					"
					UPDATE $lp_db->tb_lp_user_items
					SET graduation = %s
					WHERE user_item_id = %d;
				", $user_item_grade->grade, $user_item_grade->user_item_id
				);

				$lp_db->wpdb->query( $query );
			}

			$response->status           = 'success';
			$response->message          = 'Insert success';
			$response->percent          = $percent;
			$response->data->p          = ++ $page;
			$response->data->total_rows = $total_row;
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Convert value of meta_key "_question_answers" table learnpress_user_itemmeta LP3 to column result LP4 table learnpress_user_item_results
	 *
	 * @param array $data .
	 *
	 * @return LP_Step
	 */
	protected function convert_result_questions( array $data ): LP_Step {
		$response  = new LP_Step( __FUNCTION__, '' );
		$lp_db     = LP_Database::getInstance();
		$page      = 0;
		$offset    = 0;
		$limit     = 100;
		$total_row = 0;

		try {
			if ( empty( $data ) ) {
				// Check total rows.
				$query = $lp_db->wpdb->prepare(
					"
					SELECT COUNT(learnpress_user_item_id) FROM $lp_db->tb_lp_user_itemmeta
					WHERE meta_key = '_question_answers'
					", 1
				);

				$total_row = $response->data->total_rows = (int) $lp_db->wpdb->get_var( $query );
			} else {
				$page      = $data['p'];
				$offset    = $limit * $page;
				$total_row = $data['total_rows'];
			}

			// Convert rows.
			$query                    = $lp_db->wpdb->prepare(
				"
				SELECT learnpress_user_item_id AS user_item_id, meta_value AS answered
				FROM $lp_db->tb_lp_user_itemmeta
				WHERE meta_key = '_question_answers'
				LIMIT %d offset %d
				", $limit, $offset
			);
			$quizs_questions_answered = $lp_db->wpdb->get_results( $query );

			if ( 0 === count( $quizs_questions_answered ) ) {
				return $this->finish_step( $response, 'Convert result question success' );
			}

			foreach ( $quizs_questions_answered as $quiz_question_answered ) {
				$query_quiz_result = $lp_db->wpdb->get_var(
					$lp_db->wpdb->prepare(
						"
						SELECT meta_value AS quiz_result FROM $lp_db->tb_lp_user_itemmeta
						WHERE learnpress_user_item_id = %d
						AND meta_key = %s
						", $quiz_question_answered->user_item_id, 'results'
					)
				);

				$quiz_result        = maybe_unserialize( ( $query_quiz_result ) );
				$questions_answered = maybe_unserialize( $quiz_question_answered->answered );

				if ( ! $quiz_result || ! $questions_answered ) {
					continue;
				}

				foreach ( $questions_answered as $question_id => $question_answered ) {
					if ( isset( $quiz_result['questions'][ $question_id ] ) ) {
						$quiz_result['questions'][ $question_id ]['answered'] = $question_answered;
					}
				}

				$result_json = json_encode( $quiz_result );

				// Check exists user_item_id value on table learnpress_user_item_results.
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
				}
			}

			$percent = LP_Helper::progress_percent( $offset, $limit, $total_row );

			$response->status           = 'success';
			$response->message          = 'Insert success';
			$response->percent          = $percent;
			$response->data->p          = ++ $page;
			$response->data->total_rows = $total_row;
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Modify table learnpress_user_itemmeta .
	 * Must run after Step "convert_result_questions"
	 * Because change name column name 'learnpress_user_item_id' to 'user_item_id' and meta_key change type from 'text' to varchar(255)
	 *
	 * @return LP_Step
	 */
	protected function modify_tb_lp_user_itemmeta(): LP_Step {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		try {
			$lp_db->wpdb->query(
				"
				ALTER TABLE {$lp_db->tb_lp_user_itemmeta}
				CHANGE COLUMN `meta_key` `meta_key` VARCHAR(255) NOT NULL DEFAULT '',
				CHANGE COLUMN `meta_value` `meta_value` VARCHAR(255) NOT NULL DEFAULT ''
				"
			);

			// Change column name learnpress_question_answer_id to question_answer_id.
			$check = $lp_db->wpdb->query(
				"
				SHOW columns FROM $lp_db->tb_lp_user_itemmeta
				LIKE 'user_item_id'
				"
			);
			if ( ! $check ) {
				$lp_db->wpdb->query(
					"
					ALTER TABLE {$lp_db->tb_lp_user_itemmeta}
					CHANGE COLUMN learnpress_user_item_id user_item_id bigint(20)
					"
				);
			}
			// End change name column .

			// Create index.
			$indexs = array( 'user_item_id', 'meta_key', 'meta_value' );
			$lp_db->add_indexs_table( $lp_db->tb_lp_user_itemmeta, $indexs );
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $this->finish_step( $response, 'Step ' . __FUNCTION__ . ' finished' );
	}

	/**
	 * Remove data on table learnpress_user_itemmeta
	 */
	protected function remove_data_lp_user_itemmeta() {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		/**
		 * 1. Remove results course.
		 * 2. Remove results item's course.
		 * 3. Remove _question_answers.
		 * 4. Remove grade.
		 * 5. Remove _last_status.
		 * 6. Remove _current_status.
		 * 7. Remove finishing_type.
		 */
		$query = $lp_db->wpdb->prepare(
			"
				DELETE FROM {$lp_db->tb_lp_user_itemmeta}
				WHERE meta_key LIKE 'course_results_evaluate_%'
				OR meta_key = '_question_answers'
				OR meta_key = 'results'
				OR meta_key = 'grade'
				OR meta_key = '_last_status'
				OR meta_key = '_current_status'
				OR meta_key = 'finishing_type'
			", 1
		);
		$lp_db->wpdb->query( $query );

		return $this->finish_step( $response,
			'Step ' . __FUNCTION__ . ' finished' );
	}

	/**
	 * Modify table learnpress_quiz_questions.
	 */
	protected function modify_tb_lp_quiz_questions() {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		try {
			$indexs = array( 'quiz_id', 'question_id' );
			$lp_db->add_indexs_table( $lp_db->tb_lp_quiz_questions, $indexs );

			if ( ! empty( $lp_db->wpdb->last_error ) ) {
				throw new Exception( $lp_db->wpdb->last_error );
			}
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $this->finish_step( $response, 'Step ' . __FUNCTION__ . ' finished' );
	}

	/**
	 * Modify table learnpress_question_answers
	 */
	protected function modify_tb_lp_question_answers() {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		try {
			$query = $lp_db->wpdb->prepare(
				"
				ALTER TABLE {$lp_db->tb_lp_question_answers}
				ADD `title` text NULL AFTER `question_id`,
				ADD `value` varchar(32) NULL AFTER `title`,
				ADD `order` bigint(20) NULL DEFAULT 1 AFTER `value`,
				ADD `is_true` varchar(3) NULL AFTER `order`;
			", 1
			);
			$lp_db->wpdb->query( $query );

			$indexs = array( 'question_id' );
			$lp_db->add_indexs_table( $lp_db->tb_lp_question_answers, $indexs );

			if ( ! empty( $lp_db->wpdb->last_error ) ) {
				throw new Exception( $lp_db->wpdb->last_error );
			}
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $this->finish_step( $response, 'Step ' . __FUNCTION__ . ' finished' );
	}

	/**
	 * Convert data on table learnpress_question_answers.
	 *
	 * @param array $data .
	 *
	 * @return LP_Step
	 */
	protected function update_question_answers( array $data ): LP_Step {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();
		global $wpdb;
		$page      = 0;
		$offset    = 0;
		$limit     = 100;
		$total_row = 0;

		try {
			$offset = absint( get_transient( 'lp_upgrade_question_answers_offset' ) );

			if ( empty( $data ) ) {
				// Check total rows.
				$query = $lp_db->wpdb->prepare(
					"
					SELECT COUNT(question_answer_id) FROM $lp_db->tb_lp_question_answers
					", 1
				);

				$total_row = $response->data->total_rows = (int) $lp_db->wpdb->get_var( $query );
			} else {
				$page      = $data['p'];
				$offset    = $limit * $page;
				$total_row = $data['total_rows'];
			}

			$query = $wpdb->prepare(
				"
				SELECT *
				FROM {$lp_db->tb_lp_question_answers}
				LIMIT %d OFFSET %d
				", $limit, $offset
			);

			$rows = $wpdb->get_results( $query );

			if ( ! $rows ) {
				return $this->finish_step( $response, 'Finished ' . __FUNCTION__ );
			}

			foreach ( $rows as $row ) {
				$answers    = wp_parse_args(
					maybe_unserialize( $row->answer_data ),
					array(
						'text'    => '',
						'value'   => '',
						'is_true' => '',
					)
				);
				$update_sql = $lp_db->wpdb->prepare(
					"
					UPDATE {$lp_db->tb_lp_question_answers}
					SET `title`= %s,
						`value`= %s,
						`is_true`= %s,
						`order`= %s
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

			$percent = LP_Helper::progress_percent( $offset, $limit, $total_row );

			$response->status           = 'success';
			$response->message          = 'Insert success';
			$response->percent          = $percent;
			$response->data->p          = ++ $page;
			$response->data->total_rows = $total_row;
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Modify table learnpress_question_answermeta
	 */
	protected function modify_tb_lp_question_answermeta() {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		try {
			$lp_db->wpdb->query(
				"
				ALTER TABLE {$lp_db->tb_lp_question_answermeta}
				MODIFY COLUMN `meta_key` VARCHAR(255) NOT NULL DEFAULT '',
				MODIFY COLUMN `meta_value` longtext NULL DEFAULT ''
				"
			);

			// Change column name learnpress_question_answer_id to question_answer_id.
			$check = $lp_db->wpdb->query(
				"
				SHOW columns FROM $lp_db->tb_lp_question_answermeta
				LIKE 'question_answer_id'
				"
			);
			if ( ! $check ) {
				$lp_db->wpdb->query(
					"
				ALTER TABLE {$lp_db->tb_lp_question_answermeta}
				CHANGE COLUMN learnpress_question_answer_id question_answer_id bigint(20)
				"
				);
			}
			// End change name column .

			// Create index.
			$lp_db->drop_indexs_table( $lp_db->tb_lp_question_answermeta );
			$lp_db->wpdb->query(
				"
				ALTER TABLE {$lp_db->tb_lp_question_answermeta}
				ADD INDEX question_answer_meta (`question_answer_id`, `meta_key`)
				"
			);
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $this->finish_step( $response, __FUNCTION__ . ' finished' );
	}

	/**
	 * Convert question type fill in blank
	 *
	 * @param array $data .
	 *
	 * @return LP_Step
	 */
	protected function convert_question_type_fill_in_blank( array $data = array() ): LP_Step {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();
		global $wpdb;
		$page      = 0;
		$offset    = 0;
		$limit     = 100;
		$total_row = 0;

		try {
			if ( empty( $data ) ) {
				// Check total rows.
				$query = $lp_db->wpdb->prepare(
					"
					SELECT COUNT(meta_id) FROM $lp_db->tb_postmeta
					WHERE meta_key = %s
					AND meta_value = %s
					", 'lp_type', 'fill_in_blank'
				);

				$total_row = $response->data->total_rows = (int) $lp_db->wpdb->get_var( $query );
			} else {
				$page      = $data['p'];
				$offset    = $limit * $page;
				$total_row = $data['total_rows'];
			}

			$query_get_question_ids = $wpdb->prepare(
				"
				    SELECT meta_id
					FROM  {$lp_db->tb_postmeta}
				    WHERE meta_key = %s
				    AND meta_value = %s
				    LIMIT %d, %d
				", '_lp_type', 'fill_in_blank', $offset, $limit
			);

			$question_ids = $lp_db->wpdb->get_col( $query_get_question_ids );

			if ( empty( $question_ids ) ) {
				return $this->finish_step( $response, __FUNCTION__ . ' finished' );
			}

			$question_ids_str = implode( ',', $question_ids );

			$query = $wpdb->prepare(
				"
				UPDATE {$lp_db->tb_postmeta}
				SET meta_value = %s
				WHERE meta_id IN ($question_ids_str)
				", 'fill_in_blanks'
			);
			$lp_db->wpdb->query( $query );

			$percent = LP_Helper::progress_percent( $offset, $limit, $total_row );

			$response->status           = 'success';
			$response->message          = 'Convert question FIB success';
			$response->percent          = $percent;
			$response->data->p          = ++ $page;
			$response->data->total_rows = $total_row;
		} catch ( Exception $e ) {
			LP_Debug::rollbackTransaction();

			$response->message = $e->getMessage();
		}

		return $response;
	}

	protected function delete_columns_question_answers() {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		try {
			$lp_db->drop_col_table( $lp_db->tb_lp_question_answers, 'answer_data' );
			$lp_db->drop_col_table( $lp_db->tb_lp_question_answers, 'answer_order' );
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $this->finish_step( $response, __FUNCTION__ . ' finished' );
	}

	/**
	 * Modify table learnpress_order_items .
	 */
	protected function modify_tb_lp_order_items() {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		try {
			$lp_db->wpdb->query(
				"
	             ALTER TABLE {$lp_db->tb_lp_order_items}
	             ADD IF NOT EXISTS `item_id` bigint(20) NULL DEFAULT NULL AFTER `order_id`,
	             ADD IF NOT EXISTS `item_type` varchar(200) NULL DEFAULT NULL AFTER `item_id`
				"
			);

			$indexs = array( 'order_id', 'item_id', 'item_type' );
			$lp_db->add_indexs_table( $lp_db->tb_lp_order_items, $indexs );
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $this->finish_step( $response, __FUNCTION__ . ' finished' );
	}

	/**
	 * Modify table learnpress_order_itemmeta.
	 *
	 * @return LP_Step
	 */
	protected function modify_tb_lp_order_itemmeta(): LP_Step {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		try {
			$lp_db->wpdb->query(
				"
				ALTER TABLE {$lp_db->tb_lp_order_itemmeta}
				MODIFY COLUMN `meta_key` VARCHAR(255) NOT NULL DEFAULT '',
				MODIFY COLUMN `meta_value` VARCHAR(255) NOT NULL DEFAULT ''
				"
			);

			// Change column name learnpress_order_item_id to order_item_id.
			$check = $lp_db->wpdb->query(
				"
				SHOW columns FROM $lp_db->tb_lp_order_itemmeta
				LIKE 'order_item_id'
				"
			);
			if ( ! $check ) {
				$lp_db->wpdb->query(
					"
				ALTER TABLE {$lp_db->tb_lp_order_itemmeta}
				CHANGE COLUMN learnpress_order_item_id order_item_id bigint(20)
				"
				);
			}
			// End change name column .

			$indexs = array( 'order_item_id', 'meta_key', 'meta_value' );
			$lp_db->add_indexs_table( $lp_db->tb_lp_order_itemmeta, $indexs );
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $this->finish_step( $response, __FUNCTION__ . ' finished' );
	}

	/**
	 * Create index table learnpress_sections
	 */
	protected function modify_tb_lp_sections() {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		try {
			$indexs = array( 'section_course_id' );

			$lp_db->add_indexs_table( $lp_db->tb_lp_sections, $indexs );
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $this->finish_step( $response, __FUNCTION__ . ' finished' );
	}

	/**
	 * Create index table learnpress_section_items
	 */
	protected function modify_tb_lp_section_items() {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		try {
			$lp_db->drop_indexs_table( $lp_db->tb_lp_section_items );

			$query = $lp_db->wpdb->prepare(
				"
				ALTER TABLE {$lp_db->tb_lp_section_items}
				ADD INDEX section_item (`section_id`, `item_id`)
				", 1
			);

			$lp_db->wpdb->query( $query );

		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $this->finish_step( $response, __FUNCTION__ . ' finished' );
	}

	/**
	 * Convert Learnpress Settings
	 * 1. Courses thumbnail dimensions convert.
	 * 2. Profile thumbnail dimensions convert.
	 * 3. Profile rename dashboard to overview.
	 * 4. Block course by duration.
	 * 5. Block course when finished.
	 * 6. Assessment course by quizzes - Evaluate.
	 *
	 * @return LP_Step
	 */
	public function convert_lp_settings(): LP_Step {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		try {
			// Courses thumbnail dimensions convert.
			$lp_db->wpdb->query(
				"UPDATE $lp_db->tb_options
				SET option_name = 'learn_press_course_thumbnail_dimensions'
				WHERE option_name = 'learn_press_course_thumbnail_image_size'
				"
			);

			// Profile thumbnail dimensions convert.
			$lp_db->wpdb->query(
				"UPDATE $lp_db->tb_options
				SET option_name = 'learn_press_avatar_dimensions'
				WHERE option_name = 'learn_press_profile_picture_thumbnail_size'
				"
			);

			// Profile rename dashboard to overview.
			$learn_press_profile_endpoints = $lp_db->wpdb->get_var(
				"
				SELECT option_value FROM $lp_db->tb_options
				WHERE option_name = 'learn_press_profile_endpoints'
				"
			);

			$learn_press_profile_endpoints = maybe_unserialize( $learn_press_profile_endpoints );

			$learn_press_profile_endpoints_tmp = array();
			if ( $learn_press_profile_endpoints ) {
				foreach ( $learn_press_profile_endpoints as $k => $val ) {
					if ( 'profile-dashboard' === $k ) {
						$learn_press_profile_endpoints_tmp['overview'] = $val;
					} else {
						$learn_press_profile_endpoints_tmp[ str_replace( 'profile-', '', $k ) ] = $val;
					}
				}
			}

			$lp_db->wpdb->query(
				$lp_db->wpdb->prepare(
					"UPDATE $lp_db->tb_options
					SET option_value = %s
					WHERE option_name = 'learn_press_profile_endpoints'
					", maybe_serialize( $learn_press_profile_endpoints_tmp )
				)
			);

			// Course settings.
			// Block course by duration.
			$lp_db->wpdb->query(
				"UPDATE $lp_db->tb_postmeta
				SET meta_key = '_lp_block_expire_duration'
				WHERE meta_key = '_lp_block_course_item_duration_content'
				"
			);

			// Block course when finished.
			$lp_db->wpdb->query(
				"UPDATE $lp_db->tb_postmeta
				SET meta_key = '_lp_block_finished'
				WHERE meta_key = '_lp_block_lesson_content'
				"
			);

			// Assessment course by quizzes - Evaluate.
			$lp_db->wpdb->query(
				"UPDATE $lp_db->tb_postmeta
				SET meta_value = 'evaluate_quiz'
				WHERE meta_key = '_lp_course_result'
				AND meta_value = 'evaluate_quizzes'
				"
			);

			// End course settings.

			// Quiz settings.
			$lp_db->wpdb->query(
				"
				UPDATE $lp_db->tb_postmeta
				SET meta_key = 'lp_review'
				WHERE meta_key = '_lp_review_questions'
				"
			);

			$lp_db->wpdb->query(
				"
				UPDATE $lp_db->tb_postmeta
				SET meta_key = '_lp_negative_marking'
				WHERE meta_key = '_lp_minus_points'
				"
			);

			$lp_db->wpdb->query(
				"
				UPDATE $lp_db->tb_postmeta
				SET meta_value = 'yes'
				WHERE meta_key = '_lp_negative_marking'
				AND meta_value > 0
				"
			);

			$lp_db->wpdb->query(
				"
				UPDATE $lp_db->tb_postmeta
				SET meta_value = 'no'
				WHERE meta_key = '_lp_negative_marking'
				AND meta_value != 'yes'
				"
			);

			// Delete some fields not use.
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $this->finish_step( $response, __FUNCTION__ . ' finished' );
	}
}

LP_Upgrade_4::get_instance();
