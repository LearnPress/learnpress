<?php

use LearnPress\Databases\DataBase;

/**
 * Class LP_Upgrade_6
 *
 * Helper class for updating database to version 6
 *
 * @version 1.0.0
 * @author tungnx
 * @since 4.5.0
 */
class LP_Upgrade_6 extends LP_Handle_Upgrade_Steps {
	/**
	 * @var LP_Upgrade_6
	 */
	protected static $instance = null;

	/**
	 * Get Instance
	 *
	 * @return LP_Upgrade_6
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * LP_Upgrade_6 constructor.
	 *
	 * @see modify_tb_lp_user_item_results
	 * @see modify_tb_lp_user_items
	 * @see update_data_for_tb_lp_user_item_results
	 * @see create_new_table_question_answer
	 * @see sync_question_answers
	 * @see finish_upgrade
	 */
	protected function __construct() {
		$this->version = '6.0.0';
		/**
		 * Name key not > 50 character
		 */
		$this->group_steps = apply_filters(
			'lp/upgrade/6/steps',
			array(
				'learnpress_user_item_results' => new LP_Group_Step(
					'learnpress_user_item_results',
					'',
					array(
						'modify_tb_lp_user_item_results'          => new LP_Step(
							'modify_tb_lp_user_item_results',
							'Modify table learnpress_user_item_results',
							'Add columns: user_id, guest_key, item_id, item_type, extra_data'
						),
						'modify_tb_lp_user_items'                => new LP_Step(
							'modify_tb_lp_user_items',
							'Modify table learnpress_user_items',
							'Add column: extra_data'
						),
						'update_data_for_tb_lp_user_item_results' => new LP_Step(
							'update_data_for_tb_lp_user_item_results',
							'Update data for table learnpress_user_item_results',
							'Update data for columns: user_id, guest_key, item_id, item_type'
						),
						'create_new_table_question_answer'          => new LP_Step(
							'create_new_table_question_answer',
							'Create table learnpress_question_answer_config',
							'Create new table store answers of question in JSON format'
						),
						'sync_question_answers'                     => new LP_Step(
							'sync_question_answers',
							'Sync question answers to new table',
							'Migrate data from learnpress_question_answers to learnpress_question_answer_config'
						),
						'finish_upgrade'                           => new LP_Step(
							'finish_upgrade',
							'Update settings',
							'Update settings'
						),
					)
				),
			)
		);
	}

	/**
	 * Modify table learnpress_user_item_results.
	 * Add columns: user_id, guest_key, item_id, item_type, extra_data
	 *
	 * @return LP_Step
	 */
	protected function modify_tb_lp_user_item_results(): LP_Step {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = DataBase::getInstance();

		try {
			// Create table learnpress_upgrade_db
			$lp_db->create_tb_lp_upgrade_db();
			$lp_db->wpdb->query( "TRUNCATE TABLE {$lp_db->tb_lp_upgrade_db}" );
			$lp_db->check_execute_has_error();

			// Add columns
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'user_id', "bigint(20) unsigned NOT NULL DEFAULT '0'", 'user_item_id' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'guest_key', "varchar(64) NOT NULL DEFAULT '' COMMENT 'Key to identify users not logged in.'", 'user_id' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'item_id', "bigint(20) unsigned NOT NULL DEFAULT '0'", 'guest_key' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'item_type', "varchar(45) NOT NULL DEFAULT ''", 'item_id' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'ref_id', "varchar(45) NOT NULL DEFAULT ''", 'item_type' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'ref_type', "varchar(45) NOT NULL DEFAULT ''", 'ref_id' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'start_time', 'datetime NULL default NULL', 'ref_type' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'end_time', 'datetime NULL default NULL', 'start_time' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'status', "varchar(45) NOT NULL DEFAULT ''", 'end_time' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'graduation', 'varchar(20) NULL default NULL', 'status' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'parent_result_id', "varchar(45) NOT NULL DEFAULT ''", 'graduation' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'extra_data', "longtext COMMENT 'Store more data type JSON.'", 'result' );

			$response = $this->finish_step( $response, 'Step ' . __FUNCTION__ . ' finished' );
		} catch ( Exception $e ) {
			$response->message = $this->error_step( $response->name, $e->getMessage() );
		}

