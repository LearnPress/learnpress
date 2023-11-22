<?php

/**
 * Class LP_Admin_Ajax
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */

use LearnPress\Helpers\Template;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Admin_Ajax' ) ) {

	/**
	 * Class LP_Admin_Ajax
	 */
	class LP_Admin_Ajax {
		public function __construct() {
		}

		/**
		 * Add action ajax
		 */
		public static function init() {
			if ( ! is_user_logged_in() ) {
				return;
			}

			$ajax_events = array(
				'create_page'            => false, // Use create new page on Settings
				'load_chart'             => false,
				'search_course_category' => false,
				'custom_stats'           => false,
				'get_page_permalink'     => false,
			);

			foreach ( $ajax_events as $ajax_event => $nopriv ) {
				add_action( 'wp_ajax_learnpress_' . $ajax_event, array( __CLASS__, $ajax_event ) );

				// enable for non-logged in users
				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_learnpress_' . $ajax_event, array( __CLASS__, $ajax_event ) );
				}
			}

			do_action( 'learn-press/ajax/admin-load', __CLASS__ );

			$ajax_events = array(
				'search_items' => 'modal_search_items',
				'update-payment-order', // Update ordering of payments when user changing.
				'update-payment-status', // Enable type payment

				// admin editor
				'admin_course_editor',
				'admin_quiz_editor',
				'admin_question_editor',
				'duplicator', // Duplicate course, lesson, quiz, question.
				'modal_search_items', // Used to search courses on LP Order
				'modal_search_users', // Used to search users on LP Order
				'add_items_to_order', // Used to add courses on LP Order
				'remove_items_from_order', // Used to remove items from LP Order
				'update_email_status', // Use for enable email on LP Settings
				'search-authors', // Used to search username on input some page (list courses, lp orders, quizzes, questions... on the Backend
				//'skip-notice-install',
			);

			foreach ( $ajax_events as $action => $callback ) {
				if ( is_numeric( $action ) ) {
					$action = $callback;
				}

				$actions = LP_Request::parse_action( $action );
				$method  = $actions['action'];

				if ( ! is_callable( $callback ) ) {
					$method   = preg_replace( '/-/', '_', $method );
					$callback = array( __CLASS__, $method );
				}

				LP_Request::register_ajax( $action, $callback );
			}
		}

		/**
		 * Search user on some pages on the Backend
		 */
		public static function search_authors() {
			$args  = array(
				'orderby'        => 'name',
				'order'          => 'ASC',
				'search'         => sprintf( '*%s*', esc_attr( LP_Request::get_string( 'term' ) ) ),
				'search_columns' => array( 'user_login', 'user_email' ),
			);
			$q     = new WP_User_Query( $args );
			$users = array();

			$results = $q->get_results();

			if ( $results ) {
				foreach ( $results as $result ) {
					$users[] = array(
						'id'   => $result->ID,
						'text' => learn_press_get_profile_display_name( $result->ID ),
					);
				}
			}
			echo json_encode(
				array(
					'results' => $users,
				)
			);
			die();
		}

		/**
		 * Hide notice install
		 * @deprecated 4.2.3.1
		 */
		/*public static function skip_notice_install() {
			if ( ! current_user_can( ADMIN_ROLE ) ) { // Fix security.
				return;
			}

			delete_option( 'learn_press_install' );
		}*/

		/**
		 * Handle ajax admin course editor.
		 *
		 * @since 3.0.0
		 */
		public static function admin_course_editor() {
			$editor = LP_Admin_Editor::get_editor_course();
			self::admin_editor( $editor );
		}

		/**
		 * Handle ajax admin question editor.
		 *
		 * @since 3.0.0
		 */
		public static function admin_question_editor() {
			$editor = LP_Admin_Editor::get_editor_question();
			self::admin_editor( $editor );
		}

		/**
		 * Handle ajax admin quiz editor.
		 *
		 * @since 3.0.0
		 */
		public static function admin_quiz_editor() {
			$editor = LP_Admin_Editor::get_editor_quiz();
			self::admin_editor( $editor );
		}

		/**
		 * @param LP_Admin_Editor $editor
		 *
		 * @since 3.0.2
		 */
		public static function admin_editor( &$editor ) {
			$result = $editor->dispatch();

			if ( is_wp_error( $result ) ) {
				learn_press_send_json_error( $result->get_error_message() );
			} elseif ( ! $result ) {
				learn_press_send_json_error();
			}

			learn_press_send_json_success( $result );
		}

		/**
		 * Duplicate course, lesson, quiz, question.
		 *
		 * @since 3.0.0
		 *
		 * @note tungnx checked has use
		 */
		public static function duplicator() {
			if ( ! current_user_can( ADMIN_ROLE ) ) { // Fix security.
				return;
			}

			$nonce = LP_Request::get_param( 'nonce' );
			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				die( 'Nonce is invalid!' );
			}

			$post_id = intval( $_GET['id'] ?? 0 );

			// get post type
			$post_type = learn_press_get_post_type( $post_id );

			if ( ! $post_id ) {
				learn_press_send_json_error( __( 'Oops! ID not found', 'learnpress' ) );
			} else {

				$new_item_id = '';

				$duplicate_args = apply_filters( 'learn-press/duplicate-post-args', array( 'post_status' => 'publish' ) );

				switch ( $post_type ) {
					case LP_COURSE_CPT:
						$curd        = new LP_Course_CURD();
						$new_item_id = $curd->duplicate(
							$post_id,
							array(
								'exclude_meta' => array(
									'order-pending',
									'order-processing',
									'order-completed',
									'order-cancelled',
									'order-failed',
									'count_enrolled_users',
									'_lp_sample_data',
									'_lp_retake_count',
								),
							)
						);
						break;
					case LP_LESSON_CPT:
						$curd        = new LP_Lesson_CURD();
						$new_item_id = $curd->duplicate( $post_id, $duplicate_args );
						break;
					case LP_QUIZ_CPT:
						$curd        = new LP_Quiz_CURD();
						$new_item_id = $curd->duplicate( $post_id, $duplicate_args );
						break;
					case LP_QUESTION_CPT:
						$curd        = new LP_Question_CURD();
						$new_item_id = $curd->duplicate( $post_id, $duplicate_args );
						break;
					default:
						break;
				}

				if ( is_wp_error( $new_item_id ) ) {
					learn_press_send_json_error( __( 'Duplicate post failed. Please try again', 'learnpress' ) );
				} else {
					learn_press_send_json_success( admin_url( 'post.php?post=' . $new_item_id . '&action=edit' ) );
				}
			}
		}

		/**
		 * Update ordering of payments when user changing.
		 *
		 * @since 3.0.0
		 * @version 1.0.1
		 * @note tungnx checked has use
		 */
		public static function update_payment_order() {
			if ( ! current_user_can( ADMIN_ROLE ) ) { // Fix security.
				return;
			}

			$nonce = LP_Request::get_param( 'nonce' );
			if ( ! wp_verify_nonce( $nonce, 'lp-settings' ) ) {
				die( 'Nonce is invalid!' );
			}

			$payment_order = learn_press_get_request( 'order' );
			update_option( 'learn_press_payment_order', $payment_order );

			die( 'Order of Payment Gateway is updated success' );
		}

		/**
		 * Enable type payment
		 *
		 * @since 3.0.0
		 * @version 1.0.1
		 * @note tungnx checked has use
		 */
		public static function update_payment_status() {
			$payment_id = LP_Request::get_param( 'id' );
			$status     = LP_Request::get_param( 'status' );
			$payment    = LP_Gateways::instance()->get_gateway( $payment_id );

			if ( ! $payment ) {
				return;
			}

			if ( ! current_user_can( ADMIN_ROLE ) ) { // Fix security.
				return;
			}

			$nonce = LP_Request::get_param( 'nonce' );
			if ( ! wp_verify_nonce( $nonce, 'lp-settings' ) ) {
				die( 'Nonce is invalid!' );
			}

			$response[ $payment->id ] = $payment->enable( $status == 'yes' );

			$lp_settings_cache = new LP_Settings_Cache( true );
			$lp_settings_cache->clean_lp_settings();

			learn_press_send_json( $response );
		}

		/**
		 * nable email on LP Settings
		 *
		 * @since 3.0.0
		 * @note tungnnx checked has use
		 */
		public static function update_email_status() {
			$email_id = LP_Request::get_string( 'id' );
			$status   = LP_Request::get_string( 'status' );
			$response = array();

			if ( ! current_user_can( ADMIN_ROLE ) ) { // Fix security.
				return;
			}

			$nonce = LP_Request::get_param( 'nonce' );
			if ( ! wp_verify_nonce( $nonce, 'lp-settings' ) ) {
				die( 'Nonce is invalid!' );
			}

			if ( $email_id ) {

				$email = LP_Emails::get_email( $email_id );
				if ( ! $email ) {
					return;
				}

				$response[ $email->id ] = $email->enable( $status == 'yes' );
			} else {
				$emails = LP_Emails::instance()->emails;
				foreach ( $emails as $email ) {
					$response[ $email->id ] = $email->enable( $status == 'yes' );
				}
			}

			$lp_settings_cache = new LP_Settings_Cache( true );
			$lp_settings_cache->clean_lp_settings();

			learn_press_send_json( $response );
		}

		/**
		 * Search items by requesting params.
		 */
		public static function modal_search_items() {
			$term       = LP_Request::get_param( 'term' );
			$type       = LP_Request::get_param( 'type' );
			$context    = LP_Request::get_param( 'context' );
			$context_id = LP_Request::get_param( 'context_id' );
			$paged      = LP_Request::get_param( 'paged' );
			$exclude    = LP_Request::get_param( 'exclude' );

			if ( ! current_user_can( ADMIN_ROLE ) ) { // Fix security.
				return;
			}

			$nonce = LP_Request::get_param( 'nonce' );
			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				die( 'Nonce is invalid!' );
			}

			$search = new LP_Modal_Search_Items( compact( 'term', 'type', 'context', 'context_id', 'paged', 'exclude' ) );

			learn_press_send_json(
				array(
					'html'  => $search->get_html_items(),
					'nav'   => $search->get_pagination(),
					'items' => $search->get_items(),
				)
			);
		}

		/**
		 * Search items by requesting params.
		 *
		 * @note tungnx checked has use
		 */
		public static function modal_search_users() {
			$term         = LP_Request::get_param( 'term' );
			$type         = LP_Request::get_param( 'type' );
			$context      = LP_Request::get_param( 'context' );
			$context_id   = LP_Request::get_param( 'context_id' );
			$paged        = LP_Request::get_param( 'paged' );
			$multiple     = LP_Request::get_param( 'multiple' ) == 'yes';
			$text_format  = LP_Request::get_param( 'text_format' );
			$exclude      = LP_Request::get_param( 'exclude' );
			$roles_accept = apply_filters(
				'lp/backend/roles/can-search-users',
				[ ADMIN_ROLE ]
			);

			$flag = false;
			foreach ( $roles_accept as $role ) {
				if ( current_user_can( $role ) ) {
					$flag = true;
				}
			}

			if ( ! $flag ) {
				return;
			}

			$nonce = LP_Request::get_param( 'nonce' );
			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				die( 'Nonce is invalid!' );
			}

			$search = new LP_Modal_Search_Users( compact( 'term', 'type', 'context', 'context_id', 'paged', 'multiple', 'text_format', 'exclude' ) );

			learn_press_send_json(
				array(
					'html'  => $search->get_html_items(),
					'nav'   => $search->get_pagination(),
					'users' => $search->get_items(),
				)
			);
		}

		/**
		 * Search course category.
		 */
		public static function search_course_category() {
			global $wpdb;
			$sql   = 'SELECT `t`.`term_id` as `id`, '
					. ' `t`.`name` `text` '
					. " FROM {$wpdb->terms} t "
					. "		INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id AND taxonomy='course_category' "
					. ' WHERE `t`.`name` LIKE %s';
			$s     = '%' . filter_input( INPUT_GET, 'q' ) . '%';
			$query = $wpdb->prepare( $sql, $s );
			$items = $wpdb->get_results( $query );
			$data  = array( 'items' => $items );
			echo json_encode( $data );
			exit();
		}

		/**
		 * Remove an item from lp order
		 *
		 * @note tungnx checked has use
		 */
		public static function remove_items_from_order() {
			// ensure that user has permission
			if ( ! current_user_can( 'edit_lp_orders' ) ) {
				die( __( 'Access denied', 'learnpress' ) );
			}

			// verify nonce
			$nonce = learn_press_get_request( 'remove_nonce' );
			if ( ! wp_verify_nonce( $nonce, 'remove_order_item' ) ) {
				die( __( 'Nonce check failed', 'learnpress' ) );
			}

			// validate order
			$order_id = learn_press_get_request( 'order_id' );
			if ( ! is_numeric( $order_id ) || learn_press_get_post_type( $order_id ) != 'lp_order' ) {
				die( __( 'Invalid order', 'learnpress' ) );
			}

			// validate item
			$items = learn_press_get_request( 'items' );

			$order = learn_press_get_order( $order_id );

			global $wpdb;

			foreach ( $items as $item_id ) {
				$order->remove_item( $item_id );
			}

			$order_data                  = learn_press_update_order_items( $order_id );
			$currency_symbol             = learn_press_get_currency_symbol( $order_data['currency'] );
			$order_data['subtotal_html'] = learn_press_format_price( $order_data['subtotal'], $currency_symbol );
			$order_data['total_html']    = learn_press_format_price( $order_data['total'], $currency_symbol );
			$order_items                 = $order->get_items();
			if ( $order_items ) {
				$html = '';
				foreach ( $order_items as $item ) {
					ob_start();
					include learn_press_get_admin_view( 'meta-boxes/order/order-item.php' );
					$html .= ob_get_clean();
				}
			}

			learn_press_send_json(
				array(
					'result'     => 'success',
					'item_html'  => $html,
					'order_data' => $order_data,
				)
			);
		}

		/**
		 * Add courses to order
		 *
		 * @note tungnx checked has use
		 */
		public static function add_items_to_order() {
			if ( ! current_user_can( ADMIN_ROLE ) ) { // Fix security
				return;
			}

			$nonce = LP_Request::get_param( 'nonce' );
			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				die( 'Nonce is invalid!' );
			}

			$response = array(
				'result' => 'error',
			);

			// ensure that user has permission
			if ( ! current_user_can( 'edit_lp_orders' ) ) {
				die( __( 'Permission denied', 'learnpress' ) );
			}

			// validate order
			$order_id = learn_press_get_request( 'order_id' );
			if ( ! is_numeric( $order_id ) || learn_press_get_post_type( $order_id ) != 'lp_order' ) {
				die( __( 'Invalid order', 'learnpress' ) );
			}

			// validate item
			$item_ids   = LP_Request::get_param( 'items', [] );
			$order      = learn_press_get_order( $order_id );
			$order_item = $order->add_items( $item_ids );
			if ( $order_item ) {
				$html                        = '';
				$order_items                 = $order->get_items();
				$order_data                  = learn_press_update_order_items( $order_id );
				$currency_symbol             = learn_press_get_currency_symbol( $order_data['currency'] );
				$order_data['subtotal_html'] = learn_press_format_price( $order_data['subtotal'], $currency_symbol );
				$order_data['total_html']    = learn_press_format_price( $order_data['total'], $currency_symbol );

				if ( $order_items ) {
					foreach ( $order_items as $item ) {
						if ( ! in_array( $item['id'], $order_item ) ) {
							continue;
						}

						ob_start();
						Template::instance()->get_admin_template( 'meta-boxes/order/order-item.php', compact( 'item', 'order' ) );
						$html .= ob_get_clean();
					}
				}

				$response = array(
					'result'     => 'success',
					'item_html'  => $html,
					'order_data' => $order_data,
				);
			}

			learn_press_send_json( $response );
		}

		/**
		 * Get content send via payload and parse to json.
		 *
		 * @param mixed $params (Optional) List of keys want to get from payload.
		 *
		 * @return array|bool|mixed|object
		 * @deprecated 4.1.6.9
		 */
		/*public static function get_php_input( $params = '' ) {
			static $data = false;
			if ( false === $data ) {
				try {
					$data = json_decode( file_get_contents( 'php://input' ), true );
				} catch ( Exception $exception ) {
				}
			}

			if ( $data && func_num_args() > 0 ) {
				$params = is_array( func_get_arg( 0 ) ) ? func_get_arg( 0 ) : func_get_args();
				if ( $params ) {
					$request = array();
					foreach ( $params as $key ) {
						$request[] = array_key_exists( $key, $data ) ? $data[ $key ] : false;
					}

					return $request;
				}
			}

			return $data;
		}*/

		/**
		 * Parse request content into var.
		 * Normally, parse and assign to $_POST or $_GET.
		 *
		 * @param $var
		 * @deprecated 4.1.6.9
		 */
		/*public static function parsePhpInput( &$var ) {
			$data = self::get_php_input();

			if ( $data ) {
				foreach ( $data as $k => $v ) {
					$var[ $k ] = $v;
				}
			}
		}*/

		public static function load_chart() {
			if ( ! class_exists( 'LP_Submenu_Statistics' ) ) {
				$statistic = include_once LP_PLUGIN_PATH . '/inc/admin/sub-menus/class-lp-submenu-statistics.php';
			} else {
				$statistic = new LP_Submenu_Statistics();
			}
			$statistic->load_chart();
		}

		public static function json_search_customer_name( $query ) {
			global $wpdb;

			$term = LP_Helper::sanitize_params_submitted( $_REQUEST['term'] );
			if ( method_exists( $wpdb, 'esc_like' ) ) {
				$term = $wpdb->esc_like( $term );
			} else {
				$term = $wpdb->esc_like( $term );
			}

			$query->query_from  .= " INNER JOIN {$wpdb->usermeta} AS user_name ON {$wpdb->users}.ID = user_name.user_id AND ( user_name.meta_key = 'first_name' OR user_name.meta_key = 'last_name' ) ";
			$query->query_where .= $wpdb->prepare( ' OR user_name.meta_value LIKE %s ', '%' . $term . '%' );
		}

		/**
		 * create new page on LP Settings
		 *
		 * @note tungnnx checked use
		 */
		public static function create_page() {
			$response = array(
				'code'    => 0,
				'message' => '',
			);

			/**
			 * Check valid
			 *
			 * 1. Capability - user can edit pages (add\edit\delete)
			 * 2. Check nonce return true
			 * 3. param post page_name not empty
			 *
			 * @since  3.2.6.8
			 * @author tungnx
			 */
			if ( ! current_user_can( 'edit_pages' ) || empty( $_POST['page_name'] ) ) {
				$response['message'] = 'Request invalid';
				learn_press_send_json( $response );
			}

			$page_name  = LP_Helper::sanitize_params_submitted( $_POST['page_name'] );
			$field_name = LP_Request::get_param( 'field_name' );

			if ( $page_name ) {
				$data_create_page = array(
					'post_title' => $page_name,
				);

				$page_id = LP_Helper::create_page( $data_create_page, $field_name );

				if ( $page_id ) {
					$response['code']    = 1;
					$response['message'] = 'create page success';
					$response['page']    = get_post( $page_id );
					$html                = learn_press_pages_dropdown( '', '', array( 'echo' => false ) );
					preg_match_all( '!value=\"([0-9]+)\"!', $html, $matches );
					$response['positions'] = $matches[1];
					$response['html']      = '<a href="' . get_edit_post_link( $page_id ) . '" target="_blank">' . __( 'Edit Page', 'learnpress' ) . '</a>&nbsp;';
					$response['html']     .= '<a href="' . get_permalink( $page_id ) . '" target="_blank">' . __( 'View Page', 'learnpress' ) . '</a>';
				} else {
					$response['error'] = __( 'Error! Page creation failed. Please try again.', 'learnpress' );
				}
			} else {
				$response['error'] = __( 'Empty page name!', 'learnpress' );
			}
			learn_press_send_json( $response );
		}

		/**
		 * Get edit|view link of a page
		 */
		public static function get_page_permalink() {
			$page_id = (int) $_REQUEST['page_id'] ?? 0;
			?>

			<a href="<?php echo get_edit_post_link( $page_id ); ?>"
				target="_blank"><?php _e( 'Edit Page', 'learnpress' ); ?></a>
			<a href="<?php echo get_permalink( $page_id ); ?>"
				target="_blank"><?php _e( 'View Page', 'learnpress' ); ?></a>

			<?php
			die();
		}

		/**
		 * Get date from, to for static chart
		 */
		public static function custom_stats() {
			$from      = LP_Helper::sanitize_params_submitted( $_REQUEST['from'] ?? 0 );
			$to        = LP_Helper::sanitize_params_submitted( $_REQUEST['to'] ?? 0 );
			$date_diff = strtotime( $to ) - strtotime( $from );
			if ( $date_diff <= 0 || $from == 0 || $to == 0 ) {
				die();
			}
			learn_press_process_chart( learn_press_get_chart_students( $to, 'days', floor( $date_diff / ( 60 * 60 * 24 ) ) + 1 ) );
			die();
		}
	}

	add_action( 'init', array( 'LP_Admin_Ajax', 'init' ) );
}

new LP_Admin_Ajax();
