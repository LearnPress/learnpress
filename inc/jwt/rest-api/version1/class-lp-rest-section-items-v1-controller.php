<?php
class LP_Jwt_Section_Items_V1_Controller extends LP_REST_Jwt_Controller {
	protected $namespace = 'learnpress/v1';

	protected $rest_base = 'section-items';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/items/(?P<section_id>[\d]+)',
			array(
				'args'   => array(
					'section_id' => array(
						'description' => esc_html__( 'Section ID.', 'learnpress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_section_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	public function get_items_permissions_check( $request ) {
		if ( empty( $request['section_id'] ) ) {
			return new WP_Error( 'lp_section_not_section_id', __( 'Sorry, Invalid Section ID param.', 'learnpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$course_id = LP_Section_DB::getInstance()->get_course_id_by_section( $request['section_id'] );

		if ( ! $course_id ) {
			return new WP_Error( 'lp_section_not_course', __( 'Please assign a section to the Course.', 'learnpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$post = get_post( $course_id );

		$post_status_obj = get_post_status_object( $post->post_status );
		if ( ! $post_status_obj || ! $post_status_obj->public ) {
			return new WP_Error( 'lp_section_not_public', __( 'The course is not public', 'learnpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	public function get_section_items( $request ) {
		$filters               = new LP_Section_Items_Filter();
		$filters->section_id   = $request['section_id'];
		$filters->limit        = $request['per_page'];
		$filters->page         = $request['page'];
		$filters->order        = $request['order'];
		$filters->search_title = $request['search'];
		$filters->item_ids     = $request['include'];
		$filters->item_not_ids = $request['exclude'];

		$query_results = LP_Section_DB::getInstance()->get_section_items_by_section_id( $filters );

		if ( is_wp_error( $query_results ) ) {
			return $query_results;
		}

		$results = $query_results['results'];

		$return = array();

		foreach ( $results as $result ) {
			$data     = $this->prepare_object_for_response( $result, $request );
			$return[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $return );

		$response->header( 'X-WP-Total', $query_results['total'] );
		$response->header( 'X-WP-TotalPages', (int) $query_results['pages'] );

		return $response;
	}

	public function prepare_object_for_response( $section_data, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data = $this->get_sections_data( $section_data, $context, $request );

		$response = rest_ensure_response( $data );

		return apply_filters( 'lp_jwt_rest_prepare_sections_object', $response, $section_data, $request );
	}

	public function get_sections_data( $item_data, $context, $request ) {
		$fields = $this->get_fields_for_response( $request );

		$course_id = LP_Section_DB::getInstance()->get_course_id_by_section( $request['section_id'] ?? 0 );
		$course    = learn_press_get_course( $course_id );

		if ( ! $course ) {
			return false;
		}

		$item_id = absint( $item_data['ID'] );

		$course_item = $course->get_item( $item_id );

		// Check if item is not exists or deactive add-on( Assignment, H5P, etc )
		if ( ! $course_item instanceof LP_Course_Item ) {
			return false;
		}

		$user = $this->get_current_user();

		$user_course = $user ? $user->get_course_data( $course_id ) : false;

		$user_item = $user_course ? $user_course->get_item( $item_id ) : false;

		if ( $user_item ) {
			$graduation = $user_item->get_graduation();
			$status     = $user_item->get_status();
		}

		$can_view_item = new LP_Model_User_Can_View_Course_Item();
		if ( $user ) {
			$can_view_content_course = $user->can_view_content_course( absint( $course_id ) );
			$can_view_item           = $user->can_view_item( $item_id, $can_view_content_course );
		}

		if ( rest_is_field_included( 'id', $fields ) ) {
			$data['id'] = $item_data['ID'] ?? 0;
		}

		if ( rest_is_field_included( 'name', $fields ) ) {
			$data['name'] = $item_data['post_title'] ?? '';
		}

		if ( rest_is_field_included( 'slug', $fields ) ) {
			$data['slug'] = $item_data['post_name'] ?? '';
		}

		if ( rest_is_field_included( 'status', $fields ) ) {
			$data['status'] = $item_data['post_status'] ?? '';
		}

		if ( rest_is_field_included( 'permalink', $fields ) ) {
			$data['permalink'] = $can_view_item->flag ? $course_item->get_permalink() : '';
		}

		if ( rest_is_field_included( 'type', $fields ) ) {
			$data['type'] = $course_item->get_item_type();
		}

		if ( rest_is_field_included( 'preview', $fields ) ) {
			$data['preview'] = $course_item->is_preview();
		}

		if ( rest_is_field_included( 'duration', $fields ) ) {
			$data['duration'] = $course_item->get_duration()->to_timer(
				array(
					'day'    => __( '%s days', 'learnpress' ),
					'hour'   => __( '%s hours', 'learnpress' ),
					'minute' => __( '%s mins', 'learnpress' ),
					'second' => __( '%s secs', 'learnpress' ),
				),
				true
			);
		}

		if ( rest_is_field_included( 'graduation', $fields ) && $graduation ) {
			$data['graduation'] = $graduation;
		}

		if ( rest_is_field_included( 'user_status', $fields ) && $status ) {
			$data['user_status'] = $status;
		}

		if ( rest_is_field_included( 'locked', $fields ) ) {
			$data['locked'] = ! $can_view_item->flag;
		}

		if ( rest_is_field_included( 'count_question', $fields ) && $course_item->get_item_type() === LP_QUIZ_CPT ) {
			$data['count_question'] = $course_item->count_questions();
		}

		if ( rest_is_field_included( 'content.raw', $fields ) && $can_view_item->flag ) {
			$data['content']['raw'] = $item_data['post_content'];
		}

		if ( rest_is_field_included( 'content.rendered', $fields ) && $can_view_item->flag ) {
			$data['content']['rendered'] = apply_filters( 'the_content', $item_data['post_content'] );
		}

		return $data;
	}

	public function get_current_user() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		return learn_press_get_user( $user_id );
	}

	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'learnpress_sections',
			'type'       => 'object',
			'properties' => array(
				'id'             => array(
					'description' => __( 'A unique identifier for the resource.', 'learnpress' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'           => array(
					'description' => __( 'Item name.', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'slug'           => array(
					'description' => __( 'Item slug.', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'permalink'      => array(
					'description' => __( 'Item URL.', 'learnpress' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'         => array(
					'description' => __( 'Item status (post status).', 'learnpress' ),
					'type'        => 'string',
					'default'     => 'publish',
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future' ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'type'           => array(
					'description' => __( 'Item type.', 'learnpress' ),
					'type'        => 'string',
					'default'     => '',
					'enum'        => learn_press_get_block_course_item_types(),
					'context'     => array( 'view', 'edit' ),
				),
				'preview'        => array(
					'description' => __( 'Item preview.', 'learnpress' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'duration'       => array(
					'description' => __( 'Item duration.', 'learnpress' ),
					'type'        => 'string',
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
				),
				'graduation'     => array(
					'description' => __( 'Item graduation.', 'learnpress' ),
					'type'        => 'string',
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
				),
				'user_status'    => array(
					'description' => __( 'Item status.', 'learnpress' ),
					'type'        => 'string',
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
				),
				'locked'         => array(
					'description' => __( 'Item locked.', 'learnpress' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'count_question' => array(
					'description' => __( 'Count questions.', 'learnpress' ),
					'type'        => 'integer',
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'content'        => array(
					'description' => __( 'Item content.', 'learnpress' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Title for the post, as it exists in the database.' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML title for the post, transformed for display.' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
							'readonly'    => true,
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	public function get_collection_params() {
		$params                       = array();
		$params['context']            = $this->get_context_param();
		$params['context']['default'] = 'view';

		$params['page']     = array(
			'description'       => __( 'The current page of the collection.', 'learnpress' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);
		$params['per_page'] = array(
			'description'       => __( 'The maximum number of items to be returned in the result set.', 'learnpress' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['order']    = array(
			'description'       => __( 'Sorting attributes in ascending or descending order.', 'learnpress' ),
			'type'              => 'string',
			'default'           => 'asc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['search']   = array(
			'description'       => __( 'Limit results to those matching a string.', 'learnpress' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['exclude']  = array(
			'description'       => __( 'Ensure the result set excludes specific IDs.', 'learnpress' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);
		$params['include']  = array(
			'description'       => __( 'Limit the result set to specific IDs.', 'learnpress' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);

		return apply_filters( 'lp_rest_sections_collection_params', $params );
	}
}
