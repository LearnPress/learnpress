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
defined( 'ABSPATH' ) || exit();

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
				'create_page'             => false,
				'plugin_action'           => false,
				'modal_search_items'      => false,
				'dismiss_notice'          => false,
				'search_users'            => false,
				'load_chart'              => false,
				'search_course_category'  => false,
				/////////////
				'be_teacher'              => false,
				'custom_stats'            => false,
				'ignore_setting_up'       => false,
				'get_page_permalink'      => false,
				'dummy_image'             => false,
				'update_add_on_status'    => false,
				'plugin_install'          => false,
				'bundle_activate_add_ons' => false,
				'install_sample_data'     => false,

				// Remove Notice
				'remove_notice_popup'     => false,
				// Update order status
				'update_order_status'     => false,
			);
			foreach ( $ajaxEvents as $ajaxEvent => $nopriv ) {
				add_action( 'wp_ajax_learnpress_' . $ajaxEvent, array( __CLASS__, $ajaxEvent ) );

				// enable for non-logged in users
				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_learnpress_' . $ajaxEvent, array( __CLASS__, $ajaxEvent ) );
				}
			}

			do_action( 'learn-press/ajax/admin-load', __CLASS__ );

			$ajax_events = array(
				'search_items' => 'modal_search_items',
				'update_payment_order',
				'toggle_lesson_preview',

				// admin editor
				'admin_course_editor',
				'admin_quiz_editor',
				'admin_question_editor',
				// duplicator
				'duplicator',

				'add_item_to_order',
				'remove_order_item',

				'modal_search_items',
				'modal_search_users',
				'add_items_to_order',
				'remove_items_from_order',
				'update_email_status'
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
		 * Handle ajax admin course editor.
		 *
		 * @since 3.0.0
		 */
		public static function admin_course_editor() {

			check_ajax_referer( 'learnpress_update_curriculum', 'nonce' );

			$args = wp_parse_args( $_REQUEST, array( 'id' => false, 'type' => '' ) );

			$course_id = $args['id'];
			$course    = LP_Course::get_course( $course_id );

			if ( ! $course ) {
				wp_send_json_error();
			}

			// course curd
			$course_curd = new LP_Course_CURD();
			// section curd
			$section_curd = new LP_Section_CURD( $course_id );

			$result = $args['type'];

			switch ( $args['type'] ) {

				case 'heartbeat':

					$result = true;
					break;

				case 'draft-course':

					$new_course = ! empty( $args['course'] ) ? $args['course'] : false;
					$new_course = json_decode( wp_unslash( $new_course ), true );

					if ( ! $new_course ) {
						break;
					}

					$title   = $new_course['title'] ? $new_course['title'] : __( 'New Course', 'learnpress' );
					$content = $new_course['content'] ? $new_course['content'] : '';

					wp_update_post( array(
						'ID'           => $course_id,
						'post_title'   => $title,
						'post_content' => $content,
						'post_status'  => 'draft'
					) );

					break;

				case 'hidden-sections':

					// get hidden sections id
					$hidden = ! empty( $args['hidden'] ) ? $args['hidden'] : false;
					// update course post meta
					update_post_meta( $course_id, '_admin_hidden_sections', $hidden );

					break;

				case 'sort-sections':

					$order = ! empty( $args['order'] ) ? $args['order'] : false;
					$order = json_decode( wp_unslash( $order ), true );

					if ( ! $order ) {
						break;
					}

					$result = $section_curd->sort_sections( $order );

					// last section
					$last_section_id = end( $order );
					// update final quiz
					$section_curd->update_final_quiz( $last_section_id );

					break;

				case 'update-section':

					$section = ! empty( $args['section'] ) ? $args['section'] : false;
					$section = json_decode( wp_unslash( $section ), true );

					if ( ! $section ) {
						break;
					}

					$update = array(
						'section_id'          => $section['id'],
						'section_name'        => $section['title'],
						'section_description' => $section['description'],
						'section_order'       => $section['order'],
						'section_course_id'   => $section['course_id'],
					);

					$result = $section_curd->update( $update );

					break;

				case 'remove-section':

					$section_id = ! empty( $args['section_id'] ) ? $args['section_id'] : false;

					if ( ! $section_id ) {
						break;
					}

					$section_curd->delete( $section_id );

					break;

				case 'new-section':

					$section_name = ! empty( $args['section_name'] ) ? $args['section_name'] : false;

					$args = array(
						'section_course_id'   => $course_id,
						'section_description' => '',
						'section_name'        => $section_name,
						'items'               => array(),
					);

					// create section
					$section = $section_curd->create( $args );

					$result = array(
						'id'          => $section['section_id'],
						'items'       => $section['items'],
						'title'       => $section['section_name'],
						'description' => $section['section_description'],
						'course_id'   => $section['section_course_id'],
						'order'       => $section['section_order'],
					);

					break;

				case 'update-section-item':

					$section_id = ! empty( $args['section_id'] ) ? $args['section_id'] : false;
					$item       = ! empty( $args['item'] ) ? $args['item'] : false;
					$item       = json_decode( wp_unslash( $item ), true );

					if ( ! ( $section_id && $item ) ) {
						break;
					}

					// update lesson, quiz title
					$result = $section_curd->update_item( $item );

					break;

				case 'remove-section-item':

					$section_id = ! empty( $args['section_id'] ) ? $args['section_id'] : false;
					$item_id    = ! empty( $args['item_id'] ) ? $args['item_id'] : false;

					if ( ! ( $section_id && $item_id ) ) {
						break;
					}

					// remove item from course
					$course_curd->remove_item( $item_id );

					break;

				case 'delete-section-item':

					$section_id = ! empty( $args['section_id'] ) ? $args['section_id'] : false;
					$item_id    = ! empty( $args['item_id'] ) ? $args['item_id'] : false;

					if ( ! ( $section_id && $item_id ) ) {
						break;
					}

					$result = wp_delete_post( $item_id );

					break;

				case 'new-section-item':

					$section_id = ! empty( $args['section_id'] ) ? $args['section_id'] : false;
					$item       = ! empty( $args['item'] ) ? $args['item'] : false;
					$item       = json_decode( wp_unslash( $item ), true );

					if ( ! ( $section_id && $item ) ) {
						break;
					}

					// create new lesson, quiz and add to course
					$result = $section_curd->new_item( $section_id, $item );

					break;

				case 'update-section-items':

					$section_id   = ! empty( $args['section_id'] ) ? $args['section_id'] : false;
					$last_section = ! empty( $args['last_section'] ) ? $args['last_section'] : false;
					$items        = ! empty( $args['items'] ) ? $args['items'] : false;
					$items        = json_decode( wp_unslash( $items ), true );

					if ( ! ( $section_id && $items ) ) {
						break;
					}

					$result = $section_curd->update_section_items( $section_id, $items );

					if ( $last_section ) {
						$section_curd->update_final_quiz( $section_id );
					}

					break;

				case 'search-items':

					$query   = isset( $args['query'] ) ? $args['query'] : '';
					$type    = isset( $args['item_type'] ) ? $args['item_type'] : '';
					$page    = ! empty( $args['page'] ) ? $args['page'] : 1;
					$exclude = ! empty( $args['exclude'] ) ? $args['exclude'] : '';

					if ( $exclude ) {
						$exclude = json_decode( $exclude, true );
					}

					$ids_exclude = array();

					if ( is_array( $exclude ) ) {
						foreach ( $exclude as $item ) {
							$ids_exclude[] = $item['id'];
						}
					}

					$search = new LP_Modal_Search_Items( array(
						'type'       => $type,
						'context'    => 'course',
						'context_id' => $course_id,
						'term'       => $query,
						'limit'      => apply_filters( 'learn-press/course-editor/choose-items-limit', 10 ),
						'paged'      => $page,
						'exclude'    => $ids_exclude,
					) );

					$id_items = $search->get_items();

					$items = array();
					foreach ( $id_items as $id ) {
						$post = get_post( $id );

						$items[] = array(
							'id'    => $post->ID,
							'title' => $post->post_title,
							'type'  => $post->post_type,
						);
					}

					$result = array(
						'items'      => $items,
						'pagination' => $search->get_pagination( false )
					);

					break;

				case 'add-items-to-section':

					$section_id = ! empty( $args['section_id'] ) ? $args['section_id'] : false;
					$items      = ! empty( $args['items'] ) ? $args['items'] : false;
					$items      = json_decode( wp_unslash( $items ), true );

					if ( ! $items || ! $section_id ) {
						break;
					}

					$result = $section_curd->add_items_section( $section_id, $items );

					break;

				default:
					break;

			}

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}

			wp_send_json_success( $result );
		}

		/**
		 * Draft question.
		 *
		 * @since 3.0.0
		 *
		 * @param       $question_id
		 * @param array $args
		 *
		 * @return bool|int|LP_Question
		 */
		private static function draft_question( $question_id, $args = array() ) {

			if ( get_post_status( $question_id ) != 'auto-draft' ) {
				return false;
			}

			$curd = new LP_Question_CURD();

			$args = array(
				'id'      => $question_id,
				'title'   => $args['title'] ? $args['title'] : __( 'New question', 'learnpress' ),
				'content' => $args['content'],
				'status'  => 'draft'
			);

			$question = $curd->create( $args );

			if ( ! $question ) {
				return false;

			}

			return $question;
		}

		/**
		 * Handle ajax admin question editor.
		 *
		 * @since 3.0.0
		 */
		public static function admin_question_editor() {

			check_ajax_referer( 'learnpress_admin_question_editor', 'nonce' );

			$args = wp_parse_args( $_REQUEST, array( 'id' => false, 'type' => '' ) );

			// question id
			$question_id = $args['id'];
			$question    = LP_Question::get_question( $question_id );

			if ( ! $question ) {
				wp_send_json_error();
			}

			$curd             = new LP_Question_CURD();
			$result           = array();
			$result['status'] = false;

			switch ( $args['type'] ) {

				case 'change-question-type':

					$type = ! empty( $args['question_type'] ) ? $args['question_type'] : false;

					if ( ! $type ) {
						break;
					}

					// draft question args
					$args = ! empty( $args['draft_question'] ) ? $args['draft_question'] : '';
					$args = (array) ( json_decode( wp_unslash( $args ), '' ) );

					$draft = self::draft_question( $question_id, $args );

					// check if draft question false or question exist
					if ( $draft ) {
						$question = $draft;
					}

					// change question type
					$curd->change_question_type( $question, $type );

					$result = LP_Admin_Ajax::get_question_data_to_question_editor( $question_id );

					break;

				case 'sort-answer' :
					// answers order
					$order = ! empty( $args['order'] ) ? $args['order'] : false;

					if ( ! $order ) {
						break;
					}

					// sort answers
					$question = $curd->sort_answers( $question_id, $order );

					$result = array_values( $question->get_data( 'answer_options' ) );

					break;

				case 'update-answer-title':

					// answers
					$answer = ! empty( $args['answer'] ) ? $args['answer'] : false;
					$answer = json_decode( wp_unslash( $answer ), true );

					if ( ! $answer ) {
						break;
					}

					// update answer title
					$result = $curd->update_answer_title( $question_id, $answer );

					break;

				case 'change-correct':

					// correct answer
					$correct = ! empty( $args['correct'] ) ? $args['correct'] : false;
					$correct = json_decode( wp_unslash( $correct ), true );

					if ( ! $correct ) {
						break;
					}


					// update correct answer
					$question = $curd->change_correct_answer( $question, $correct );

					$result = $question->get_data( 'answer_options' );

					break;

				case 'delete-answer' :
					// answer id
					$answer_id = ! empty( $args['answer_id'] ) ? $args['answer_id'] : false;

					if ( ! $answer_id ) {
						break;
					}

					// delete answer
					$curd->delete_answer( $question_id, $answer_id );

					$result = $question->get_data( 'answer_options' );

					break;

				case 'new-answer' :

					// new answer
					$answer = LP_Question::get_default_answer();
					// add new
					$curd->new_answer( $question_id, $answer );

					$result = $question->get_data( 'answer_options' );

					break;

				default:
					break;
			}

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}

			wp_send_json_success( $result );
		}

		/**
		 * Handle ajax admin quiz editor.
		 *
		 * @since 3.0.0
		 */
		public static function admin_quiz_editor() {

			check_ajax_referer( 'learnpress_admin_quiz_editor', 'nonce' );

			$args = wp_parse_args( $_REQUEST, array( 'id' => false, 'type' => '' ) );

			// get quiz
			$quiz_id = $args['id'];
			$quiz    = learn_press_get_quiz( $quiz_id );

			if ( ! $quiz ) {
				wp_send_json_error();
			}

			$quiz_curd     = new LP_Quiz_CURD();
			$question_curd = new LP_Question_CURD();

			$result           = array();
			$result['status'] = false;

			switch ( $args['type'] ) {

				case 'heartbeat' :
					$result = true;
					break;

				case 'hidden-questions':

					$hidden = ! empty( $args['hidden'] ) ? $args['hidden'] : false;

					update_post_meta( $quiz_id, '_lp_hidden_questions', $hidden );

					break;

				case 'new-question':

					// new question
					$question = ! empty( $args['question'] ) ? $args['question'] : false;
					$question = json_decode( wp_unslash( $question ), true );

					if ( ! $question ) {
						break;
					}

					// draft quiz
					if ( get_post_status( $quiz_id ) == 'auto-draft' ) {

						$draft_quiz = ! empty( $args['draft_quiz'] ) ? $args['draft_quiz'] : '';
						$draft_quiz = (array) ( json_decode( wp_unslash( $draft_quiz ), '' ) );

						$quiz_args = array(
							'id'      => $quiz_id,
							'title'   => $draft_quiz['title'] ? $draft_quiz['title'] : __( 'New question', 'learnpress' ),
							'content' => $draft_quiz['content'],
							'status'  => 'draft'
						);

						$quiz_id = $quiz_curd->create( $quiz_args );
					}

					if ( ! $quiz_id ) {
						$result = new WP_Error( __( 'Create quiz fail.', 'learnpress' ) );
						break;
					}

					$args = array(
						'quiz_id' => $quiz_id,
						'title'   => $question['title'],
						'type'    => $question['type'],
					);

					$new_question = $question_curd->create( $args );

					if ( ! is_wp_error( $new_question ) ) {
						// get new question data
						$result = LP_Admin_Ajax::get_question_data_to_quiz_editor( $new_question, true );
					}

					// code
					break;

				case 'sort-questions':

					$order = ! empty( $args['order'] ) ? $args['order'] : false;
					$order = json_decode( wp_unslash( $order ), true );

					if ( ! $order ) {
						break;
					}

					$result = $quiz_curd->sort_questions( $order );

					break;

				case 'update-question-title':

					$question = ! empty( $args['question'] ) ? $args['question'] : false;
					$question = json_decode( wp_unslash( $question ), true );

					if ( ! $question ) {
						break;
					}

					wp_update_post( array( 'ID' => $question['id'], 'post_title' => $question['title'] ) );

					$result['status'] = true;

					break;

				case 'change-question-type':

					$question_id = ! empty( $args['question_id'] ) ? $args['question_id'] : false;
					$type        = ! empty( $args['question_type'] ) ? $args['question_type'] : false;

					if ( ! ( $question_id || $type ) ) {
						break;
					}

					$question = LP_Question::get_question( $question_id );

					// change question type
					$question = $question_curd->change_question_type( $question, $type );

					$result = LP_Admin_Ajax::get_question_data_to_quiz_editor( $question, true );

					break;

				case 'clone-question':

					$question = ! empty( $args['question'] ) ? $args['question'] : false;
					$question = json_decode( wp_unslash( $question ), true );

					if ( ! $question ) {
						break;
					}

					// duplicate question
					$new_question_id = $question_curd->duplicate( $question['id'], array( 'post_status' => 'publish' ) );

					if ( ! is_wp_error( $new_question_id ) ) {
						// add question to quiz
						$quiz_curd->add_question( $quiz_id, $new_question_id );

						$result = LP_Admin_Ajax::get_question_data_to_quiz_editor( $new_question_id );
					}

					break;

				case 'remove-question':

					$question_id = ! empty( $args['question_id'] ) ? $args['question_id'] : false;

					if ( ! $question_id ) {
						break;
					}

					$result = $quiz_curd->remove_questions( $quiz_id, $question_id );

					break;

				case 'delete-question':

					$question_id = ! empty( $args['question_id'] ) ? $args['question_id'] : false;

					if ( ! $question_id ) {
						break;
					}

					$result = wp_delete_post( $question_id );

					break;

				case 'sort-question-answers':

					$question_id = ! empty( $args['question_id'] ) ? $args['question_id'] : false;

					$order = ! empty( $args['order'] ) ? $args['order'] : false;
					$order = json_decode( wp_unslash( $order ), true );


					if ( ! ( $question_id && $order ) ) {
						break;
					}

					// sort answer
					$result = $question_curd->sort_answers( $question_id, $order );

					break;

				case 'update-question-answer-title':

					// question id
					$question_id = ! empty( $args['question_id'] ) ? $args['question_id'] : false;

					// answers
					$answer = ! empty( $args['answer'] ) ? $args['answer'] : false;
					$answer = json_decode( wp_unslash( $answer ), true );

					if ( ! ( $question_id && $answer ) ) {
						break;
					}

					// update answer title
					$result = $question_curd->update_answer_title( $question_id, $answer );

					break;

				case 'change-question-correct-answer':

					$question_id = ! empty( $args['question_id'] ) ? $args['question_id'] : false;

					// correct answer
					$correct = ! empty( $args['correct'] ) ? $args['correct'] : false;
					$correct = json_decode( wp_unslash( $correct ), true );

					if ( ! ( $question_id && $correct ) ) {
						break;
					}

					$question = LP_Question::get_question( $question_id );
					// update correct answer, get new question
					$question = $question_curd->change_correct_answer( $question, $correct );

					$result = LP_Admin_Ajax::get_question_data_to_quiz_editor( $question, true );

					break;

				case 'delete-question-answer':

					$question_id = isset( $_POST['question_id'] ) ? $_POST['question_id'] : false;
					$answer_id   = isset( $_POST['answer_id'] ) ? intval( $_POST['answer_id'] ) : false;

					if ( ! ( $question_id && $answer_id ) ) {
						break;
					}

					$result = $question_curd->delete_answer( $question_id, $answer_id );

					break;

				case 'new-question-answer':
					$question_id = ! empty( $args['question_id'] ) ? $args['question_id'] : false;

					if ( ! $question_id ) {
						break;
					}

					// new answer
					$answer = LP_Question::get_default_answer();
					// add new
					$new_answer_id = $question_curd->new_answer( $question_id, $answer );

					$question = LP_Question::get_question( $question_id );

					if ( $new_answer_id ) {
						$result = array_merge( $answer, array(
							'question_answer_id' => $new_answer_id,
							'question_id'        => $question_id,
							'answer_order'       => count( $question->get_data( 'answer_options' ) )
						) );
					}

					break;

				case 'update-question-content':

					$question = ! empty( $args['question'] ) ? $args['question'] : false;
					$question = json_decode( wp_unslash( $question ), true );

					if ( ! $question ) {
						break;
					}

					wp_update_post( array(
						'ID'           => $question['id'],
						'post_content' => $question['settings']['content']
					) );

					$result['status'] = true;

					break;

				case 'update-question-meta':

					$question = ! empty( $args['question'] ) ? $args['question'] : false;
					$question = json_decode( wp_unslash( $question ), true );

					$meta_key = ! empty( $args['meta_key'] ) ? $args['meta_key'] : false;

					if ( ! ( $question && $meta_key ) ) {
						break;
					}

					update_post_meta( $question['id'], '_lp_' . $meta_key, $question['settings'][ $meta_key ] );

					$result['status'] = true;

					break;

				case 'search-items':

					$query   = ! empty( $args['query'] ) ? $args['query'] : '';
					$page    = ! empty( $args['page'] ) ? intval( $args['page'] ) : 1;
					$exclude = ! empty( $args['exclude'] ) ? intval( $args['exclude'] ) : '';

					if ( $exclude ) {
						$exclude = json_decode( $exclude, true );
					}

					$ids_exclude = array();
					if ( is_array( $exclude ) ) {
						foreach ( $exclude as $item ) {
							$ids_exclude[] = $item['id'];
						}
					}

					$search = new LP_Modal_Search_Items( array(
						'type'       => 'lp_question',
						'context'    => 'quiz',
						'context_id' => $quiz_id,
						'term'       => $query,
						'limit'      => apply_filters( 'learn-press/quiz-editor/choose-items-limit', 10 ),
						'paged'      => $page,
						'exclude'    => $ids_exclude
					) );

					$ids_item = $search->get_items();

					$items = array();
					foreach ( $ids_item as $id ) {
						$post = get_post( $id );

						$items[] = array(
							'id'    => $post->ID,
							'title' => $post->post_title,
							'type'  => $post->post_type
						);
					}

					$result = array(
						'items'      => $items,
						'pagination' => $search->get_pagination( false )
					);

					break;

				case 'add-questions-to-quiz':

					// added questions
					$questions = isset( $_POST['items'] ) ? $_POST['items'] : false;
					$questions = json_decode( wp_unslash( $questions ), true );

					if ( ! $questions ) {
						break;
					}

					// draft quiz
					if ( get_post_status( $quiz_id ) == 'auto-draft' ) {

						$draft_quiz = ! empty( $args['draft_quiz'] ) ? $args['draft_quiz'] : '';
						$draft_quiz = (array) ( json_decode( wp_unslash( $draft_quiz ), '' ) );

						$quiz_args = array(
							'id'      => $quiz_id,
							'title'   => $draft_quiz['title'],
							'content' => $draft_quiz['content'],
							'status'  => 'draft'
						);

						$quiz_id = $quiz_curd->create( $quiz_args );
					}

					if ( ! $quiz_id ) {
						$result = new WP_Error( __( 'Create quiz fail.', 'learnpress' ) );
						break;
					}

					if ( $questions ) {
						foreach ( $questions as $key => $question ) {
							$quiz_curd->add_question( $quiz_id, $question['id'] );
						}
						$result = $quiz->quiz_editor_get_questions();
					}

					break;

				default:
					break;
			}

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}

			wp_send_json_success( $result );
		}

		/**
		 * Duplicate course, lesson, quiz, question.
		 *
		 * @since 3.0.0
		 */
		public static function duplicator() {
			$args = wp_parse_args( $_REQUEST, array( 'id' => false ) );

			// get post type
			$post_type = get_post_type( $args['id'] );

			if ( ! $args['id'] ) {
				LP_Admin_Notice::add_redirect( new WP_Error( __( '<p>Op! ID not found</p>', 'learnpress' ) ), 'error' );
			} else {

				$new_item_id = '';

				switch ( $post_type ) {
					case LP_COURSE_CPT:
						$curd        = new LP_Course_CURD();
						$new_item_id = $curd->duplicate( $args['id'], array( 'post_status' => 'publish' ) );
						break;
					case LP_LESSON_CPT:
						$curd        = new LP_Lesson_CURD();
						$new_item_id = $curd->duplicate( $args['id'], array( 'post_status' => 'publish' ) );
						break;
					case LP_QUIZ_CPT:
						$curd        = new LP_Quiz_CURD();
						$new_item_id = $curd->duplicate( $args['id'], array( 'post_status' => 'publish' ) );
						break;
					case LP_QUESTION_CPT:
						$curd        = new LP_Question_CURD();
						$new_item_id = $curd->duplicate( $args['id'], array( 'post_status' => 'publish' ) );
						break;
					default:
						break;
				}

				$results = array( 'redirect' => admin_url( 'edit.php?post_type=' . $post_type ) );

				if ( is_wp_error( $new_item_id ) ) {
					LP_Admin_Notice::add_redirect( $new_item_id->get_error_message(), 'error' );
				} else {
					LP_Admin_Notice::add_redirect( sprintf( '<strong>%s</strong> %s', get_the_title( $args['id'] ), __( ' has duplicated', 'learnpress' ) ), 'updated' );
					$results['redirect'] = admin_url( 'post.php?post=' . $new_item_id . '&action=edit' );
				}

				wp_send_json( $results );


				die();
			}
		}

		/**
		 * Get question data in admin question editor.
		 *
		 * @since 3.0.0
		 *
		 * @param $question
		 * @param $object | if true, input in question object, do not need init LP_Question::get_question()
		 *
		 * @return array
		 */
		public static function get_question_data_to_question_editor( $question, $object = false ) {

			if ( ! $object ) {
				if ( get_post_type( $question ) !== LP_QUESTION_CPT ) {
					return array();
				}

				// get question
				$question = LP_Question::get_question( $question );
			}

			// question id
			$question_id = $question->get_id();

			$data = array(
				'id'      => $question_id,
				'open'    => false,
				'title'   => get_the_title( $question_id ),
				'type'    => array(
					'key'   => $question->get_type(),
					'label' => $question->get_type_label()
				),
				'answers' => $question->get_data( 'answer_options' )
			);

			return $data;
		}

		/**
		 * Get question data in admin quiz editor.
		 *
		 * @since 3.0.0
		 *
		 * @param       $question
		 * @param       $object | if true, input in question object, do not need init LP_Question::get_question()
		 *
		 * @return array
		 */
		public static function get_question_data_to_quiz_editor( $question, $object = false ) {

			if ( ! $object ) {
				if ( get_post_type( $question ) !== LP_QUESTION_CPT ) {
					return array();
				}

				// get question
				$question = LP_Question::get_question( $question );
			}

			// question id
			$question_id = $question->get_id();
			// question answer
			$answers = $question->get_data( 'answer_options' );

			$data = array(
				'id'       => $question_id,
				'open'     => false,
				'title'    => get_the_title( $question_id ),
				'type'     => array(
					'key'   => $question->get_type(),
					'label' => $question->get_type_label()
				),
				'answers'  => $answers,
				'settings' => array(
					'mark'        => get_post_meta( $question_id, '_lp_mark', true ),
					'explanation' => get_post_meta( $question_id, '_lp_explanation', true ),
					'hint'        => get_post_meta( $question_id, '_lp_hint', true )
				),
				'order'    => count( $answers )
			);

			return $data;
		}

		/**
		 * Update ordering of payments when user changing.
		 *
		 * @since 3.0.0
		 */
		public static function update_payment_order() {
			$payment_order = learn_press_get_request( 'order' );
			update_option( 'learn_press_payment_order', $payment_order );
		}

		/**
		 * Update email status.
		 *
		 * @since 3.0.0
		 */
		public static function update_email_status() {
			$email = LP_Emails::get_email( LP_Request::get_string( 'id' ) );
			if ( ! $email ) {
				return;
			}

			$status = $email->enable( LP_Request::get_string( 'status' ) == 'yes' );

			learn_press_send_json( array( 'status' => $status ) );
		}

		/**
		 * Toggle lesson preview.
		 */
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
			$exclude    = LP_Request::get( 'exclude' );

			$search = new LP_Modal_Search_Items( compact( 'term', 'type', 'context', 'context_id', 'paged', 'exclude' ) );

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
			$exclude     = LP_Request::get( 'exclude' );

			$search = new LP_Modal_Search_Users( compact( 'term', 'type', 'context', 'context_id', 'paged', 'multiple', 'text_format', 'exclude' ) );

			learn_press_send_json( array(
				'html'  => $search->get_html_items(),
				'nav'   => $search->get_pagination(),
				'users' => $search->get_items()
			) );

		}

		/**
		 * Search course category.
		 */
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

			global $wpdb;

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

			global $wpdb;
			$response = array(
				'result' => 'error'
			);
			if ( $order_item_ids = $order->add_items( $item_ids ) ) {
				$html        = '';
				$order_items = $order->get_items();

				$order_data                  = learn_press_update_order_items( $order_id );
				$currency_symbol             = learn_press_get_currency_symbol( $order_data['currency'] );
				$order_data['subtotal_html'] = learn_press_format_price( $order_data['subtotal'], $currency_symbol );
				$order_data['total_html']    = learn_press_format_price( $order_data['total'], $currency_symbol );

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


				$response = array(
					'result'     => 'success',
					'item_html'  => $html,
					'order_data' => $order_data
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
		 */
		public static function getPhpInput( $params = '' ) {
			static $data = false;
			if ( false === $data ) {
				try {
					$data = json_decode( file_get_contents( 'php://input' ), true );
				}
				catch ( Exception $exception ) {
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

		/*************/

		public static function load_chart() {
			if ( ! class_exists( 'LP_Submenu_Statistics' ) ) {
				$statistic = include_once LP_PLUGIN_PATH . '/inc/admin/sub-menus/class-lp-submenu-statistics.php';
			} else {
				$statistic = new LP_Submenu_Statistics();
			}
			$statistic->load_chart();
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

		public static function plugin_action() {
			$url = learn_press_get_request( 'url' );
			ob_start();
			wp_remote_get( $url );
			ob_get_clean();
			echo wp_remote_get( admin_url( 'admin.php?page=learn-press-addons&tab=installed' ) );
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
					$response['page'] = get_post( $page_id );
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

		public static function upload_user_avatar() {
			$file       = $_FILES['lp-upload-avatar'];
			$upload_dir = learn_press_user_profile_picture_upload_dir();

			add_filter( 'upload_dir', array( __CLASS__, '_user_avatar_upload_dir' ), 10000 );

			$result = wp_handle_upload( $file,
				array(
					'test_form' => false
				)
			);

			remove_filter( 'upload_dir', array( __CLASS__, '_user_avatar_upload_dir' ), 10000 );
			if ( is_array( $result ) ) {
				$result['name'] = $upload_dir['subdir'] . '/' . basename( $result['file'] );
				unset( $result['file'] );
			} else {
				$result = array(
					'error' => __( 'Upload profile avatar error.', 'learnpress' )
				);
			}
			learn_press_send_json( $result );
		}

		public static function _user_avatar_upload_dir( $dir ) {
			$dir = learn_press_user_profile_picture_upload_dir();

			return $dir;
		}

	}

	if ( defined( 'DOING_AJAX' ) ) {
		add_action( 'wp_ajax_learnpress_upload-user-avatar', array( 'LP_Admin_Ajax', 'upload_user_avatar' ) );
	}

	add_action( 'init', array( 'LP_Admin_Ajax', 'init' ) );
}
