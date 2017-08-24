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
			$ajaxEvents = array(
				'create_page'                     => false,
				'add_quiz_question'               => false,
				'convert_question_type'           => false,
				'update_quiz_question_state'      => false,
				'update_editor_hidden'            => false,
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
			do_action( 'learn_press_admin_ajax_load', __CLASS__ );
		}

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

			$term = stripslashes( $_GET['term'] );

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

			$term = stripslashes( $_GET['term'] );
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
						FROM {$wpdb->prefix}learnpress_quiz_questions qq
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

		public static function modal_search_items() {
			global $wpdb;

			$user                   = learn_press_get_current_user();
			$term                   = (string) ( stripslashes( learn_press_get_request( 'term' ) ) );
			$type                   = (string) ( stripslashes( learn_press_get_request( 'type' ) ) );
			$context                = (string) ( stripslashes( learn_press_get_request( 'context' ) ) );
			$context_id             = (string) ( stripslashes( learn_press_get_request( 'context_id' ) ) );
			$current_items_in_order = learn_press_get_request( 'current_items' );
			$current_items          = array();

			foreach ( $current_items_in_order as $item ) {
				$sql = "SELECT meta_value
                        FROM {$wpdb->prefix}learnpress_order_itemmeta 
                        WHERE meta_key = '_course_id' 
                        AND learnpress_order_item_id = $item";
				$id  = $wpdb->get_results( $sql, OBJECT );
				array_push( $current_items, $id[0]->meta_value );
			}

			$exclude = array();

			if ( ! empty( $_GET['exclude'] ) ) {
				$exclude = array_map( 'intval', $_GET['exclude'] );
			}

			$author_id = get_post_field( 'post_author', $context_id );

			$exclude = array_unique( (array) apply_filters( 'learn_press_modal_search_items_exclude', $exclude, $type, $context, $context_id ) );
			$exclude = array_map( 'intval', $exclude );

			$args = array(
				'post_type'      => array( $type ),
				'posts_per_page' => - 1,
				'post_status'    => 'publish',
				'order'          => 'ASC',
				'orderby'        => 'parent title',
				'author'         => $author_id,
				'exclude'        => $exclude
			);

			if ( $term ) {
				$args['s'] = $term;
			}
			
			// allow super admin can search course of other user 
			if( is_super_admin() && $context == 'course-items' && $type=='lp_course' ) {
			    unset( $args['author'] );
			}
			
			$args        = apply_filters( 'learn_press_filter_admin_ajax_modal_search_items_args', $args, $context, $context_id );
			$posts       = get_posts( $args );
			$found_items = array();

			if ( ! empty( $posts ) ) {
				if ( $current_items_in_order ) {
					foreach ( $posts as $post ) {
						if ( in_array( $post->ID, $current_items ) ) {
							continue;
						}
						$found_items[ $post->ID ]             = $post;
						$found_items[ $post->ID ]->post_title = ! empty( $post->post_title ) ? $post->post_title : sprintf( '(%s)', __( 'Untitled', 'learnpress' ) );
					}
				} else {
					foreach ( $posts as $post ) {
						$found_items[ $post->ID ]             = $post;
						$found_items[ $post->ID ]->post_title = ! empty( $post->post_title ) ? $post->post_title : sprintf( '(%s)', __( 'Untitled', 'learnpress' ) );
					}
				}
			}


			ob_start();
			if ( $found_items ) {
				foreach ( $found_items as $id => $item ) {
					printf( '
                            <li class="" data-id="%1$d" data-type="%3$s" data-text="%2$s">
						<label>
							<input type="checkbox" value="%1$d">
							<span class="lp-item-text">%2$s</span>
						</label>
					</li>
					', $id, esc_attr( $item->post_title ), $item->post_type );
				}
			} else {
				echo '<li>' . apply_filters( 'learn_press_modal_search_items_not_found', __( 'No item found', 'learnpress' ), $type ) . '</li>';
			}

			$item_object    = $type ? get_post_type_object( $type ) : '';
			$post_type      = $context_id ? get_post_type_object( get_post_type( $context_id ) ) : '';
			$response       = array(
				'html'    => ob_get_clean(),
				'data'    => $found_items,
				'notices' => '<div class="learnpress-search-notices notice notice-warning" data-post-type="' . esc_attr( $item_object->name ) . '" data-user="' . esc_attr( $user->id ) . '">' . sprintf( '<p>' . __( 'A ', 'learnpress' ) . '<span style="text-transform: lowercase;">%s</span>' . __( ' is just used for only one ', 'learnpress' ) . '<span style="text-transform: lowercase;">%s</span></p>', $item_object->labels->singular_name, $post_type->labels->singular_name ) . '<a class="learnpress-dismiss-notice"></a></div>'
			);
			$dismiss_notice = 'learnpress_notice_' . $item_object->name . '_' . $user->id;
			$dismiss_notice = get_transient( $dismiss_notice );
			if ( $dismiss_notice || $item_object->name === 'lp_course' ) { // Check lp_course to hidden notice in order post
				unset( $response['notices'] );
			}

			learn_press_send_json( $response );
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

			if ( ! empty( $_GET['exclude'] ) ) {
				$exclude = array_map( 'intval', $_GET['exclude'] );
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
				$args['author'] = $user->id;
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
		public static function remove_order_item() {
			// ensure that user has permission
			if ( ! current_user_can( 'edit_lp_orders' ) ) {
				die( __( 'Permission denied', 'learnpress' ) );
			}

			// verify nonce
			$nonce = learn_press_get_request( 'remove_nonce' );
			if ( ! wp_verify_nonce( $nonce, 'remove_order_item' ) ) {
				die( __( 'Check nonce failed', 'learnpress' ) );
			}

			// validate order
			$order_id = learn_press_get_request( 'order_id' );
			if ( ! is_numeric( $order_id ) || get_post_type( $order_id ) != 'lp_order' ) {
				die( __( 'Order invalid', 'learnpress' ) );
			}

			// validate item
			$item_id = learn_press_get_request( 'item_id' );
			$post    = get_post( learn_press_get_order_item_meta( $item_id, '_course_id' ) );
			if ( ! $post || ( 'lp_course' !== $post->post_type ) ) {
				die( __( 'Course invalid', 'learnpress' ) );
			}

			learn_press_remove_order_item( $item_id );

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
		public static function add_item_to_order() {

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
			$item_ids   = learn_press_get_request( 'item_id' );
			$item_html  = '';
			$order_data = array();
//			$order  = learn_press_get_order( $order_id );

//			echo '<pre>'.print_r($item_ids, true).'</pre>';
//			exit(''.__LINE__);
			foreach ( $item_ids as $item_id ):
				$post = get_post( $item_id );
				if ( ! $post || ( 'lp_course' !== $post->post_type ) ) {
					continue;
//					die( __( 'Course invalid', 'learnpress' ) );
				}
				$course = learn_press_get_course( $post->ID );
				$item   = array(
					'course_id' => $course->id,
					'name'      => $course->get_title(),
					'quantity'  => 1,
					'subtotal'  => $course->get_price(),
					'total'     => $course->get_price()
				);

				// Add item
				$item_id = learn_press_add_order_item( $order_id, array(
					'order_item_name' => $item['name']
				) );

				$item['id'] = $item_id;

				// Add item meta
				if ( $item_id ) {
					$item = apply_filters( 'learn_press_ajax_order_item', $item );

					learn_press_add_order_item_meta( $item_id, '_course_id', $item['course_id'] );
					learn_press_add_order_item_meta( $item_id, '_quantity', $item['quantity'] );
					learn_press_add_order_item_meta( $item_id, '_subtotal', $item['subtotal'] );
					learn_press_add_order_item_meta( $item_id, '_total', $item['total'] );

					do_action( 'learn_press_ajax_add_order_item_meta', $item );
				}

				$order_data                  = learn_press_update_order_items( $order_id );
				$currency_symbol             = learn_press_get_currency_symbol( $order_data['currency'] );
				$order_data['subtotal_html'] = learn_press_format_price( $order_data['subtotal'], $currency_symbol );
				$order_data['total_html']    = learn_press_format_price( $order_data['total'], $currency_symbol );

				ob_start();
				include learn_press_get_admin_view( 'meta-boxes/order/order-item.php' );
				$item_html .= ob_get_clean();
			endforeach;


			learn_press_send_json(
				array(
					'result'     => 'success',
					'item_html'  => $item_html,
					'order_data' => $order_data
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

		public static function update_editor_hidden() {
			if ( $id = learn_press_get_request( 'course_id' ) ) {
				if ( learn_press_get_request( 'is_hidden' ) ) {
					update_post_meta( $id, '_lp_editor_hidden', 'yes' );
				} else {
					delete_post_meta( $id, '_lp_editor_hidden' );
				}
			}
			learn_press_send_json( $_POST );
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
LP_Admin_Ajax::init();
