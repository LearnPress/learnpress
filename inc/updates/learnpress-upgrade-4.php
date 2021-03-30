<?php

/**
 * Class LP_Upgrade_4
 *
 * Helper class for updating database to 4.0.0
 *
 * @version 1.0.0
 * @author tungnx
 * @since 4.0.0
 */
class LP_Upgrade_4 extends LP_Handle_Upgrade_Steps {
	/**
	 * @var LP_Upgrade_4
	 */
	protected static $instance = null;

	/**
	 * Get Instance
	 *
	 * @return LP_Upgrade_4
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * LP_Upgrade_4 constructor.
	 *
	 * @see create_tables
	 * @see clone_tables
	 * @see modify_tb_lp_user_items
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
					'',
					array(
						'create_tables'                       => new LP_Step(
							'create_tables',
							'Create tables',
							'learnpress_user_item_results'
						),
						'clone_tables'                        => new LP_Step(
							'clone_tables',
							'Backup Tables',
							'learnpress_user_items, learnpress_user_itemmeta, learnpress_question_answers, postmeta, options'
						),
						'modify_tb_lp_user_items'             => new LP_Step(
							'modify_tb_lp_user_items',
							'Modify table learnpress_user_items',
							'Modify table learnpress_user_items, move the result of students on table learnpress_uset_itemmeta to learnpress_user_item_results'
						),
						'convert_result_graduation_item'      => new LP_Step(
							'convert_result_graduation_item',
							'Convert Data Result courses, items\' courses',
							''
						),
						'convert_result_questions'            => new LP_Step(
							'convert_result_questions',
							'Convert Data Result questions',
							''
						),
						'convert_retake_quiz'                 => new LP_Step(
							'convert_retake_quiz',
							'Convert retake quizzes',
							''
						),
						'remove_data_lp_user_itemmeta'        => new LP_Step(
							'remove_data_lp_user_itemmeta',
							'Remove data results of lp_user_itemmeta',
							''
						),
						'modify_tb_lp_user_itemmeta'          => new LP_Step(
							'modify_tb_lp_user_itemmeta',
							'Modify table learnpress_user_itemmeta',
							'Change type columns: meta_key, meta_value. Create Index: user_item_id, meta_key, meta_value'
						),
						'modify_tb_lp_quiz_questions'         => new LP_Step(
							'modify_tb_lp_quiz_questions',
							'Modify table learnpress_quiz_questions',
							''
						),
						'modify_tb_lp_question_answers'       => new LP_Step(
							'modify_tb_lp_question_answers',
							'Modify table learnpress_question_answers',
							''
						),
						'update_question_answers'             => new LP_Step(
							'update_question_answers',
							'Update data table learnpress_question_answers',
							''
						),
						'modify_tb_lp_question_answermeta'    => new LP_Step(
							'modify_tb_lp_question_answermeta',
							'Modify table learnpress_question_answermeta',
							''
						),
						'convert_question_type_fill_in_blank' => new LP_Step(
							'convert_question_type_fill_in_blank',
							'Update data question type "Fill in blank"',
							''
						),
						'delete_columns_question_answers'     => new LP_Step(
							'delete_columns_question_answers',
							'Delete data table learnpress_question_answers',
							''
						),
						'modify_tb_lp_order_items'            => new LP_Step(
							'modify_tb_lp_order_items',
							'Modify data table learnpress_order_items',
							''
						),
						'modify_tb_lp_order_itemmeta'         => new LP_Step(
							'modify_tb_lp_order_itemmeta',
							'Modify data table learnpress_order_itemmeta',
							''
						),
						'modify_tb_lp_sections'               => new LP_Step(
							'modify_tb_lp_sections',
							'Modify table learnpress_sections',
							'Create Index'
						),
						'modify_tb_lp_section_items'          => new LP_Step(
							'modify_tb_lp_section_items',
							'Modify table learnpress_section_items',
							'Create Index'
						),
						'convert_lp_settings'                 => new LP_Step(
							'convert_lp_settings',
							'Convert data settings learnpress',
							'Courses thumbnail dimensions, Profile thumbnail dimensions, Profile rename dashboard to overview, Block course by duration, Block course when finished, Assessment course by quizzes - Evaluate, '
						),
					)
				),
			)
		);
		add_action( 'lp/db/upgrade/finish', array( $this, 'save_db_when_step_finish' ) );
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
			 * @see LP_Database::create_tb_lp_upgrade_db()
			 */
			$lp_db                    = LP_Database::getInstance();
			$create_tables            = array(
				'tb_lp_user_item_results',
				'tb_lp_upgrade_db',
			);
			$total_tables_need_create = count( $create_tables );

			// Check table need create.
			if ( empty( $data ) ) {
				$create_table = $create_tables[0];
			} elseif ( ! empty( $data[ $key_create_table ] )
			           && in_array( $data[ $key_create_table ], $create_tables ) ) {
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
					throw new Exception( $lp_db->wpdb->last_error );
				}

				// Set param to clone table next.
				$index   = array_search( $create_table, $create_tables );
				$percent = LP_Helper::progress_percent( $index, 1, $total_tables_need_create );
				++ $index;

				if ( ! empty( $create_tables[ $index ] ) ) {
					$response->status             = 'success';
					$response->percent            = $percent;
					$response->data->create_table = $create_tables[ $index ];
					$response->message            = 'Table "' . $create_table . '" created';
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
				$lp_db->tb_postmeta,
				$lp_db->tb_options,
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
					throw new Exception( $lp_db->wpdb->last_error );
				}

