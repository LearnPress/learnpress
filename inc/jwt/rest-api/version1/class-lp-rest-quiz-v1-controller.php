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
					$data['questions'] = $this->get_all_question( $object );
					break;
			}
		}

		$data['meta_data'] = $this->get_course_meta( $id );

		return $data;
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
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
