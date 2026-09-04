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
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'start_time', 'datetime NULL default NULL', 'item_type' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'end_time', 'datetime NULL default NULL', 'start_date' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'ref_id', "varchar(45) NOT NULL DEFAULT ''", 'item_type' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'ref_type', "varchar(45) NOT NULL DEFAULT ''", 'ref_id' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'status', "varchar(45) NOT NULL DEFAULT ''", 'ref_type' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'graduation', 'varchar(20) NULL default NULL', 'status' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'parent_id', "varchar(45) NOT NULL DEFAULT ''", 'results' );
			$lp_db->add_col_table( $lp_db->tb_lp_user_item_results, 'extra_data', "longtext COMMENT 'Store more data type JSON.'", 'results' );

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
					uir.graduation = ui.graduation,
					uir.parent_id = ui.parent_id
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