				// Set param to clone table next.
				$index   = array_search( $clone_table, $clone_tables );
				$percent = LP_Helper::progress_percent( $index, 1, $total_tables_need_clone );
				++ $index;

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
	 * Convert retake quiz.
	 *
	 * @param array $data .
	 *
	 * @return LP_Step
	 */
	protected function convert_retake_quiz( array $data ): LP_Step {
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
					WHERE meta_key = '_retaken_items'
					", 1
				);

				$total_row = $response->data->total_rows = (int) $lp_db->wpdb->get_var( $query );
			} else {
				$page      = $data['p'];
				$offset    = $limit * $page;
				$total_row = $data['total_rows'];
			}

			// Convert rows.
			$query                   = $lp_db->wpdb->prepare(
				"
				SELECT learnpress_user_item_id AS user_item_id, meta_value AS quizzes_retaken
				FROM $lp_db->tb_lp_user_itemmeta
				WHERE meta_key = '_retaken_items'
				LIMIT %d offset %d
				", $limit, $offset
			);
			$courses_quizzes_retaken = $lp_db->wpdb->get_results( $query );

			if ( 0 === count( $courses_quizzes_retaken ) ) {
				return $this->finish_step( $response, 'Convert result question success' );
			}

			foreach ( $courses_quizzes_retaken as $quizzes_retaken ) {
				$user_item_id = $quizzes_retaken->user_item_id;
				$retaken      = maybe_unserialize( $quizzes_retaken->quizzes_retaken );

				if ( is_array( $retaken ) ) {
					foreach ( $retaken as $quiz_id => $v ) {
						$quiz_user_item_id = $lp_db->wpdb->get_var(
							$lp_db->wpdb->prepare(
								"
								SELECT user_item_id
								FROM $lp_db->tb_lp_user_items
								WHERE parent_id = %d
								AND item_id = %d
								", $user_item_id, $quiz_id
							)
						);

						if ( $quiz_user_item_id ) {
							$lp_db->wpdb->query(
								$lp_db->wpdb->prepare(
									"
									INSERT INTO $lp_db->tb_lp_user_itemmeta
									(learnpress_user_item_id, meta_key, meta_value)
									VALUES ($quiz_user_item_id, '_lp_retake_count', $v)
									"
								)
							);
						}
					}
				}
			}

			$percent = LP_Helper::progress_percent( $offset, $limit, $total_row );

			$response->status           = 'success';
			$response->message          = 'Convert success ';
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

			// Create index.
			$indexs = array( 'learnpress_user_item_id', 'meta_key', 'meta_value' );
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
		 * 7. Remove _retaken_items.
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
				OR meta_key = '_retaken_items'
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
	 *
	 * @return LP_Step
	 * @throws Exception .
	 */
	protected function modify_tb_lp_question_answers(): LP_Step {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		try {
			// Add column title.
			$check_col_title = $lp_db->check_col_table( $lp_db->tb_lp_question_answers, 'title' );
			if ( ! $check_col_title ) {
				$lp_db->wpdb->query(
					"
					ALTER TABLE {$lp_db->tb_lp_question_answers}
					ADD `title` text NULL AFTER `question_id`
					"
				);
			}

			// Add column value.
			$check_col_value = $lp_db->check_col_table( $lp_db->tb_lp_question_answers, 'value' );
			if ( ! $check_col_value ) {
				$lp_db->wpdb->query(
					"
					ALTER TABLE {$lp_db->tb_lp_question_answers}
					ADD `value` varchar(32) NULL AFTER `title`
					"
				);
			}

			// Add column order.
			$check_col_order = $lp_db->check_col_table( $lp_db->tb_lp_question_answers, 'order' );
			if ( ! $check_col_order ) {
				$lp_db->wpdb->query(
					"
					ALTER TABLE {$lp_db->tb_lp_question_answers}
					ADD `order` bigint(20) NULL DEFAULT 1 AFTER `value`
					"
				);
			}

			// Add column is_true.
			$check_col_is_true = $lp_db->check_col_table( $lp_db->tb_lp_question_answers, 'is_true' );
			if ( ! $check_col_is_true ) {
				$lp_db->wpdb->query(
					"
					ALTER TABLE {$lp_db->tb_lp_question_answers}
					ADD `is_true` varchar(3) NULL AFTER `order`
					"
				);
			}

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
				if ( ! isset( $row->answer_data ) ) {
					continue;
				}

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

			// Create index.
			$lp_db->drop_indexs_table( $lp_db->tb_lp_question_answermeta );
			$lp_db->wpdb->query(
				"
				ALTER TABLE {$lp_db->tb_lp_question_answermeta}
				ADD INDEX question_answer_meta (`learnpress_question_answer_id`, `meta_key`)
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

			$indexs = array( 'learnpress_order_item_id', 'meta_key', 'meta_value' );
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
	protected function modify_tb_lp_section_items(): LP_Step {
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
	protected function convert_lp_settings(): LP_Step {
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

			// Finish upgrade.
			update_option( 'learnpress_db_version', '4' );
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $this->finish_step( $response, __FUNCTION__ . ' finished' );
	}

	/**
	 * Save step status completed .
	 *
	 * @param LP_Step $step .
	 */
	public function save_db_when_step_finish( LP_Step $step ) {
		$lp_db = LP_Database::getInstance();
		$lp_db->set_step_complete( $step->name, 'completed' );
	}
}

LP_Upgrade_4::get_instance();