		return $response;
	}

	/**
	 * Modify table learnpress_user_items.
	 * Add column: extra_data
	 *
	 * @return LP_Step
	 */
	protected function modify_tb_lp_user_items(): LP_Step {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		try {
			$lp_db->add_col_table( $lp_db->tb_lp_user_items, 'extra_data', "longtext COMMENT 'Store more data type JSON.'" );
			$response = $this->finish_step( $response, 'Step ' . __FUNCTION__ . ' finished' );
		} catch ( Exception $e ) {
			$response->message = $this->error_step( $response->name, $e->getMessage() );
		}

		return $response;
	}

	/**
	 * Update data for table learnpress_user_item_results.
	 * Backfill user_id, guest_key, item_id, item_type from learnpress_user_items.
	 *
	 * @return LP_Step
	 */
	protected function update_data_for_tb_lp_user_item_results(): LP_Step {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		try {
			$lp_db->wpdb->query(
				"
				UPDATE {$lp_db->tb_lp_user_item_results} uir
				INNER JOIN {$lp_db->tb_lp_user_items} ui ON uir.user_item_id = ui.user_item_id
				SET uir.user_id = ui.user_id,
					uir.item_id = ui.item_id,
					uir.item_type = ui.item_type,
					uir.ref_id = ui.ref_id,
					uir.ref_type = ui.ref_type,
					uir.status = ui.status,
					uir.graduation = ui.graduation
				WHERE uir.user_item_id > 0
				"
			);
			$lp_db->check_execute_has_error();

			$response = $this->finish_step( $response, 'Step ' . __FUNCTION__ . ' finished' );
		} catch ( Exception $e ) {
			$response->message = $this->error_step( $response->name, $e->getMessage() );
		}

		return $response;
	}

	/**
	 * Create table learnpress_question_answer_config.
	 * Store answers of question in JSON format.
	 *
	 * @return LP_Step
	 */
	protected function create_new_table_question_answer(): LP_Step {
		$response = new LP_Step( __FUNCTION__, 'Create table learnpress_question_answer_config' );
		$lp_db    = DataBase::getInstance();

		try {
			$collate = $lp_db->get_collate();

			$lp_db->wpdb->query(
				"
				CREATE TABLE IF NOT EXISTS {$lp_db->tb_lp_question_answer_config} (
					question_answer_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					question_id bigint(20) unsigned NOT NULL DEFAULT '0',
					answers longtext NOT NULL COMMENT 'Store answers of question in JSON format.',
					PRIMARY KEY (question_answer_id),
					KEY question_id (question_id)
				) $collate;
				"
			);
			$lp_db->check_execute_has_error();

			$response = $this->finish_step( $response, 'Step ' . __FUNCTION__ . ' finished' );
		} catch ( Exception $e ) {
			$response->message = $this->error_step( $response->name, $e->getMessage() );
		}

		return $response;
	}

	/**
	 * Sync question answers from old tables to learnpress_question_answer_config.
	 *
	 * Read data from learnpress_question_answers and learnpress_question_answermeta,
	 * group answers per question and store as JSON in the new table.
	 * Process 50 questions per request. Old tables are read-only.
	 *
	 * @param array $data
	 *
	 * @return LP_Step
	 */
	protected function sync_question_answers( array $data ): LP_Step {
		$response = new LP_Step( __FUNCTION__, 'Sync question answers' );
		$lp_db    = DataBase::getInstance();
		$wpdb     = $lp_db->wpdb;
		$limit    = 50;

		try {
			$total_questions = (int) $wpdb->get_var(
				"SELECT COUNT(DISTINCT question_id) FROM {$lp_db->tb_lp_question_answers}"
			);

			if ( 0 === $total_questions ) {
				return $this->finish_step( $response, 'Step ' . __FUNCTION__ . ' finished - no data to sync' );
			}

			// Get next 50 question_ids not synced yet (resumable).
			$question_ids = $wpdb->get_col(
				$wpdb->prepare(
					"
					SELECT DISTINCT qa.question_id
					FROM {$lp_db->tb_lp_question_answers} qa
					LEFT JOIN {$lp_db->tb_lp_question_answer_config} qac ON qac.question_id = qa.question_id
					WHERE qac.question_id IS NULL
					ORDER BY qa.question_id ASC
					LIMIT %d
					",
					$limit
				)
			);

			if ( empty( $question_ids ) ) {
				return $this->finish_step( $response, 'Step ' . __FUNCTION__ . ' finished' );
			}

			$question_ids_in = implode( ',', array_map( 'absint', $question_ids ) );

			// Get all answers of these questions.
			$answer_rows = $wpdb->get_results(
				"
				SELECT question_answer_id, question_id, title, `value`, `order`, is_true
				FROM {$lp_db->tb_lp_question_answers}
				WHERE question_id IN ({$question_ids_in})
				ORDER BY `order` ASC
				"
			);
			$lp_db->check_execute_has_error();

			// Get all meta of these answers.
			$answer_ids    = wp_list_pluck( $answer_rows, 'question_answer_id' );
			$answer_ids_in = implode( ',', array_map( 'absint', $answer_ids ) );
			$meta_rows     = [];
			if ( ! empty( $answer_ids_in ) ) {
				$meta_rows = $wpdb->get_results(
					"
					SELECT learnpress_question_answer_id, meta_key, meta_value
					FROM {$lp_db->tb_lp_question_answermeta}
					WHERE learnpress_question_answer_id IN ({$answer_ids_in})
					"
				);
				$lp_db->check_execute_has_error();
			}

			// Group meta per answer.
			$meta_by_answer = [];
			foreach ( $meta_rows as $meta_row ) {
				$meta_by_answer[ $meta_row->learnpress_question_answer_id ][ $meta_row->meta_key ] = maybe_unserialize( $meta_row->meta_value );
			}

			// Group answers per question and insert into new table.
			$answers_by_question = [];
			foreach ( $answer_rows as $answer_row ) {
				$answer = array(
					'title'              => $answer_row->title,
					'value'              => $answer_row->value,
					'order'              => (int) $answer_row->order,
					'is_true'            => $answer_row->is_true,
				);

				if ( ! empty( $meta_by_answer[ $answer_row->question_answer_id ] ) ) {
					$answer['meta'] = $meta_by_answer[ $answer_row->question_answer_id ];
				}

				$answers_by_question[ $answer_row->question_id ][] = $answer;
			}

			foreach ( $answers_by_question as $question_id => $answers ) {
				$wpdb->insert(
					$lp_db->tb_lp_question_answer_config,
					array(
						'question_id' => $question_id,
						'answers'     => wp_json_encode( $answers ),
					)
				);
			}
			$lp_db->check_execute_has_error();

			$synced   = (int) $wpdb->get_var(
				"SELECT COUNT(DISTINCT question_id) FROM {$lp_db->tb_lp_question_answer_config}"
			);
			$finished = $synced >= $total_questions;

			if ( $finished ) {
				return $this->finish_step( $response, 'Step ' . __FUNCTION__ . ' finished' );
			}

			$percent           = floatval( number_format( $synced * 100 / $total_questions, 2 ) );
			$response->percent = $percent;
			$response->status  = 'success';
			$response->message = sprintf( 'Synced %d/%d questions', $synced, $total_questions );
		} catch ( Exception $e ) {
			$response->message = $this->error_step( $response->name, $e->getMessage() );
		}

		return $response;
	}

	/**
	 * Finish upgrade.
	 * Update DB version to 6.
	 *
	 * @return LP_Step
	 */
	protected function finish_upgrade(): LP_Step {
		$response = new LP_Step( __FUNCTION__, '' );
		$db       = DataBase::getInstance();

		try {
			update_option( LP_KEY_DB_VERSION, 6 );
			$response = $this->finish_step( $response, __FUNCTION__ . ' finished' );
			$db->drop_table( $db->tb_lp_upgrade_db );
		} catch ( Exception $e ) {
			$response->message = $this->error_step( $response->name, $e->getMessage() );
		}

		return $response;
	}
}

LP_Upgrade_6::get_instance();
