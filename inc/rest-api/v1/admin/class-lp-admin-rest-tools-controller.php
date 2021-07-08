<?php

/**
 * Class LP_REST_Admin_Tools_Controller
 *
 * @since 4.0.3
 * @author tungnx
 * @version 1.0.0
 */
class LP_REST_Admin_Tools_Controller extends LP_Abstract_REST_Controller {
	public function __construct() {
		$this->namespace = 'lp/v1/admin';
		$this->rest_base = 'tools';
		parent::__construct();
	}

	/**
	 * Register rest routes.
	 */
	public function register_routes() {
		$this->routes = array(
			'create-indexs'      => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_indexs' ),
					'permission_callback' => '__return_true',
				),
			),
			'list-tables-indexs' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_list_tables_indexs' ),
					'permission_callback' => '__return_true',
				),
			),
			'clean-tables'       => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'clean_tables' ),
					'permission_callback' => '__return_true',
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * Create indexs.
	 *
	 * @param WP_REST_Request $request .
	 *
	 * @return void
	 */
	public function create_indexs( WP_REST_Request $request ) {
		$response = new LP_REST_Response();
		$lp_db    = LP_Database::getInstance();

		try {
			$tables     = $request->get_param( 'tables' );
			$table      = $request->get_param( 'table' );
			$table_keys = array();

			$lp_db->wpdb->query( 'SET autocommit = 0' );

			if ( empty( $tables ) ) {
				throw new Exception( 'Param invalid!' );
			} else {
				$table_keys = array_keys( $tables );
			}

			if ( empty( $table ) ) {
				$table = $lp_db->tb_lp_user_items;
			} elseif ( array_key_exists( $table, $table_keys ) ) {
				throw new Exception( 'Table invalid!' );
			}

			// Create Indexs for a table.
			if ( $table === $lp_db->tb_lp_question_answermeta ) {
				$lp_db->drop_indexs_table( $lp_db->tb_lp_question_answermeta );
				$lp_db->wpdb->query(
					"
					ALTER TABLE {$lp_db->tb_lp_question_answermeta}
					ADD INDEX question_answer_meta (`learnpress_question_answer_id`, `meta_key`(150))
					"
				);
				$lp_db->check_execute_has_error();
			} elseif ( $table === $lp_db->tb_lp_section_items ) {
				$lp_db->drop_indexs_table( $lp_db->tb_lp_section_items );

				$lp_db->wpdb->query(
					"
					ALTER TABLE {$lp_db->tb_lp_section_items}
					ADD INDEX section_item (`section_id`, `item_id`)
					"
				);
				$lp_db->check_execute_has_error();
			} else {
				$lp_db->add_indexs_table( $table, $tables[ $table ] );
			}

			// Set next table key.
			$index_key = array_search( $table, $table_keys );
			++ $index_key;

			if ( ! array_key_exists( $index_key, $table_keys ) ) {
				$response->status        = 'finished';
				$response->data->percent = 100;
			} else {
				$response->data->table   = $table_keys[ $index_key ];
				$response->data->percent = 100;
				$response->status        = 'success';
			}
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	public function get_list_tables_indexs( WP_REST_Request $request ) {
		$response = new LP_REST_Response();
		$lp_db    = LP_Database::getInstance();

		$tables_indexs = array(
			$lp_db->tb_lp_user_items          => array( 'user_id', 'item_id', 'item_type', 'status', 'ref_type', 'ref_id', 'parent_id' ),
			$lp_db->tb_lp_user_itemmeta       => array( 'learnpress_user_item_id', 'meta_key', 'meta_value' ),
			$lp_db->tb_lp_quiz_questions      => array( 'quiz_id', 'question_id' ),
			$lp_db->tb_lp_question_answers    => array( 'question_id' ),
			$lp_db->tb_lp_question_answermeta => '',
			$lp_db->tb_lp_order_items         => array( 'order_id', 'item_id', 'item_type' ),
			$lp_db->tb_lp_order_itemmeta      => array( 'learnpress_order_item_id', 'meta_key', 'meta_value' ),
			$lp_db->tb_lp_sections            => array( 'section_course_id' ),
			$lp_db->tb_lp_section_items       => '',
		);

		$response->data->tables = $tables_indexs;
		$response->data->table  = $lp_db->tb_lp_user_items;
		$response->status       = 'success';

		wp_send_json( $response );
	}

	public function clean_tables( WP_REST_Request $request ) {
		$response            = new LP_REST_Response();
		$lp_db_sessions      = LP_Sessions_DB::getInstance();
		$tables              = $request->get_param( 'tables' );
		$item_before_process = $request->get_param( 'itemtotal' );
		if ( empty( $tables ) ) {
			throw new Exception( 'Param invalid!' );
		}

		if ( empty( $item_before_process ) ) {
			$item_before_process = 0;
		}

		if ($item_before_process == 0) {
			$response->data->percent == 100;
			$response->status = 'finished';
			wp_send_json( $response );
		}

		try {
			// Delete resuilt in table select
			if ( $tables == 'learnpress_sessions' ) {
				$lp_db_sessions->delete_rows();
				// check the number of lines remaining after each query
				$item_after_process = $lp_db_sessions->count_row_db_sessions();
				$response->data->processed = $item_before_process - $item_after_process;
				$percent   = ( ($item_before_process - $item_after_process) / $item_before_process ) * 100;
				$response->data->percent   = number_format_i18n($percent,'2');
			}
			if ( $response->data->percent == 100 ) {
				$response->status = 'finished';
			} else {
				$response->status = 'success';
			}
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}
		wp_send_json( $response );
	}
}
