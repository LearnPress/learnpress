<?php
class LP_Jwt_Quiz_V1_Controller extends LP_REST_Jwt_Posts_Controller {
	protected $namespace = 'learnpress/v1';

	protected $rest_base = 'quiz';

	protected $post_type = LP_QUIZ_CPT;

	protected $hierarchical = true;

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => esc_html__( 'Unique identifier for the resource.', 'learnpress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/start',
			array(
				'args' => array(
					'id' => array(
						'description' => esc_html__( 'Quiz ID.', 'learnpress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'start_quiz' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/check_answer',
			array(
				'args' => array(
					'id'          => array(
						'description' => esc_html__( 'Quiz ID.', 'learnpress' ),
						'type'        => 'integer',
					),
					'question_id' => array(
						'description' => esc_html__( 'Question ID.', 'learnpress' ),
						'type'        => 'integer',
					),
					'answered'    => array(
						'description' => esc_html__( 'Answer this question.', 'learnpress' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'check_quiz' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/finish',
			array(
				'args' => array(
					'id'       => array(
						'description' => esc_html__( 'Quiz ID.', 'learnpress' ),
						'type'        => 'integer',
					),
					'answered' => array(
						'description' => esc_html__( 'Answer all question.', 'learnpress' ),
						'type'        => 'object',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'submit_quiz' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Checks if a course can be read.
	 *
	 * Correctly handles courses with the inherit status.
	 *
	 * @author Nhamdv
	 *
	 * @return bool Whether the post can be read.
	 * */
	public function check_read_permission( $post_id ) {
		if ( empty( absint( $post_id ) ) ) {
			return false;
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		if ( lp_rest_check_post_permissions( $this->post_type, 'read', $post_id ) ) {
			return true;
		}

		$post_status_obj = get_post_status_object( $post->post_status );
		if ( ! $post_status_obj || ! $post_status_obj->public ) {
			return false;
		}

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$user = learn_press_get_user( $user_id );

		// Get course ID by lesson ID assigned.
		$course_id = $this->get_course_by_item_id( $post_id );

		if ( empty( $course_id ) ) {
			return false;
		}

		$can_view_content_course = $user->can_view_content_course( $course_id );

		$can_view_item = $user->can_view_item( $post_id, $can_view_content_course );

		if ( ! $can_view_item->flag ) {
			return false;
		}

		// Can we read the parent if we're inheriting?
		if ( 'inherit' === $post->post_status && $post->post_parent > 0 ) {
			$parent = get_post( $post->post_parent );

			if ( $parent ) {
				return $this->check_read_permission( $parent );
			}
		}

		return true;
	}

	protected function get_course_by_item_id( $item_id ) {
		static $output;

		global $wpdb;

		if ( empty( $item_id ) ) {
			return false;
		}

		if ( ! isset( $output ) ) {
			$output = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT c.ID FROM {$wpdb->posts} c
					INNER JOIN {$wpdb->learnpress_sections} s ON c.ID = s.section_course_id
					INNER JOIN {$wpdb->learnpress_section_items} si ON si.section_id = s.section_id
					WHERE si.item_id = %d ORDER BY si.section_id DESC LIMIT 1
					",
					$item_id
				)
			);
		}

		if ( $output ) {
			return absint( $output );
		}

		return false;
	}

	protected function get_object( $quiz = 0 ) {
		global $post;

		if ( false === $quiz && isset( $post, $post->ID ) && LP_QUIZ_CPT === get_post_type( $post->ID ) ) {
			$id = absint( $post->ID );
		} elseif ( is_numeric( $quiz ) ) {
			$id = $quiz;
		} elseif ( $quiz instanceof LP_Quiz ) {
			$id = $quiz->get_id();
		} elseif ( ! empty( $quiz->ID ) ) {
			$id = $quiz->ID;
		}

		return new LP_Quiz( $id );
	}

	public function start_quiz( $request ) {
		$response  = new LP_REST_Response();
		$quiz_id   = isset( $request['id'] ) ? absint( $request['id'] ) : '';
		$course_id = $this->get_course_by_item_id( $quiz_id );

		if ( empty( $quiz_id ) || empty( $course_id ) ) {
			$response->status  = 'error';
			$response->message = esc_html__( 'No Quiz ID or Quiz is not assigned in Course.', 'learnpress' );

			return rest_ensure_response( $response );
		}

		if ( ! class_exists( 'LP_REST_Users_Controller' ) ) {
			include_once LP_PLUGIN_PATH . 'inc/rest-api/v1/frontend/class-lp-rest-users-controller.php';
		}

		$controller = new LP_REST_Users_Controller();

		$request->set_param( 'course_id', $course_id );
		$request->set_param( 'item_id', $quiz_id );

		return $controller->start_quiz( $request );
	}

	public function check_quiz( $request ) {
		$response    = new LP_REST_Response();
		$quiz_id     = isset( $request['id'] ) ? absint( $request['id'] ) : '';
		$question_id = isset( $request['question_id'] ) ? absint( $request['question_id'] ) : '';
		$answered    = isset( $request['answered'] ) ? wp_unslash( $request['answered'] ) : '';
		$course_id   = $this->get_course_by_item_id( $quiz_id );

		if ( empty( $quiz_id ) || empty( $course_id ) ) {
			$response->status  = 'error';
			$response->message = esc_html__( 'No Quiz ID or Quiz is not assigned in Course.', 'learnpress' );

			return rest_ensure_response( $response );
		}

		if ( ! class_exists( 'LP_REST_Users_Controller' ) ) {
			include_once LP_PLUGIN_PATH . 'inc/rest-api/v1/frontend/class-lp-rest-users-controller.php';
		}

		$controller = new LP_REST_Users_Controller();

		$request->set_param( 'answered', $answered );
		$request->set_param( 'question_id', $question_id );
		$request->set_param( 'course_id', $course_id );
		$request->set_param( 'item_id', $quiz_id );

		return $controller->check_answer( $request );
	}

	public function submit_quiz( $request ) {
		$response = new LP_REST_Response();
		$quiz_id  = isset( $request['id'] ) ? absint( $request['id'] ) : '';
		$answered = isset( $request['answered'] ) ? wp_unslash( $request['answered'] ) : '';

		$course_id = $this->get_course_by_item_id( $quiz_id );

		if ( empty( $quiz_id ) || empty( $course_id ) ) {
			$response->status  = 'error';
			$response->message = esc_html__( 'No Quiz ID or Quiz is not assigned in Course.', 'learnpress' );

			return rest_ensure_response( $response );
		}

		if ( ! isset( $request['answered'] ) ) {
			$response->status  = 'error';
			$response->message = esc_html__( 'No Answed param.', 'learnpress' );

			return rest_ensure_response( $response );
		}

		if ( ! class_exists( 'LP_REST_Users_Controller' ) ) {
			include_once LP_PLUGIN_PATH . 'inc/rest-api/v1/frontend/class-lp-rest-users-controller.php';
		}

		$controller = new LP_REST_Users_Controller();

		$request->set_param( 'answered', $answered );
		$request->set_param( 'course_id', $course_id );
		$request->set_param( 'item_id', $quiz_id );

		return $controller->submit_quiz( $request );
	}

	public function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->get_quiz_data( $object, $context, $request );

		$response = rest_ensure_response( $data );

		return apply_filters( "lp_jwt_rest_prepare_{$this->post_type}_object", $response, $object, $request );
	}

	protected function get_quiz_data( $object, $context = 'view' ) {
		$request = func_num_args() >= 2 ? func_get_arg( 2 ) : new WP_REST_Request( '', '', array( 'context' => $context ) );
		$fields  = $this->get_fields_for_response( $request );

		$id   = ! empty( $object->ID ) ? $object->ID : $object->get_id();
		$post = get_post( $id );
		$data = array();

		$assigned = $this->get_assigned( $id );
		if ( ! empty( $assigned ) && method_exists( $object, 'set_course' ) ) {
			$course_id = $assigned['course']['id'];
			$object->set_course( $course_id );
		}

		foreach ( $fields as $field ) {
			switch ( $field ) {
				case 'id':
					$data['id'] = $id;
					break;
				case 'name':
					$data['name'] = $post->post_title;
					break;
				case 'slug':
					$data['slug'] = $post->post_name;
					break;
				case 'permalink':
					$data['permalink'] = $object->get_permalink();
					break;
				case 'date_created':
					$data['date_created'] = lp_jwt_prepare_date_response( $post->post_date_gmt, $post->post_date );
					break;
				case 'date_created_gmt':
					$data['date_created_gmt'] = lp_jwt_prepare_date_response( $post->post_date_gmt );
					break;
				case 'date_modified':
					$data['date_modified'] = lp_jwt_prepare_date_response( $post->post_modified_gmt, $post->post_modified );
					break;
				case 'date_modified_gmt':
					$data['date_modified_gmt'] = lp_jwt_prepare_date_response( $post->post_modified_gmt );
					break;
				case 'status':
					$data['status'] = $post->post_status;
					break;
				case 'content':
					$data['content'] = 'view' === $context ? wpautop( do_shortcode( $post->post_content ) ) : $post->post_content;
					break;
				case 'excerpt':
					$data['excerpt'] = $post->post_excerpt;
					break;
				case 'assigned':
					$data['assigned'] = $assigned;
					break;
				case 'questions':
					$data['questions'] = $this->get_quiz_results( $object, true )['questions'] ?? array();
					break;
				case 'results':
					$data['results'] = $this->get_quiz_results( $object );
					break;
			}
		}

		$data['meta_data'] = $this->get_course_meta( $id );

		return $data;
	}

	public function get_quiz_results( $quiz, $get_question = false ) {
		$output = array();

		$user = learn_press_get_current_user();

		if ( ! $user || ! $quiz ) {
			return $output;
		}

		$id = ! empty( $quiz->ID ) ? $quiz->ID : $quiz->get_id();

		$course_id = $this->get_course_by_item_id( $id );

		if ( empty( $course_id ) ) {
			return $output;
		}

		$user_course         = $user->get_course_data( $course_id );
		$user_quiz           = $user_course ? $user_course->get_item( $id ) : false;
		$show_check          = $quiz->get_instant_check();
		$show_correct_review = $quiz->get_show_correct_review();
		$answered            = array();
		$status              = '';
		$checked_questions   = array();
		$question_ids        = array();

		if ( $user_quiz ) {
			$status            = $user_quiz->get_status();
			$quiz_results      = $user_quiz->get_results( '' );
			$checked_questions = $user_quiz->get_checked_questions();
			$expiration_time   = $user_quiz->get_expiration_time();

			// If expiration time is specific then calculate total time
			if ( $expiration_time && ! $expiration_time->is_null() ) {
				$total_time = strtotime( $user_quiz->get_expiration_time() ) - strtotime( $user_quiz->get_start_time() );
			}

			$output = array(
				'status'            => $status,
				'attempts'          => $user_quiz->get_attempts(),
				'checked_questions' => $checked_questions,
				'start_time'        => lp_jwt_prepare_date_response( $user_quiz->get_start_time()->toSql( false ) ),
				'retaken'           => absint( $user_quiz->get_retaken_count() ),
			);

			if ( isset( $total_time ) ) {
				$output['total_time'] = lp_jwt_prepare_date_response( $total_time );
				$output['endTime']    = lp_jwt_prepare_date_response( $expiration_time->toSql( false ) );
			}

			if ( $quiz_results ) {
				$output['results'] = $quiz_results->get();
				$answered          = $quiz_results->getQuestions();
				$question_ids      = $quiz_results->getQuestions( 'ids' );
			} else {
				$question_ids = $quiz->get_question_ids();
			}

			$output['answered']     = ! empty( $answered ) ? (object) $answered : new stdClass();
			$output['question_ids'] = array_map( 'absint', array_values( $question_ids ) );
		}

		$duration = $quiz->get_duration();

		$array = array(
			'passing_grade'      => $quiz->get_passing_grade(),
			'negative_marking'   => $quiz->get_negative_marking(),
			'instant_check'      => $quiz->get_instant_check(),
			'retake_count'       => absint( $quiz->get_retake_count() ),
			'questions_per_page' => $quiz->get_pagination(),
			'page_numbers'       => get_post_meta( $quiz->get_id(), '_lp_pagination_numbers', true ) === 'yes',
			'review_questions'   => $quiz->get_review_questions(),
			'support_options'    => learn_press_get_question_support_answer_options(),
			'duration'           => $duration ? $duration->get() : false,
		);

		if ( function_exists( 'learn_press_rest_prepare_user_questions' ) && $get_question ) {
			$questions = learn_press_rest_prepare_user_questions(
				$question_ids,
				array(
					'instant_check'       => $show_check,
					'quiz_status'         => $status,
					'checked_questions'   => $checked_questions,
					'answered'            => $answered,
					'show_correct_review' => $show_correct_review,
				)
			);

			$output['questions'] = $questions;
		}

		return array_merge( $array, $output );
	}

	public function get_course_meta( $id ) {
		if ( ! class_exists( 'LP_Meta_Box_Quiz' ) ) {
			include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/quiz/settings.php';
		}

		$metabox = new LP_Meta_Box_Quiz();

		$output = array();
		foreach ( $metabox->metabox( $id ) as $meta_key => $object ) {
			if ( is_a( $object, 'LP_Meta_Box_Field' ) ) {
				$object->id          = $meta_key;
				$output[ $meta_key ] = $object->meta_value( $id );
			}
		}

		return $output;
	}

	public function get_assigned( int $id ) : array {
		$courses = learn_press_get_item_courses( $id );

		$output = array();

		if ( $courses ) {
			foreach ( $courses as $course ) {
				$output['course'] = array(
					'id'      => $course->ID,
					'title'   => $course->post_title,
					'slug'    => $course->post_name,
					'content' => $course->post_content,
					'author'  => $course->post_author,
				);
			}
		}

		return $output;
	}

	public function get_all_question( object $quiz ) : array {
		$questions = array();

		if ( function_exists( 'learn_press_rest_prepare_user_questions' ) ) {
			$questions = learn_press_rest_prepare_user_questions(
				$quiz->get_question_ids(),
				array(
					'instant_check'       => $quiz->get_instant_check(),
					'show_correct_review' => $quiz->get_show_correct_review(),
				)
			);
		}

		return $questions;
	}

	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'description' => __( 'Unique identifier for the resource.', 'learnpress' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'              => array(
					'description' => __( 'Course name.', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'slug'              => array(
					'description' => __( 'Course slug.', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'permalink'         => array(
					'description' => __( 'Course URL.', 'learnpress' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'      => array(
					'description' => __( "The date the Course was created, in the site's timezone.", 'learnpress' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'  => array(
					'description' => __( 'The date the Course was created, as GMT.', 'learnpress' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified'     => array(
					'description' => __( "The date the Course was last modified, in the site's timezone.", 'learnpress' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt' => array(
					'description' => __( 'The date the Course was last modified, as GMT.', 'learnpress' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'            => array(
					'description' => __( 'Course status (post status).', 'learnpress' ),
					'type'        => 'string',
					'default'     => 'publish',
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future' ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'content'           => array(
					'description' => __( 'Content course.', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'excerpt'           => array(
					'description' => __( 'Retrieves the course excerpt..', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'assigned'          => array(
					'description' => __( 'Assigned.', 'learnpress' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'id'      => array(
							'description' => __( 'Item ID.', 'learnpress' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'title'   => array(
							'description' => __( 'Title.', 'learnpress' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'slug'    => array(
							'description' => __( 'Item slug.', 'learnpress' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'content' => array(
							'description' => __( 'Item Content.', 'learnpress' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'author'  => array(
							'description' => __( 'Item Author.', 'learnpress' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'questions'         => array(
					'description' => __( 'List all Question in Quiz.', 'learnpress' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'description' => __( 'Question items.', 'learnpress' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'items'       => array(
							'id'      => array(
								'description' => __( 'Item ID.', 'learnpress' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'type'    => array(
								'description' => __( 'Item Type.', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'title'   => array(
								'description' => __( 'Item title.', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content' => array(
								'description' => __( 'Item Content.', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'point'   => array(
								'description' => __( 'Point.', 'learnpress' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'hint'    => array(
								'description' => __( 'Question Hint.', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'options' => array(
								'description' => __( 'Question Options.', 'learnpress' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'description' => __( 'Question items.', 'learnpress' ),
									'type'        => 'object',
									'context'     => array( 'view', 'edit' ),
									'items'       => array(
										'title' => array(
											'description' => __( 'Item title.', 'learnpress' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'value' => array(
											'description' => __( 'Item value.', 'learnpress' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'uid'   => array(
											'description' => __( 'Item id.', 'learnpress' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
						),
					),
				),
				'results'           => array(
					'description' => __( 'Retrieves the quiz result..', 'learnpress' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
