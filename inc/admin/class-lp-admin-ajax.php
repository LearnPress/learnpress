<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'LP_Admin_Ajax' ) ) {

	/**
	 * Class LP_Admin_Ajax
	 */
	class LP_Admin_Ajax {
		/**
		 * Add action ajax
		 */
		public static function init() {

			if ( ! is_user_logged_in() ) {
				return;
			}

			$ajaxEvents = array(
				'create_page'                     => false,
				'add_quiz_question'               => false,
				'convert_question_type'           => false,
				'update_quiz_question_state'      => false,
				'update_curriculum_section_state' => false,
				'quick_add_item'                  => false,
				'add_new_item'                    => false,
				'toggle_lesson_preview'           => false,
				'remove_course_items'             => false,
				'search_courses'                  => false,
				'add_item_to_order'               => false,
				'remove_order_item'               => false,
				'plugin_action'                   => false,
				'search_questions'                => false,
				'remove_quiz_question'            => false,
				'modal_search_items'              => false,
				'add_item_to_section'             => false,
				'remove_course_section'           => false,
				'dismiss_notice'                  => false,
				'search_users'                    => false,
				'load_chart'                      => false,
				'search_course'                   => false,
				'search_course_category'          => false,
				/////////////
				'quick_add_lesson'                => false,
				'quick_add_quiz'                  => false,
				'be_teacher'                      => false,
				'custom_stats'                    => false,
				'ignore_setting_up'               => false,
				'get_page_permalink'              => false,
				'dummy_image'                     => false,
				'update_add_on_status'            => false,
				'plugin_install'                  => false,
				'bundle_activate_add_ons'         => false,
				'install_sample_data'             => false,
				// Duplicate Course
				'duplicate_course'                => false,
				'duplicate_question'              => false,
				// Remove Notice
				'remove_notice_popup'             => false,
				// Update order status
				'update_order_status'             => false,


			);
			foreach ( $ajaxEvents as $ajaxEvent => $nopriv ) {
				add_action( 'wp_ajax_learnpress_' . $ajaxEvent, array( __CLASS__, $ajaxEvent ) );
				// enable for non-logged in users
				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_learnpress_' . $ajaxEvent, array( __CLASS__, $ajaxEvent ) );
				}
			}
			add_filter( 'learn_press_modal_search_items_exclude', array(
				__CLASS__,
				'_modal_search_items_exclude'
			), 10, 4 );
			add_filter( 'learn_press_modal_search_items_not_found', array(
				__CLASS__,
				'_modal_search_items_not_found'
			), 10, 2 );
			add_action( 'admin_init', array( __CLASS__, 'do_ajax' ), - 1000 );
			//add_action( 'load-post-new.php', array( __CLASS__, 'do_ajax' )  );
			//add_action( 'load-post.php', array( __CLASS__, 'do_ajax' )  );

			do_action( 'learn_press_admin_ajax_load', __CLASS__ );

			$ajax_events = array(
				'add_question',
				'delete_quiz_question',
				'update_quiz',
				'update_quiz_question_orders',
				'update_question_answer_orders',
				'change_question_type',
				'closed_question_box',
				'add_quiz_questions',
				'clear_quiz_question',
				'search_items' => 'modal_search_items',
				'update-payment-order',
				'bundle_update_quiz_questions',
				'modal-search-questions',
				'get-question-data',
				'update_curriculum',
				'modal-search-items',
				'modal-search-users',
				'add-items-to-order',
				'remove-items-from-order'
			);
			foreach ( $ajax_events as $ajax_event => $callback ) {
				if ( ! is_string( $ajax_event ) ) {
					$ajax_event = $callback;
				}
				$ajax_event = preg_replace( '~[-]+~', '_', $ajax_event );
				$callback   = preg_replace( '~[-]+~', '_', $callback );
				add_action( "learn-press/ajax/{$ajax_event}", array( __CLASS__, $callback ) );
			}
		}

		/**
		 * Handle ajax update curriculum.
		 *
		 * @since 3.0.0
		 */
		public static function update_curriculum() {
			check_ajax_referer( 'learnpress_update_curriculum', 'nonce' );

			$args = wp_parse_args( $_REQUEST, array(
				'course-id' => false,
				'type'      => ''
			) );

			$course_id = $args['course-id'];
			$course    = learn_press_get_course( $args['course-id'] );
			if ( ! $course ) {
				wp_send_json_error();
			}

			$curd = new LP_Section_CURD( $course_id );

			$result = $args['type'];
			switch ( $args['type'] ) {
				case 'sync-sections':
					$result = $course->get_curriculum_raw();

					break;

				case 'add-items-to-section':
					$items      = isset( $_POST['items'] ) ? $_POST['items'] : false;
					$section_id = isset( $_POST['section-id'] ) ? $_POST['section-id'] : false;

					$items = wp_unslash( $items );
					$items = json_decode( $items, true );

					if ( ! $items || ! $section_id ) {
						$result = new WP_Error();
						break;
					}

					$result = $curd->add_items_section( $section_id, $items );

					break;

				case 'new-section':
					$args = array(
						'section_course_id'   => $course_id,
						'section_description' => '',
						'section_name'        => '',
						'items'               => [],
					);

					$section = $curd->create( $args );
					$result  = array(
						'id'          => $section['section_id'],
						'items'       => $section['items'],
						'title'       => $section['section_name'],
						'description' => $section['section_description'],
						'course_id'   => $section['section_course_id'],
						'order'       => $section['section_order'],
					);
					break;

				case 'sort-sections':
					$orders = ! empty( $args['orders'] ) ? $args['orders'] : false;
					if ( ! $orders ) {
						break;
					}

					$orders = wp_unslash( $orders );
					$orders = json_decode( $orders, true );
					$result = $curd->sort_sections( $orders );

					break;

				case 'remove-section':
					$section_id = ! empty( $args['section-id'] ) ? $args['section-id'] : false;
					$curd->delete( $section_id );
					break;

				case 'update-section':
					$section = ! empty( $args['section'] ) ? $args['section'] : false;
					$section = wp_unslash( $section );
					$section = json_decode( $section, true );

					if ( ! is_array( $section ) || empty( $section ) ) {
						break;
					}

					$update = array(
						'section_id'          => $section['id'],
						'section_name'        => $section['title'],
						'section_description' => $section['description'],
						'section_order'       => $section['order'],
						'section_course_id'   => $section['course_id'],
					);

					$result = $curd->update( $update );

					break;

				case 'search-items':
					$query = isset( $_POST['query'] ) ? $_POST['query'] : '';
					$type  = isset( $_POST['item-type'] ) ? $_POST['item-type'] : '';
					$page  = ! empty( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;

					$search = new LP_Modal_Search_Items( array(
						'type'       => $type,
						'context'    => 'course',
						'context_id' => $course_id,
						'term'       => $query,
						'limit'      => 10,
						'paged'      => $page
					) );

					$id_items = $search->get_items();
					$items    = get_posts( array(
						'post_type' => $type,
						'post__in'  => $id_items
					) );

					$result = array();
					foreach ( $items as $item ) {
						$result[] = array(
							'id'    => $item->ID,
							'title' => $item->post_title,
							'type'  => $item->post_type
						);
					}

					break;
			}

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}

			wp_send_json_success( $result );
		}

		/**
		 * Get question data
		 */
		public static function get_question_data() {
			global $current_screen;
			// Force to init quiz screen to make some hooks work correct.
			$current_screen = WP_Screen::get( LP_QUIZ_CPT );

			self::parsePhpInput( $_REQUEST );

			$question_id = learn_press_get_request( 'id' );

			if ( $question = learn_press_get_question( $question_id ) ) {
				global $post;
				$post = get_post( $question_id );
				setup_postdata( $post );
				LP()->load_meta_box();

				$view = learn_press_get_admin_view( "quiz/html-loop-question" );
				include $view;
			}
		}

		public static function modal_search_questions() {
			self::parsePhpInput( $_REQUEST );
			$paged   = learn_press_get_request( 'paged' );
			$args    = array(
				'term'       => learn_press_get_request( 'term' ),
				'context'    => 'lp_quiz',
				'type'       => 'lp_question',
				'context_id' => learn_press_get_request( 'id' ),
				'exclude'    => learn_press_get_request( 'exclude' ),
				'limit'      => learn_press_get_request( 'limit' ),
				'paged'      => $paged
			);
			$results = LP_Query_Search::search_items( $args );
			$nav     = '';
			if ( $results['items'] ) {
				foreach ( $results['items'] as $k => $item ) {
					$results['items'][ $k ] = array(
						'id'   => $item->ID,
						'text' => get_the_title( $item->ID )
					);
				}
				if ( $paged && $results['pages'] > 1 ) {
					$pagenum_link = html_entity_decode( get_pagenum_link() );

					$query_args = array();
					$url_parts  = explode( '?', $pagenum_link );

					if ( isset( $url_parts[1] ) ) {
						wp_parse_str( $url_parts[1], $query_args );
					}

					$pagenum_link = remove_query_arg( array_keys( $query_args ), $pagenum_link );
					$pagenum_link = trailingslashit( $pagenum_link ) . '%_%';
					$nav          = paginate_links( array(
						'base'      => $pagenum_link,
						//'format'    => $format,
						'total'     => $results['pages'],
						'current'   => max( 1, $paged ),
						'mid_size'  => 1,
						'add_args'  => array_map( 'urlencode', $query_args ),
						'prev_text' => __( '<', 'learnpress' ),
						'next_text' => __( '>', 'learnpress' ),
						'type'      => ''
					) );
				}
			}


			learn_press_send_json( array(
				'total'     => $results['total'],
				'items'     => $results['items'],
				'navigator' => $nav
			) );
			$output = '';
			if ( ! empty( $items ) ) {
				global $post;
				$origin_post = $post;
				foreach ( $items as $post ) {
					setup_postdata( $post );
					$output .= sprintf( '
                    <li class="%s" data-id="%2$d" data-type="%4$s" data-text="%3$s">
                        <label>
                            <input type="checkbox" value="%2$d" name="selectedItems[]">
                            <span class="lp-item-text">%3$s</span>
                        </label>
                    </li>
                    ', 'lp-result-item', get_the_ID(), esc_attr( get_the_title() ), $post->post_type );
				}
				$post = $origin_post;
				setup_postdata( $post );
			} else {
				$output .= '<li>' . apply_filters( 'learn-press/modal-search-questions/item-not-found', __( 'No question found', 'learnpress' ), 'lp_quiz' ) . '</li>';
			}
			echo $output;
		}


		/**
		 * Update ordering of payments when user changing.
		 */
		public static function update_payment_order() {
			$payment_order = learn_press_get_request( 'order' );
			update_option( 'learn_press_payment_order', $payment_order );
		}

		public static function bundle_update_quiz_questions() {
			self::parsePhpInput( $_REQUEST );
			learn_press_debug( $_REQUEST );
		}

		/**
		 * Get content send via payload and parse to json.
		 *
		 * @param mixed $params (Optional) List of keys want to get from payload.
		 *
		 * @return array|bool|mixed|object
		 */
		public static function getPhpInput( $params = '' ) {
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
		}

		/**
		 * Parse request content into var.
		 * Normally, parse and assign to $_POST or $_GET.
		 *
		 * @param $var
		 */
		public static function parsePhpInput( &$var ) {
			if ( $data = self::getPhpInput() ) {
				foreach ( $data as $k => $v ) {
					$var[ $k ] = $v;
				}
			}
		}

		public static function do_ajax() {
			if ( empty( $_REQUEST['lp-ajax'] ) ) {
				return;
			}
			$action = preg_replace( '~[-]~', '_', $_REQUEST['lp-ajax'] );
			do_action( "learn-press/ajax/{$action}" );
			die();
		}

		/**
		 * Ajax callback to add new question into quiz
		 */
		public static function add_question() {
			list( $type, $title, $order, $quiz_id ) = learn_press_get_request_args( array(
				'type',
				'title',
				'order',
				'quiz_id'
			) );
			$question_id = LP_Question_Factory::add_question(
				array(
					'type'    => $type,
					'title'   => $title,
					'quiz_id' => $quiz_id,
					'order'   => $order
				)
			);
			$response    = array();
			if ( $question_id ) {
				$response['result'] = 'success';
				$response['id']     = $question_id;
				$response['type']   = $type;
			} else {
				$response['result']  = 'error';
				$response['message'] = __( 'Insert question failed!', 'learnpress' );
			}
			learn_press_send_json( $response );
		}

		/**
		 * Delete a question from quiz
		 */
		public static function delete_quiz_question() {
			list( $quiz_id, $id, $nonce, $extra_data ) = learn_press_get_request_args(
				array(
					'quiz_id',
					'id',
					'nonce',
					'extra_data'
				)
			);
			global $wpdb;
			$response = array( 'result' => 'success' );
			if ( ! ( LP_QUIZ_CPT == get_post_type( $quiz_id ) && LP_QUESTION_CPT == get_post_type( $id ) ) || ! wp_verify_nonce( $nonce, 'question-nonce' ) ) {
				$response['result']  = 'error';
				$response['message'] = __( 'Bad request.', 'learnpress' );
			} else {
				try {
					$quiz = learn_press_get_quiz( $quiz_id );
					if ( $results = $quiz->remove_question( $id, $extra_data ) ) {
						$response['message'] = __( 'Question deleted!', 'learnpress' );
						$response['ids']     = $results;
					} else {
						$response['message'] = __( 'Delete question failed.', 'learnpress' );
					}
				} catch ( Exception $exception ) {
				}
			}
			learn_press_send_json( $response );
		}

		/**
		 * Delete a question from quiz
		 */
		public static function clear_quiz_question() {
			list( $quiz_id, $ids, $nonce, $extra_data ) = learn_press_get_request_args(
				array(
					'quiz_id',
					'ids',
					'nonce',
					'extra_data'
				)
			);
			global $wpdb;
			echo sprintf( 'quiz-nonce-%d', get_current_user_id() );
			$response = array( 'result' => 'success' );
			if ( ! ( LP_QUIZ_CPT == get_post_type( $quiz_id ) ) || ! wp_verify_nonce( $nonce, sprintf( 'quiz-nonce-%d', get_current_user_id() ) ) ) {
				$response['result']  = 'error';
				$response['message'] = __( 'Bad request.', 'learnpress' );
			} else {
				try {
					$quiz = learn_press_get_quiz( $quiz_id );
					if ( $results = $quiz->remove_question( $ids, $extra_data ) ) {
						$response['message'] = __( 'Question deleted!', 'learnpress' );
						$response['ids']     = $results;
					} else {
						$response['message'] = __( 'Delete question failed.', 'learnpress' );
					}
				} catch ( Exception $exception ) {
				}
			}
			learn_press_send_json( $response );
		}

		/**
		 * Update quiz content and it's questions
		 */
		public static function update_quiz() {
			global $wpdb;
			list( $id, $questions ) = self::getPhpInput( 'id', 'questions' );
			$response = array( 'result' => 'success' );
			if ( ! $quiz = learn_press_get_quiz( $id ) ) {
				$response['result']  = 'error';
				$response['message'] = __( 'Invalid quiz.', 'learnpress' );
			} else {
				$response['data'] = $quiz->update_questions( $questions );
			}
			learn_press_send_json( $response );
		}

		/**
		 * Update state of box questions
		 */
		public static function closed_question_box() {

			list( $hidden ) = self::getPhpInput( 'hidden' );
			$data = learn_press_get_user_option( 'post-closed-box' );
			if ( ! $data ) {
				$data = array();
			}
			foreach ( $hidden as $id => $value ) {
				$index = array_search( $id, $data );
				if ( $value == 'yes' ) {
					if ( false === $index ) {
						$data[] = $id;
					}
				} else {
					if ( false !== $index ) {
						array_splice( $data, $index, 1 );
					}
				}
			}
			learn_press_update_user_option( 'post-closed-box', $data );
		}

		/**
		 * Reorder question orders
		 */
		public static function update_quiz_question_orders() {
			list( $id, $questions ) = self::getPhpInput( 'id', 'questions' );
			if ( $quiz = learn_press_get_quiz( $id ) ) {
				$quiz->update_questions_orders( $questions );
			}
		}

		/**
		 * Reorder question answer orders.
		 */
		public static function update_question_answer_orders() {
			list( $id, $answers ) = self::getPhpInput( 'id', 'answers' );
			if ( $question = learn_press_get_question( $id ) ) {
				$question->update_answer_orders( $answers );
			}
		}

		/**
		 * Change type of a question.
		 */
		public static function change_question_type() {
			list( $id, $from, $to ) = self::getPhpInput( 'id', 'from', 'to' );
			LP_Question_Factory::convert_question( $id, $from, $to );
			$question = learn_press_get_question( $id );
			$question->admin_interface();
		}

		public static function add_quiz_questions() {
			list( $id, $questions ) = self::getPhpInput( 'id', 'questions' );
			$response = array( 'questions' => array() );
			if ( $quiz = learn_press_get_quiz( $id ) ) {
				if ( $questions ) {
					foreach ( $questions as $question_id ) {
						if ( $quiz->add_question( $question_id ) ) {
							$question                              = learn_press_get_question( $question_id );
							$response['questions'][ $question_id ] = $question->admin_interface( array( 'echo' => false ) );
						}
					}
				}
			}
			learn_press_send_json( $response );
		}

		/**
		 * Search items by requesting params.
		 */
		public static function modal_search_items() {
			self::parsePhpInput( $_REQUEST );
			$term       = (string) ( stripslashes( learn_press_get_request( 'term' ) ) );
			$type       = (string) ( stripslashes( learn_press_get_request( 'type' ) ) );
			$context    = (string) ( stripslashes( learn_press_get_request( 'context' ) ) );
			$context_id = (string) ( stripslashes( learn_press_get_request( 'context_id' ) ) );
			$paged      = (string) ( stripslashes( learn_press_get_request( 'paged' ) ) );

			$search = new LP_Modal_Search_Items( compact( 'term', 'type', 'context', 'context_id', 'paged' ) );

			learn_press_send_json( array(
				'html'  => $search->get_html_items(),
				'nav'   => $search->get_pagination(),
				'items' => $search->get_items()
			) );

		}

		/**
		 * Search items by requesting params.
		 */
		public static function modal_search_users() {
			self::parsePhpInput( $_REQUEST );
			$term        = (string) ( stripslashes( learn_press_get_request( 'term' ) ) );
			$type        = (string) ( stripslashes( learn_press_get_request( 'type' ) ) );
			$context     = (string) ( stripslashes( learn_press_get_request( 'context' ) ) );
			$context_id  = (string) ( stripslashes( learn_press_get_request( 'context_id' ) ) );
			$paged       = (string) ( stripslashes( learn_press_get_request( 'paged' ) ) );
			$multiple    = (string) ( stripslashes( learn_press_get_request( 'multiple' ) ) ) == 'yes';
			$text_format = (string) ( stripslashes( learn_press_get_request( 'text_format' ) ) );

			$search = new LP_Modal_Search_Users( compact( 'term', 'type', 'context', 'context_id', 'paged', 'multiple', 'text_format' ) );

			learn_press_send_json( array(
				'html'  => $search->get_html_items(),
				'nav'   => $search->get_pagination(),
				'users' => $search->get_items()
			) );

		}

		/*************/

		public static function load_chart() {
			if ( ! class_exists( '' ) ) {
				require_once LP_PLUGIN_PATH . '/inc/admin/sub-menus/statistics.php';
			}
			LP_Admin_Submenu_Statistic::instance()->load_chart();
		}

		public static function search_course() {
			global $wpdb;
			$sql = "SELECT ID id, post_title text "
			       . " FROM {$wpdb->posts} "
			       . " WHERE post_type='lp_course' "
			       . " AND post_status in ('publish') "
			       . " AND post_title like %s";
			if ( current_user_can( LP_TEACHER_ROLE ) ) {
				$user_id = learn_press_get_current_user_id();
				$sql     .= " AND post_author=" . intval( $user_id ) . " ";
			}
			$s     = '%' . filter_input( INPUT_GET, 'q' ) . '%';
			$query = $wpdb->prepare( $sql, $s );
			$items = $wpdb->get_results( $query );
			$data  = array( 'items' => $items );
			echo json_encode( $data );
			exit();
		}

		public static function search_course_category() {
			global $wpdb;
			$sql   = "SELECT `t`.`term_id` as `id`, "
			         . " `t`.`name` `text` "
			         . " FROM {$wpdb->terms} t "
			         . "		INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id AND taxonomy='course_category' "
			         . " WHERE `t`.`name` LIKE %s";
			$s     = '%' . filter_input( INPUT_GET, 'q' ) . '%';
			$query = $wpdb->prepare( $sql, $s );
			$items = $wpdb->get_results( $query );
			$data  = array( 'items' => $items );
			echo json_encode( $data );
			exit();
		}

		public static function remove_course_items() {
			$id = learn_press_get_request( 'id' );
			if ( $id ) {
				global $wpdb;
				$in_clause = array_fill( 0, sizeof( $id ), '%d' );
				$in_clause = "IN(" . join( ",", $in_clause ) . ")";
				echo $wpdb->prepare( "
						DELETE FROM {$wpdb->prefix}learnpress_section_items
						WHERE section_item_id $in_clause
					", $id );
				$wpdb->query(
					$wpdb->prepare( "
						DELETE FROM {$wpdb->prefix}learnpress_section_items
						WHERE section_item_id $in_clause
					", $id )
				);
			}
			die();
		}

		public static function search_users() {
			if ( ! current_user_can( 'edit_lp_orders' ) ) {
				die( - 1 );
			}

			$term = stripslashes( $_REQUEST['term'] );

			if ( empty( $term ) ) {
				die();
			}

			$found_customers = array();

			add_action( 'pre_user_query', array( __CLASS__, 'json_search_customer_name' ) );

			$customers_query = new WP_User_Query( apply_filters( 'learn_press_search_customers_query', array(
				'fields'         => 'all',
				'orderby'        => 'display_name',
				'search'         => '*' . $term . '*',
				'search_columns' => array( 'ID', 'user_login', 'user_email', 'user_nicename' )
			) ) );

			remove_action( 'pre_user_query', array( __CLASS__, 'json_search_customer_name' ) );

			$customers = $customers_query->get_results();

			if ( ! empty( $customers ) ) {
				foreach ( $customers as $customer ) {
					$found_customers[] = array(
						'label' => $customer->display_name . ' (#' . $customer->ID . ' &ndash; ' . sanitize_email( $customer->user_email ) . ')',
						'value' => $customer->ID
					);
				}
			}

			echo json_encode( $found_customers );
			die();
		}

		public static function json_search_customer_name( $query ) {
			global $wpdb;

			$term = stripslashes( $_REQUEST['term'] );
			if ( method_exists( $wpdb, 'esc_like' ) ) {
				$term = $wpdb->esc_like( $term );
			} else {
				$term = like_escape( $term );
			}

			$query->query_from  .= " INNER JOIN {$wpdb->usermeta} AS user_name ON {$wpdb->users}.ID = user_name.user_id AND ( user_name.meta_key = 'first_name' OR user_name.meta_key = 'last_name' ) ";
			$query->query_where .= $wpdb->prepare( " OR user_name.meta_value LIKE %s ", '%' . $term . '%' );
		}

		public static function dismiss_notice() {
			$context   = learn_press_get_request( 'context' );
			$transient = learn_press_get_request( 'transient' );

			if ( $context ) {
				if ( $transient >= 0 ) {
					set_transient( 'learn_press_dismiss_notice_' . $context, 'off', $transient ? $transient : DAY_IN_SECONDS * 7 );
				} else {
					update_option( 'learn_press_dismiss_notice_' . $context, 'off' );
				}
			}
			die();
		}

		public static function _modal_search_items_not_found( $message, $type ) {
			switch ( $type ) {
				case 'lp_lesson':
					$message = __( 'There are no available lessons for this course, please use ', 'learnpress' );
					$message .= '<a target="_blank" href="' . admin_url( 'post-new.php?post_type=lp_lesson' ) . '">' . esc_html__( 'Adding New Item.', 'learnpress' ) . '</a>';
					break;
				case 'lp_quiz':
					$message = __( 'There are no available quizzes for this course, please use ', 'learnpress' );
					$message .= '<a target="_blank" href="' . admin_url( 'post-new.php?post_type=lp_quiz' ) . '">' . esc_html__( 'Adding New Item.', 'learnpress' ) . '</a>';
					break;
				case 'lp_question':
					$message = __( 'There are no available questions for this quiz, please use ', 'learnpress' );
					$message .= '<a target="_blank" href="' . admin_url( 'post-new.php?post_type=lp_question' ) . '">' . esc_html__( 'Adding New Item.', 'learnpress' ) . '</a>';
					break;
			}

			return $message;
		}

		/**
		 * Filter to exclude the items has already added to it's parent.
		 * Each item only use one time
		 *
		 * @param        $exclude
		 * @param        $type
		 * @param string $context
		 * @param null $context_id
		 *
		 * @return array
		 */
		public static function _modal_search_items_exclude( $exclude, $type, $context = '', $context_id = null ) {
			global $wpdb;
			$used_items = array();
			switch ( $type ) {
				case 'lp_lesson':
				case 'lp_quiz':
					$query      = $wpdb->prepare( "
						SELECT item_id
						FROM {$wpdb->prefix}learnpress_section_items si
						INNER JOIN {$wpdb->prefix}learnpress_sections s ON s.section_id = si.section_id
						INNER JOIN {$wpdb->posts} p ON p.ID = s.section_course_id
						WHERE %d
						AND p.post_type = %s
					", 1, LP_COURSE_CPT );
					$used_items = $wpdb->get_col( $query );
					break;
				case 'lp_question':
					$query      = $wpdb->prepare( "
						SELECT question_id
						FROM {$wpdb->prefix}learnpress_quiz_questions
						INNER JOIN {$wpdb->posts} q ON q.ID = qq.quiz_id
						WHERE %d
						AND q.post_type = %s
					", 1, LP_QUIZ_CPT );
					$used_items = $wpdb->get_col( $query );
					break;

			}
			if ( $used_items && $exclude ) {
				$exclude = array_merge( $exclude, $used_items );
			} else if ( $used_items ) {
				$exclude = $used_items;
			}

			return array_unique( $exclude );
		}

		public static function add_item_to_section() {
			global $wpdb;
			$section = learn_press_get_request( 'section' );
			if ( ! $section ) {
				wp_die( __( 'Error', 'learnpress' ) );
			}
			$items = (array) learn_press_get_request( 'item' );
			if ( ! $items ) {
				$max_order = $wpdb->get_var( $wpdb->prepare( "SELECT max() FROM {$wpdb}learnpress_section_items WHERE section_id = %d", $section ) );
				foreach ( $items as $item ) {

				}
			}
		}


		public static function remove_quiz_question() {
			global $wpdb;
			$quiz_id     = learn_press_get_request( 'quiz_id' );
			$question_id = learn_press_get_request( 'question_id' );
			if ( ! wp_verify_nonce( learn_press_get_request( 'remove-nonce' ), 'remove_quiz_question' ) ) {
				wp_die( __( 'Error', 'learnpress' ) );
			}
			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->prefix}learnpress_quiz_questions
				WHERE quiz_id = %d
				AND question_id = %d
			", $quiz_id, $question_id );
			$wpdb->query( $query );

			// trigger change user memorize question types
			$user_id = get_current_user_id();
			$type    = get_post_meta( $question_id, '_lp_type', true );
			if ( $type ) {
				$question_types          = get_user_meta( $user_id, '_learn_press_memorize_question_types', true );
				$question_types          = ! $question_types ? array() : $question_types;
				$counter                 = ! empty ( $question_types[ $type ] ) && $question_types[ $type ] ? absint( $question_types[ $type ] ) : 0;
				$question_types[ $type ] = $counter ? $counter -- : 0;
				update_user_meta( $user_id, '_learn_press_memorize_question_types', $question_types );
			}
			// end trigger change user memorize question types
			die();
		}

		public static function search_questions() {
			global $wpdb;

			$quiz_id = learn_press_get_request( 'quiz_id' );
			$user    = learn_press_get_current_user();
			if ( ! $user->is_admin() && get_post_field( 'post_author', $quiz_id ) != get_current_user_id() ) {
				wp_die( __( 'You have no permission to access this section.', 'learnpress' ) );
			}
			$term    = (string) ( stripslashes( learn_press_get_request( 'term' ) ) );
			$exclude = array();

			if ( ! empty( $_REQUEST['exclude'] ) ) {
				$exclude = array_map( 'intval', $_REQUEST['exclude'] );
			}

			$added = $wpdb->get_col(
				$wpdb->prepare( "
					SELECT question_id
					FROM {$wpdb->prefix}learnpress_quiz_questions
					WHERE %d
				", 1 )
			);
			if ( $added ) {
				$exclude = array_merge( $exclude, $added );
				$exclude = array_unique( $exclude );
			}

			$args = array(
				'post_type'      => array( 'lp_question' ),
				'posts_per_page' => - 1,
				'post_status'    => 'publish',
				'order'          => 'ASC',
				'orderby'        => 'parent title',
				'exclude'        => $exclude
			);
			if ( ! $user->is_admin() ) {
				$args['author'] = $user->get_id();
			}
			if ( $term ) {
				$args['s'] = $term;
			}
			$posts           = get_posts( $args );
			$found_questions = array();

			if ( ! empty( $posts ) ) {
				foreach ( $posts as $post ) {
					$found_questions[ $post->ID ] = ! empty( $post->post_title ) ? $post->post_title : sprintf( '(%s)', __( 'Untitled', 'learnpress' ) );
				}
			}

			ob_start();
			if ( $found_questions ) {
				foreach ( $found_questions as $id => $question ) {
					printf( '
						<li class="" data-id="%1$d" data-type="" data-text="%2$s">
						<label>
							<input type="checkbox" value="%1$d">
							<span class="lp-item-text">%2$s</span>
						</label>
					</li>
					', $id, $question );
				}
			} else {
				echo '<li>' . __( 'No questions found', 'learnpress' ) . '</li>';
			}

			$response = array(
				'html' => ob_get_clean(),
				'data' => $found_questions,
				'args' => $args
			);
			learn_press_send_json( $response );
		}

		public static function plugin_action() {
			$url = learn_press_get_request( 'url' );
			ob_start();
			wp_remote_get( $url );
			ob_get_clean();
			echo wp_remote_get( admin_url( 'admin.php?page=learn-press-addons&tab=installed' ) );
			die();
		}

		/**
		 * Remove an item from order
		 */
		public static function remove_items_from_order() {
			// ensure that user has permission
			if ( ! current_user_can( 'edit_lp_orders' ) ) {
				die( __( 'Permission denied', 'learnpress' ) );
			}

			// verify nonce
			$nonce = learn_press_get_request( 'remove_nonce' );
			if ( ! wp_verify_nonce( $nonce, 'remove_order_item' ) ) {
				//die( __( 'Check nonce failed', 'learnpress' ) );
			}

			// validate order
			$order_id = learn_press_get_request( 'order_id' );
			if ( ! is_numeric( $order_id ) || get_post_type( $order_id ) != 'lp_order' ) {
				die( __( 'Order invalid', 'learnpress' ) );
			}

			// validate item
			$items = learn_press_get_request( 'items' );

			$order = learn_press_get_order( $order_id );

			foreach ( $items as $item_id ) {
				$order->remove_item( $item_id );
			}

			$order_data                  = learn_press_update_order_items( $order_id );
			$currency_symbol             = learn_press_get_currency_symbol( $order_data['currency'] );
			$order_data['subtotal_html'] = learn_press_format_price( $order_data['subtotal'], $currency_symbol );
			$order_data['total_html']    = learn_press_format_price( $order_data['total'], $currency_symbol );


			learn_press_send_json(
				array(
					'result'     => 'success',
					'order_data' => $order_data
				)
			);
		}

		/**
		 * Add new course to order
		 */
		public static function add_items_to_order() {

			// ensure that user has permission
			if ( ! current_user_can( 'edit_lp_orders' ) ) {
				die( __( 'Permission denied', 'learnpress' ) );
			}

			// verify nonce
//			$nonce = learn_press_get_request( 'nonce' );
//			if ( !wp_verify_nonce( $nonce, 'add_item_to_order' ) ) {
//				die( __( 'Check nonce failed', 'learnpress' ) );
//			}

			// validate order
			$order_id = learn_press_get_request( 'order_id' );
			if ( ! is_numeric( $order_id ) || get_post_type( $order_id ) != 'lp_order' ) {
				die( __( 'Order invalid', 'learnpress' ) );
			}

			// validate item
			$item_ids = learn_press_get_request( 'items' );
			$order    = learn_press_get_order( $order_id );
			if ( $order_item_ids = $order->add_items( $item_ids ) ) {
				$html        = '';
				$order_items = $order->get_items();

				$order_data                  = learn_press_update_order_items( $order_id );
				$currency_symbol             = learn_press_get_currency_symbol( $order_data['currency'] );
				$order_data['subtotal_html'] = learn_press_format_price( $order_data['subtotal'], $currency_symbol );
				$order_data['total_html']    = learn_press_format_price( $order_data['total'], $currency_symbol );
print_r($order_items);print_r($order_item_ids);
				if ( $order_items ) {
					foreach ( $order_items as $item ) {

						if ( ! in_array( $item['id'], $order_item_ids ) ) {
							continue;
						}
						ob_start();
						include learn_press_get_admin_view( 'meta-boxes/order/order-item.php' );
						$html .= ob_get_clean();
					}
				}


				learn_press_send_json(
					array(
						'result'     => 'success',
						'item_html'  => $html,
						'order_data' => $order_data
					)
				);
			}
			learn_press_send_json(
				array(
					'result' => 'error'
				)
			);
		}

		public static function search_courses() {
			$nonce = learn_press_get_request( 'nonce' );
			if ( ! wp_verify_nonce( $nonce, 'search_item_term' ) ) {
				LP_Debug::exception( __( 'Verify nonce failed', 'learnpress' ) );
			}

			$term    = learn_press_get_request( 'term' );
			$exclude = learn_press_get_request( 'exclude' );

			$posts         = learn_press_get_all_courses(
				array(
					'term'    => $term,
					'exclude' => $exclude
				)
			);
			$found_courses = array();
			if ( ! empty( $posts ) ) {
				foreach ( $posts as $post ) {
					$found_courses[ $post ] = array(
						'title'     => get_the_title( $post ),
						'permalink' => get_the_permalink( $post )
					);
				}
			}

			$found_courses = apply_filters( 'learn_press_json_search_found_courses', $found_courses );

			learn_press_send_json( $found_courses );
		}

		public static function remove_course_section() {
			$id = learn_press_get_request( 'id' );
			if ( $id ) {
				global $wpdb;
				$query = $wpdb->prepare( "
					DELETE FROM {$wpdb->prefix}learnpress_section_items
					WHERE section_id = %d
				", $id );
				$wpdb->query( $query );
				learn_press_reset_auto_increment( 'learnpress_section_items' );
				$query = $wpdb->prepare( "
					DELETE FROM {$wpdb->prefix}learnpress_sections
					WHERE section_id = %d
				", $id );
				$wpdb->query( $query );
				learn_press_reset_auto_increment( 'learnpress_sections' );
			}
			die();
		}

		public static function toggle_lesson_preview() {
			$id = learn_press_get_request( 'lesson_id' );
			if ( get_post_type( $id ) == 'lp_lesson' && wp_verify_nonce( learn_press_get_request( 'nonce' ), 'learn-press-toggle-lesson-preview' ) ) {
				$previewable = learn_press_get_request( 'previewable' );
				if ( is_null( $previewable ) ) {
					$previewable = '0';
				}
				update_post_meta( $id, '_lp_preview', $previewable );
			}
			die();
		}

		public static function add_new_item() {
			$post_type  = learn_press_get_request( 'type' );
			$post_title = learn_press_get_request( 'name' );
			$response   = array();
			if ( $post_type && $post_title ) {
				$args                = compact( 'post_title', 'post_type' );
				$args['post_status'] = 'publish';
				$item_id             = wp_insert_post( $args );
				if ( $item_id ) {
					LP_Lesson_Post_Type::create_default_meta( $item_id );
					$item                        = get_post( $item_id );
					$response['post']            = $item;
					$response['post']->edit_link = get_edit_post_link( $item_id );
				}
			}
			learn_press_send_json( $response );
		}

		public static function quick_add_item() {
			$post_type  = learn_press_get_request( 'type' );
			$post_title = learn_press_get_request( 'name' );
			$response   = array();
			if ( $post_type && $post_title ) {
				$args                = compact( 'post_title', 'post_type' );
				$args['post_status'] = 'publish';
				$item_id             = wp_insert_post( $args );
				if ( $item_id ) {
					$item             = get_post( $item_id );
					$response['post'] = $item;
					$response['html'] = sprintf( '<li class="" data-id="%1$d" data-type="%2$s" data-text="%3$s">
						<label>
							<input type="checkbox" value="%1$d">
							<span class="lp-item-text">%3$s</span>
						</label>
					</li>', $item->ID, $item->post_type, $item->post_title );
				}
			}
			learn_press_send_json( $response );
		}

		public static function update_quiz_question_state() {
			$hidden = learn_press_get_request( 'hidden' );
			$post   = learn_press_get_request( 'quiz_id' );
			update_post_meta( $post, '_admin_hidden_questions', $hidden );
			die();
		}

		public static function update_curriculum_section_state() {
			$hidden = learn_press_get_request( 'hidden' );
			$post   = learn_press_get_request( 'course_id' );
			update_post_meta( $post, '_admin_hidden_sections', $hidden );
			die();
		}

		/**
		 * Create a new page with the title passed via $_REQUEST
		 */
		public static function create_page() {
			$page_name = ! empty( $_REQUEST['page_name'] ) ? $_REQUEST['page_name'] : '';
			$response  = array();
			if ( $page_name ) {
				$args    = array(
					'post_type'   => 'page',
					'post_title'  => $page_name,
					'post_status' => 'publish'
				);
				$page_id = wp_insert_post( $args );

				if ( $page_id ) {
					$response['page'] = get_page( $page_id );
					$html             = learn_press_pages_dropdown( '', '', array( 'echo' => false ) );
					preg_match_all( '!value=\"([0-9]+)\"!', $html, $matches );
					$response['positions'] = $matches[1];
					$response['html']      = '<a href="' . get_edit_post_link( $page_id ) . '" target="_blank">' . __( 'Edit Page', 'learnpress' ) . '</a>&nbsp;';
					$response['html']      .= '<a href="' . get_permalink( $page_id ) . '" target="_blank">' . __( 'View Page', 'learnpress' ) . '</a>';
				} else {
					$response['error'] = __( 'Error! Create page failed. Please try again!', 'learnpress' );
				}
			} else {
				$response['error'] = __( 'Empty page name!', 'learnpress' );
			}
			learn_press_send_json( $response );
			die();
		}

		public static function add_quiz_question() {
			global $post;
			$id       = learn_press_get_request( 'id' );
			$quiz_id  = learn_press_get_request( 'quiz_id' );
			$type     = learn_press_get_request( 'type' );
			$name     = learn_press_get_request( 'name' );
			$user_id  = get_current_user_id();
			$response = array(
				'id' => $id
			);
			$post     = get_post( $quiz_id );
			setup_postdata( $post );
			if ( ! $id ) {
				$args_item = array(
					'post_title'  => $name,
					'post_type'   => LP_QUESTION_CPT,
					'post_status' => 'publish'
				);
				$args_item = apply_filters( 'learnpress_quiz_insert_item_args', $args_item, $quiz_id );
				$id        = wp_insert_post( $args_item );
				if ( $id ) {
					add_post_meta( $id, '_lp_type', $type );
				}
				$response['id'] = $id;
			}
			if ( $id && $quiz_id ) {
				global $wpdb;
				$max_order = $wpdb->get_var( $wpdb->prepare( "SELECT max(question_order) FROM {$wpdb->prefix}learnpress_quiz_questions WHERE quiz_id = %d", $quiz_id ) );
				$wpdb->insert(
					$wpdb->prefix . 'learnpress_quiz_questions',
					array(
						'quiz_id'        => $quiz_id,
						'question_id'    => $id,
						'question_order' => $max_order + 1
					),
					array( '%d', '%d', '%d' )
				);
				ob_start();
				$question = LP_Question_Factory::get_question( $id );
				learn_press_admin_view( 'meta-boxes/quiz/question.php', array( 'question' => $question ) );
				$response['html'] = ob_get_clean();

				// trigger change user memorize question types
				$question_types          = get_user_meta( $user_id, '_learn_press_memorize_question_types', true );
				$question_types          = ! $question_types ? array() : $question_types;
				$type                    = get_post_meta( $id, '_lp_type', true );
				$question_types[ $type ] = ! empty ( $question_types[ $type ] ) ? absint( $question_types[ $type ] ) + 1 : 1;
				update_user_meta( $user_id, '_learn_press_memorize_question_types', $question_types );
				// end trigger change user memorize question types
			}
			learn_press_send_json( $response );
			die();
		}

		public static function convert_question_type() {
			if ( ( $from = learn_press_get_request( 'from' ) ) && ( $to = learn_press_get_request( 'to' ) ) && $question_id = learn_press_get_request( 'question_id' ) ) {
				$data = array();
				parse_str( $_POST['data'], $data );

				do_action( 'learn_press_convert_question_type', $question_id, $from, $to, $data );
				$question = LP_Question_Factory::get_question( $question_id, array( 'type' => $to ) );

				// trigger change user memorize question types
				$user_id                 = get_current_user_id();
				$question_types          = get_user_meta( $user_id, '_learn_press_memorize_question_types', true );
				$question_types[ $from ] = ! empty( $question_types[ $from ] ) && $question_types[ $from ] ? absint( $question_types[ $from ] ) - 1 : 0;
				$question_types[ $to ]   = ! empty( $question_types[ $to ] ) && $question_types[ $to ] ? absint( $question_types[ $to ] ) + 1 : 1;
				update_user_meta( $user_id, '_learn_press_memorize_question_types', $question_types );
				// end trigger change user memorize question types
				if ( 'auto-draft' === $question->post->post_status ) {
					$question->answers = $question->get_default_answers( false );
				}
				learn_press_send_json(
					array(
						'html' => $question->admin_interface( array( 'echo' => false ) ),
						'icon' => $question->get_icon()
					)
				);
			} else {
				//throw new Exception( __( 'Convert question type must be specify the id, source and destination type', 'learnpress' ) );
				throw new Exception( __( 'Something went wrong! Please try again or ask <a href="https://wordpress.org/support/">support forums</a>.', 'learnpress' ) );
			}
			die();
		}

		/*******************************************************************************************************/

		/**
		 * Install sample data or dismiss the notice depending on user's option
		 */
		public static function install_sample_data() {
			$yes            = ! empty( $_REQUEST['yes'] ) ? $_REQUEST['yes'] : '';
			$response       = array( 'result' => 'fail' );
			$retry_button   = sprintf( '<a href="" class="button yes" data-action="yes">%s</a>', __( 'Try again!', 'learnpress' ) );
			$dismiss_button = sprintf( '<a href="" class="button disabled no" data-action="no">%s</a>', __( 'Cancel', 'learnpress' ) );
			$buttons        = sprintf( '<p>%s %s</p>', $retry_button, $dismiss_button );
			if ( 'no' == $yes ) {
				set_transient( 'learn_press_install_sample_data', 'off', 12 * HOUR_IN_SECONDS );
			} else {
				$result = array( 'status' => 'activate' );//learn_press_install_and_active_add_on( 'learnpress-import-export' );
				if ( 'activate' == $result['status'] ) {
					// copy dummy-data.xml to import folder of the add-on
					lpie_mkdir( lpie_import_path() );
					if ( file_exists( lpie_import_path() ) ) {
						$import_source = LP_PLUGIN_PATH . '/dummy-data/dummy-data.xml';
						$file          = 'dummy-data-' . time() . '.xml';
						$copy          = lpie_import_path() . '/' . $file;
						copy( $import_source, $copy );
						if ( file_exists( $copy ) ) {
							$url                 = admin_url( 'admin-ajax.php?page=learn_press_import_export&tab=import-course' );
							$postdata            = array(
								'step'        => 2,
								'action'      => 'learn_press_import',
								'import-file' => 'import/' . $file,
								'nonce'       => wp_create_nonce( 'lpie-import-file' ),
								'x'           => 1
							);
							$response['url']     = $url = $url . '&' . http_build_query( $postdata ) . "\n";
							$response['result']  = 'success';
							$response['message'] = sprintf( '<p>%s <a href="edit.php?post_type=lp_course">%s</a> </p>', __( 'Import sample data successes.', 'learnpress' ), __( 'View courses', 'learnpress' ) );
						}
					}
					if ( $response['result'] == 'fail' ) {
						$response['message'] = sprintf( '<p>%s</p>%s', __( 'Import sample data failed. Please try again!.', 'learnpress' ), $buttons );
					}
				} else {
					$response['result']  = 'fail';
					$response['message'] = sprintf( '<p>%s</p>', __( 'Unknown error when installing/activating Import/Export addon. Please try again!', 'learnpress' ) ) . $buttons;
				}
			}
			learn_press_send_json( $response );
			die();
		}

		/**
		 * Activate a bundle of add-ons, if an add-on is not installed then install it first
		 */
		public static function bundle_activate_add_ons() {
			global $learn_press_add_ons;
			include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); //for plugins_api..
			$response = array( 'addons' => array() );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				$response['error'] = __( 'You do not have sufficient permissions to deactivate plugins for this site.', 'learnpress' );
			} else {

				$add_ons = $learn_press_add_ons['bundle_activate'];

				if ( $add_ons ) {
					foreach ( $add_ons as $slug ) {
						$response['addons'][ $slug ] = learn_press_install_and_active_add_on( $slug );
					}
				}
			}
			learn_press_send_json( $response );
		}

		/**
		 * Activate a bundle of add-ons, if an add-on is not installed then install it first
		 */
		public static function bundle_activate_add_on() {
			$response = array();
			include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); //for plugins_api..
			if ( ! current_user_can( 'activate_plugins' ) ) {
				$response['error'] = __( 'You do not have sufficient permissions to deactivate plugins for this site.', 'learnpress' );
			} else {
				$slug              = ! empty( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : null;
				$response[ $slug ] = learn_press_install_and_active_add_on( $slug );
			}
			learn_press_send_json( $response );
		}

		public static function plugin_install() {
			$plugin_name = ! empty( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
			$response    = learn_press_install_add_on( $plugin_name );
			learn_press_send_json( $response );
			die();
		}

		public static function update_add_on_status() {
			$plugin   = ! empty( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
			$t        = ! empty( $_REQUEST['t'] ) ? $_REQUEST['t'] : '';
			$response = array();
			if ( ! current_user_can( 'activate_plugins' ) ) {
				$response['error'] = __( 'You do not have sufficient permissions to deactivate plugins for this site.', 'learnpress' );
			}
			if ( $plugin && $t ) {
				if ( $t == 'activate' ) {
					activate_plugin( $plugin, false, is_network_admin() );
				} else {
					deactivate_plugins( $plugin, false, is_network_admin() );
				}
				$is_activate        = is_plugin_active( $plugin );
				$response['status'] = $is_activate ? 'activate' : 'deactivate';

			}
			wp_send_json( $response );
			die();
		}

		/**
		 * Output the image to browser with text and params passed via $_GET
		 */
		public static function dummy_image() {
			$text = ! empty( $_REQUEST['text'] ) ? $_REQUEST['text'] : '';
			learn_press_text_image( $text, $_GET );
			die();
		}

		/**
		 * Get edit|view link of a page
		 */
		public static function get_page_permalink() {
			$page_id = ! empty( $_REQUEST['page_id'] ) ? $_REQUEST['page_id'] : '';
			?>
            <a href="<?php echo get_edit_post_link( $page_id ); ?>"
               target="_blank"><?php _e( 'Edit Page', 'learnpress' ); ?></a>
            <a href="<?php echo get_permalink( $page_id ); ?>"
               target="_blank"><?php _e( 'View Page', 'learnpress' ); ?></a>
			<?php
			die();
		}


		/**
		 *
		 */
		public function custom_stats() {
			$from      = ! empty( $_REQUEST['from'] ) ? $_REQUEST['from'] : 0;
			$to        = ! empty( $_REQUEST['to'] ) ? $_REQUEST['to'] : 0;
			$date_diff = strtotime( $to ) - strtotime( $from );
			if ( $date_diff <= 0 || $from == 0 || $to == 0 ) {
				die();
			}
			learn_press_process_chart( learn_press_get_chart_students( $to, 'days', floor( $date_diff / ( 60 * 60 * 24 ) ) + 1 ) );
			die();
		}

		/**
		 * Quick add lesson with only title
		 */
		public static function quick_add_lesson() {

			$lesson_title = $_POST['lesson_title'];

			$new_lesson = array(
				'post_title'  => wp_strip_all_tags( $lesson_title ),
				'post_type'   => LP_LESSON_CPT,
				'post_status' => 'publish'
			);

			wp_insert_post( $new_lesson );

			$args      = array(
				'numberposts' => 1,
				'post_type'   => LP_LESSON_CPT,
				'post_status' => 'publish'
			);
			$lesson    = wp_get_recent_posts( $args );
			$lesson_id = $lesson[0]['ID'];
			$data      = array(
				'id'    => $lesson_id,
				'title' => $lesson_title
			);
			wp_send_json( $data );
			die;
		}

		/**
		 * Add a new quiz with the title only
		 */
		public static function quick_add_quiz() {
			$quiz_title = $_POST['quiz_title'];

			$new_quiz = array(
				'post_title'  => wp_strip_all_tags( $quiz_title ),
				'post_type'   => LP_QUIZ_CPT,
				'post_status' => 'publish'
			);

			wp_insert_post( $new_quiz );

			$args    = array(
				'numberposts' => 1,
				'post_type'   => LP_QUIZ_CPT,
				'post_status' => 'publish'
			);
			$quiz    = wp_get_recent_posts( $args );
			$quiz_id = $quiz[0]['ID'];
			$data    = array(
				'id'    => $quiz_id,
				'title' => $quiz_title
			);
			wp_send_json( $data );
			die;
		}

		public static function be_teacher() {
			$user_id    = get_current_user_id();
			$be_teacher = new WP_User( $user_id );
			$be_teacher->set_role( LP_TEACHER_ROLE );
			die;
		}

		public static function ignore_setting_up() {
			update_option( '_lpr_ignore_setting_up', 1, true );
			die;
		}

		public static function duplicate_course() {
			if ( empty( $_POST['course_id'] ) || empty( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'lp-duplicate-course' ) ) {
				return;
			}
			global $wpdb;
			$course_id = absint( $_POST['course_id'] );
			$force     = ! empty( $_POST['content'] ) && $_POST['content'] ? true : false;

			$results       = array(
				'redirect' => admin_url( 'edit.php?post_type=' . LP_COURSE_CPT )
			);
			$new_course_id = learn_press_duplicate_course( $course_id, $force );
			if ( is_wp_error( $course_id ) ) {
				LP_Admin_Notice::add_redirect( $course_id->get_error_message(), 'error' );
			} else {
				LP_Admin_Notice::add_redirect( sprintf( '<strong>%s</strong> %s', get_the_title( $course_id ), __( ' course has duplicated', 'learnpress' ) ), 'updated' );
				$results['redirect'] = admin_url( 'post.php?post=' . $new_course_id . '&action=edit' );
			}

			wp_send_json( $results );
			die();
		}

		public static function duplicate_question() {
			if ( empty( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'duplicate-question' ) ) {
				return;
			}
			global $wpdb;
			$question_id = learn_press_get_request( 'question-id' );
			$quiz_id     = learn_press_get_request( 'quiz-id' );
			$user_id     = learn_press_get_current_user_id();

			$new_question_id = learn_press_duplicate_question( $question_id, $quiz_id );
			if ( ! is_wp_error( $new_question_id ) ) {
				ob_start();
				$question = LP_Question_Factory::get_question( $new_question_id );
				$post     = get_post( $quiz_id );
				setup_postdata( $post );
				_learn_press_setup_question( $new_question_id );
				learn_press_admin_view( 'meta-boxes/quiz/question.php', array( 'question' => $question ) );
				$response['html'] = ob_get_clean();

				// trigger change user memorize question types
				$question_types          = get_user_meta( $user_id, '_learn_press_memorize_question_types', true );
				$question_types          = ! $question_types ? array() : $question_types;
				$type                    = get_post_meta( $new_question_id, '_lp_type', true );
				$question_types[ $type ] = ! empty ( $question_types[ $type ] ) ? absint( $question_types[ $type ] ) + 1 : 1;
				update_user_meta( $user_id, '_learn_press_memorize_question_types', $question_types );
				// end trigger change user memorize question types
				learn_press_send_json( $response );


				die();
			}
		}

		public static function remove_notice_popup() {

			if ( isset( $_POST['action'] ) && $_POST['action'] === 'learnpress_remove_notice_popup'
			     && isset( $_POST['slug'] ) && ! empty( $_POST['slug'] )
			     && isset( $_POST['user'] ) && ! empty( $_POST['user'] )
			) {

				$slug = 'learnpress_notice_' . $_POST['slug'] . '_' . $_POST['user'];

				set_transient( $slug, true, 30 * DAY_IN_SECONDS );
			}

			wp_die();

		}

		public static function update_order_status() {
			global $wpdb;
			$order_id = learn_press_get_request( 'order_id' );
			$value    = learn_press_get_request( 'value' );

			$order = array(
				'ID'          => $order_id,
				'post_status' => $value,
			);

			wp_update_post( $order ) ? $response['success'] = true : $response['success'] = false;

			learn_press_send_json( $response );

			die();
		}

	}
}

add_action( 'init', array( 'LP_Admin_Ajax', 'init' ) );
