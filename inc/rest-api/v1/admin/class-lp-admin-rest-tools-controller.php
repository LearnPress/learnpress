<?php

use LearnPress\Helpers\Template;
use LearnPress\Models\UserItems\UserCourseModel;

/**
 * Class LP_REST_Admin_Tools_Controller
 *
 * @since 4.0.3
 * @author tungnx
 * @version 1.0.1
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
			'create-indexs'           => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_indexes' ),
					'permission_callback' => '__return_true',
				),
			),
			'list-tables-indexs'      => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_list_tables_indexs' ),
					'permission_callback' => '__return_true',
				),
			),
			'clean-tables'            => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'clean_tables' ),
					'permission_callback' => '__return_true',
				),
			),
			'admin-notices'           => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'admin_notices' ),
					'permission_callback' => '__return_true',
				),
			),
			'search-course'           => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'search_courses' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'search-user'             => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_users' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'search-roles'            => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_roles' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'assign-user-course'      => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'assign_courses_to_users' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'remove-user-from-course' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'remove_user_from_course' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * Create create_indexes.
	 *
	 * @param WP_REST_Request $request .
	 *
	 * @return void
	 */
	public function create_indexes( WP_REST_Request $request ) {
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
			++$index_key;

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

		$tables_indexes = array(
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

		$response->data->tables = $tables_indexes;
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

		if ( $item_before_process == 0 ) {
			$response->data->percent = 100;
			$response->status        = 'finished';
			wp_send_json( $response );
		}

		try {
			// Delete result in table select
			if ( $tables == 'learnpress_sessions' ) {
				$lp_db_sessions->delete_rows();
				// check the number of lines remaining after each query
				$item_after_process        = $lp_db_sessions->count_row_db_sessions();
				$response->data->processed = $item_before_process - $item_after_process;
				$percent                   = ( ( $item_before_process - $item_after_process ) / $item_before_process ) * 100;
				$response->data->percent   = number_format_i18n( $percent, '2' );
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

	/**
	 * Show admin notices.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return void
	 * @since 4.1.7.3.2
	 * @version 1.0.0
	 */
	public function admin_notices( WP_REST_Request $request ) {
		$response = new LP_REST_Response();
		$content  = '';

		try {
			$params                = $request->get_params();
			$admin_notices_dismiss = get_option( 'lp_admin_notices_dismiss', [] );
			$lp_beta_version_info  = LP_Admin_Notice::check_lp_beta_version();

			if ( isset( $params['dismiss'] ) ) {
				if ( $lp_beta_version_info ) {
					// Store version of LP beta to session.
					learn_press_setcookie( 'lp_beta_version', $lp_beta_version_info['version'] ?? 0 );
				}

				$admin_notices_dismiss[ $params['dismiss'] ] = $params['dismiss'];
				update_option( 'lp_admin_notices_dismiss', $admin_notices_dismiss );
				$response->message = __( 'Dismissed!', 'learnpress' );
			} else {
				$show_notice_lp_beta_version = false;
				/**
				 * Check if LP beta version is not dismissed or dismissed version lower than current version, will bet to show notice.
				 */
				if ( $lp_beta_version_info && ! isset( $_GET['tab'] ) &&
					( ! isset( $_COOKIE['lp_beta_version'] ) || version_compare( $_COOKIE['lp_beta_version'], $lp_beta_version_info['version'], '<' ) ) ) {
					$show_notice_lp_beta_version = true;
				}

				$rules = apply_filters(
					'learn-press/admin-notices',
					[
						// Check wp_remote call success.
						'check_wp_remote'       => [
							'template' => 'admin-notices/wp-remote.php',
							'check'    => LP_Admin_Notice::check_wp_remote(),
						],
						// Check name plugin base.
						'check_plugin_base'     => [
							'template' => 'admin-notices/plugin-base.php',
							'check'    => LP_Admin_Notice::check_plugin_base(),
						],
						// Show beta version of LP.
						'lp-beta-version'       => [
							'template'      => 'admin-notices/beta-version.php',
							'check'         => $show_notice_lp_beta_version,
							'info'          => $lp_beta_version_info,
							'allow_dismiss' => 1,
						],
						// Show message needs upgrades database compatible with LP version current.
						'lp-upgrade-db'         => [
							'template' => 'admin-notices/upgrade-db.php',
							'check'    => LP_Updater::instance()->check_lp_db_need_upgrade(),
						],
						// Show message wrong permalink structure.
						'lp-permalink'          => [
							'template' => 'admin-notices/permalink-wrong.php',
							'check'    => ! get_option( 'permalink_structure' ),
						],
						// Show notice setup wizard.
						'lp-setup-wizard'       => [
							'template'      => 'admin-notices/setup-wizard.php',
							'check'         => ! get_option( 'learn_press_setup_wizard_completed', false )
							&& ! isset( $admin_notices_dismiss['lp-setup-wizard'] ),
							'allow_dismiss' => 1,
						],
						// Show notification addons new version.
						'lp-addons-new-version' => [
							'template'      => 'admin-notices/addons-new-version.php',
							'addons'        => LP_Manager_Addons::instance()->list_addon_new_version(),
							'allow_dismiss' => 1,
							'dismiss'       => isset( $admin_notices_dismiss['lp-addons-new-version'] ),
						],
					]
				);

				ob_start();
				foreach ( $rules as $template_data ) {
					Template::instance()->get_admin_template(
						$template_data['template'] ?? '',
						[ 'data' => $template_data ]
					);
				}
			}

			$response->status        = 'success';
			$response->data->content = ob_get_clean();
		} catch ( Exception $e ) {
			ob_end_clean();
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}


	/**
	 * Search courses by title
	 *
	 * @param  WP_REST_Request $request
	 * @return LP_REST_Response
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function search_courses( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();
		try {
			$filter              = new LP_Course_Filter();
			$params              = $request->get_params();
			$filter->limit       = 5;
			$filter->only_fields = [ 'ID', 'post_title' ];
			$filter->post_title  = $params['c_search'] ?? '';
			$courses             = LP_Course::get_courses( $filter );
			$response->data      = $courses;
			$response->status    = 'success';
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $response;
	}

	/**
	 * Search user by name or email
	 *
	 * @param WP_REST_Request $request
	 * @return LP_REST_Response
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function search_users( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();
		try {
			$params        = $request->get_params();
			$search_string = sanitize_text_field( $params['search'] ?? '' );
			$users         = get_users(
				array(
					'search'         => "*{$search_string}*",
					'search_columns' => array(
						'user_login',
						'user_nicename',
						'user_email',
					),
					'number'         => 10,
					'fields'         => array( 'ID', 'display_name', 'user_login', 'user_email' ),
				)
			);
			if ( ! empty( $users ) ) {
				$response->data = $users;
			}
			$response->status = 'success';
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $response;
	}

	/**
	 * [search_roles search roles to assign]
	 * @param  WP_REST_Request $request [description]
	 * @return [array]                   [description]
	 */
	public function search_roles( WP_REST_Request $request ) {
		$response = new LP_REST_Response();
		try {
			global $wp_roles;
			$data  = [];
			$roles = $wp_roles->get_names();
			foreach ( $roles as $key => $value ) {
				$data[] = array(
					'slug' => $key,
					'name' => $value,
				);
			}
			$response->data   = $data;
			$response->status = 'success';
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * Enroll users to courses
	 * @param WP_REST_Request $request [description]
	 * @return LP_REST_Response
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function assign_courses_to_users( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();

		try {
			$params     = $request->get_params();
			$course_ids = $params['course_ids'] ?? [];
			$user_ids   = $params['user_ids'] ?? [];

			if ( empty( $course_ids ) || empty( $user_ids ) ) {
				throw new Exception( __( 'Invalid course or user.', 'learnpress' ) );
			}

			foreach ( $user_ids as $user_id ) {
				foreach ( $course_ids as $course_id ) {
					// Delete data user who already enrolled this course.
					LP_User_Items_DB::getInstance()->delete_user_items_old( $user_id, $course_id );
					// End

					// Insert new data user to user_item table.
					$user_course_new             = new UserCourseModel();
					$user_course_new->user_id    = $user_id;
					$user_course_new->item_id    = $course_id;
					$user_course_new->item_type  = LP_COURSE_CPT;
					$user_course_new->ref_type   = '';
					$user_course_new->status     = LP_COURSE_ENROLLED;
					$user_course_new->graduation = LP_COURSE_GRADUATION_IN_PROGRESS;
					$user_course_new->start_time = gmdate( LP_Datetime::$format, time() );
					$user_course_new->save();
					// End
				}
			}

			$response->status  = 'success';
			$response->message = __( 'Assign users to courses successfully.', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	/**
	 * [add_user_to_course multiple insert user_item to user_item table and key_cache to thim_cache table]
	 * @param integer $course_id [description]
	 * @param array   $user_ids  [description]
	 */
	public function add_user_to_course( $course_id = 0, $user_ids = [] ) {
		if ( ! $course_id ) {
			throw new Exception( 'Invalid course.', 'learnpress' );
		}
		if ( empty( $user_ids ) ) {
			throw new Exception( 'Select users to assign', 'learnpress' );
		}
		$current_user = wp_get_current_user();

		$key_caches = array();
		$clearcache = array();
		$user_items = [];
		$ref_type   = 'admin-tool';
		foreach ( $user_ids as $id ) {
			$user_item    = array(
				'user_id'    => $id,
				'item_id'    => $course_id,
				'start_time' => current_time( 'mysql' ),
				// 'end_time'     => '',
				'item_type'  => LP_COURSE_CPT,
				'status'     => LP_COURSE_ENROLLED,
				'graduation' => LP_COURSE_GRADUATION_IN_PROGRESS,
				// 'access_level' => 50,
				'ref_id'     => 0,
				'ref_type'   => $ref_type,
				'parent_id'  => 0,
			);
			$user_items[] = $user_item;

			$key_caches[] = array(
				'key_cache' => 'learn_press/user-items/' . $id . '/' . $course_id . '/' . LP_COURSE_CPT,
				'value'     => json_encode( $user_item ),
			);
			$clearcache[] = 'learn_press/user-items/' . $id . '/' . $course_id . '/' . LP_COURSE_CPT;
		}
		$this->clear_user_items_cache( $clearcache );
		$this->clear_user_items_if_exist( $course_id, $user_ids );
		$user_items         = apply_filters( 'learnpress/admin-tools/assign-course-items', $user_items, $course_id );
		$user_items_table   = LP_Database::getInstance()->tb_lp_user_items;
		$user_items_formats = [ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d' ];
		$lp_insert_multiple = LP_WPDB_Multiple::getInstance();
		$new_item_count     = $lp_insert_multiple->insert_multiple( $user_items_table, $user_items, $user_items_formats );
		$thim_cache_table   = Thim_Cache_DB::instance()->table_name;
		$new_cache_count    = $lp_insert_multiple->insert_multiple( $thim_cache_table, $key_caches, [ '%s', '%s' ] );

		return ( $new_item_count && $new_cache_count ) ? true : false;
	}


	/**
	 * [remove_user_from_course remove users who assigned this course]
	 * @param  WP_REST_Request $request [description]
	 * @return [type]                   [description]
	 */
	public function remove_user_from_course( WP_REST_Request $request ) {
		$response = new LP_REST_Response();
		try {
			$params    = $request->get_params();
			$course_id = (int) $params['course_id'];
			if ( ! $course_id ) {
				throw new Exception( 'Invalid course.', 'learnpress' );
			}
			$user_ids = $params['remove_user'];
			if ( empty( $user_ids ) ) {
				throw new Exception( 'Choose remove users', 'learnpress' );
			}
			$user_ids   = map_deep( $user_ids, 'intval' );
			$clearcache = array();
			foreach ( $user_ids as $id ) {
				$clearcache[] = 'learn_press/user-items/' . $id . '/' . $course_id . '/' . LP_COURSE_CPT;
			}
			$delete_user_items = $this->clear_user_items_if_exist( $course_id, $user_ids );
			$delete_cache      = $this->clear_user_items_cache( $clearcache );
			if ( $delete_user_items && $delete_cache ) {
				$this->update_course_cache( $course_id );
				$response->status  = 'success';
				$response->message = __( 'Remove user successfully.', 'learnpress' );
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * [clear_user_items_cache clear user-items cache]
	 * @param  array  $key_caches [user-items cache key]
	 * @return [type]             [description]
	 */
	public function clear_user_items_cache( $key_caches = [] ) {
		if ( empty( $key_caches ) ) {
			throw new Exception( 'Cannot clear key cache.', 'learnpress' );
		}
		global $wpdb;
		$in_str      = '';
		$prepare_arr = [];
		foreach ( $key_caches as $val ) {
			$in_str       .= "'%s',";
			$prepare_arr[] = $val;
		}
		$in_str     = rtrim( $in_str, ',' );
		$table_name = Thim_Cache_DB::instance()->table_name;
		$sql        = "DELETE FROM $table_name WHERE key_cache IN ( " . $in_str . ')';
		$delete     = $wpdb->query( $wpdb->prepare( $sql, $prepare_arr ) );
		LP_Database::getInstance()->check_execute_has_error();
		return $delete;
	}
	/**
	 * [clear_user_items_if_exist remove all user item id when user is removed]
	 * @param  integer $course_id [description]
	 * @param  array   $user_ids  [description]
	 * @return [type]             [description]
	 */
	public function clear_user_items_if_exist( $course_id = 0, $user_ids = [] ) {
		global $wpdb;
		// $wpdb->process_fields();
		$table_name  = LP_Database::getInstance()->tb_lp_user_items;
		$prepare_arr = [ $course_id, LP_COURSE_CPT ];
		$in_str      = '';
		foreach ( $user_ids as $id ) {
			$in_str       .= '%d,';
			$prepare_arr[] = $id;
		}
		$in_str        = rtrim( $in_str, ',' );
		$user_item_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_item_id from $table_name WHERE item_id=%d AND item_type='%s' AND user_id IN (" . $in_str . ')',
				$prepare_arr
			)
		);
		$sql           = "DELETE FROM $table_name WHERE item_id=%d AND item_type='%s' AND user_id IN (" . $in_str . ')';
		$delete        = $wpdb->query( $wpdb->prepare( $sql, $prepare_arr ) );
		LP_Database::getInstance()->check_execute_has_error();
		if ( $delete ) {
			$this->clear_user_items_relationship_if_exist( $user_item_ids );
			return $delete;
		}
	}

	/**
	 * [clear_user_items_relationship_if_exist delete user_item meta and user_item result when remove user]
	 * @param  array  $user_item_ids [description]
	 * @return [type]                [description]
	 */
	public function clear_user_items_relationship_if_exist( $user_item_ids = [] ) {
		global $wpdb;
		$ui_results_table = LP_Database::getInstance()->tb_lp_user_item_results;
		$ui_meta_table    = LP_Database::getInstance()->tb_lp_user_itemmeta;
		$prepare_arr      = [];
		$in               = '';
		foreach ( $user_item_ids as $id ) {
			$prepare_arr[] = intval( $id );
			$in           .= '%d,';
		}
		$in = rtrim( $in, ',' );
		$wpdb->query( $wpdb->prepare( "DELETE FROM $ui_results_table where user_item_id IN (" . $in . ')', $prepare_arr ) );
		LP_Database::getInstance()->check_execute_has_error();
		$wpdb->query( $wpdb->prepare( "DELETE FROM $ui_meta_table where learnpress_user_item_id IN (" . $in . ')', $prepare_arr ) );
		LP_Database::getInstance()->check_execute_has_error();
	}

	/**
	 * [update_course_cache update cache after assign or remove student]
	 * @param  integer $course_id [course id]
	 * @return [type]             [description]
	 */
	public function update_course_cache( $course_id = 0 ) {
		$lp_course_cache = new LP_Course_Cache( true );
		$lp_course_db    = LP_Course_DB::getInstance();
		$total           = $lp_course_db->get_total_user_enrolled( $course_id );
		$lp_course_cache->set_total_students_enrolled( $course_id, $total );
		$total_enrolled_or_purchased = $lp_course_db->get_total_user_enrolled_or_purchased( $course_id );
		$lp_course_cache->set_total_students_enrolled_or_purchased( $course_id, $total_enrolled_or_purchased );
	}

	/**
	 * [check_permission check permission to access api]
	 * @param  [type] $request [wp request]
	 * @return [bool]
	 */
	public function check_permission( $request ): bool {
		$permission = current_user_can( 'administrator' );
		return $permission;
	}
}
