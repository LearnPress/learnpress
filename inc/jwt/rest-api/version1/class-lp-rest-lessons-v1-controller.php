<?php
class LP_Jwt_Lessons_V1_Controller extends LP_REST_Jwt_Posts_Controller {
	protected $namespace = 'learnpress/v1';

	protected $rest_base = 'lessons';

	protected $post_type = LP_LESSON_CPT;

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
			'/' . $this->rest_base . '/finish',
			array(
				'args' => array(
					'id' => array(
						'description' => esc_html__( 'Unique identifier for the resource.', 'learnpress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'finish_lesson' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'edit',
							)
						),
					),
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

	protected function get_object( $lesson = 0 ) {
		global $post;

		if ( false === $lesson && isset( $post, $post->ID ) && LP_LESSON_CPT === get_post_type( $post->ID ) ) {
			$id = absint( $post->ID );
		} elseif ( is_numeric( $lesson ) ) {
			$id = $lesson;
		} elseif ( $lesson instanceof LP_Lesson ) {
			$id = $lesson->get_id();
		} elseif ( ! empty( $lesson->ID ) ) {
			$id = $lesson->ID;
		}

		return LP_Course_Item::get_item( $id );
	}

	/**
	 * Get course ID by lesson ID assigned.
	 *
	 * @param [type] $item_id
	 * @return void
	 */
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

	public function finish_lesson( $request ) {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$id = isset( $request['id'] ) ? absint( $request['id'] ) : '';

			if ( empty( $id ) ) {
				throw new Exception( esc_html__( 'Error: No lesson available!.', 'learnpress' ) );
			}

			$course_id = $this->get_course_by_item_id( $id );

			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'Error: This lesson is not assign in Course.', 'learnpress' ) );
			}

			$course = learn_press_get_course( $course_id );
			$user   = learn_press_get_current_user();

			if ( empty( $course ) || empty( $user ) ) {
				throw new Exception( esc_html__( 'Error: No Course or User available.', 'learnpress' ) );
			}

			$result = $user->complete_lesson( $id, $course_id, true );

			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() ?? esc_html__( 'Error: Cannot complete Lesson', 'learnpress' ) );
			}

			$response->status  = 'success';
			$response->message = esc_html__( 'Congrats! You complete lesson is successfully', 'learnpress' );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
		}

		return rest_ensure_response( $response );
	}

	public function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->get_lesson_data( $object, $context, $request );

		$response = rest_ensure_response( $data );

		return apply_filters( "lp_jwt_rest_prepare_{$this->post_type}_object", $response, $object, $request );
	}

	protected function get_lesson_data( $object, $context = 'view' ) {
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
					$data['content'] = 'view' === $context ? apply_filters( 'the_content', $post->post_content ) : $post->post_content;
					break;
				case 'excerpt':
					$data['excerpt'] = $post->post_excerpt;
					break;
				case 'can_finish_course':
					$data['can_finish_course'] = $this->check_can_finish_course( $id );
					break;
				case 'duration':
					$data['duration'] = learn_press_get_post_translated_duration( $id, esc_html__( 'Lifetime', 'learnpress' ) );
					break;
				case 'assigned':
					$data['assigned'] = $assigned;
					break;
				case 'results':
					$data['results'] = $this->get_lesson_results( $object );
					break;
			}
		}

		$data['meta_data'] = $this->get_course_meta( $id );

		return $data;
	}

	public function check_can_finish_course( $id ) {
		$user = learn_press_get_current_user();

		if ( ! $user || ! $id ) {
			return falase;
		}

		$course_id = $this->get_course_by_item_id( $id );

		if ( empty( $course_id ) ) {
			return false;
		}

		$course = learn_press_get_course( $course_id );

		if ( $user && $course ) {
			$check = $user->can_show_finish_course_btn( $course );

			if ( $check['status'] === 'success' ) {
				return true;
			}

			return false;
		}

		return false;
	}

	public function get_lesson_results( $lesson ) {
		global $wpdb;

		$output = array();

		$user_id = learn_press_get_current_user_id();

		if ( ! $user_id || ! $lesson ) {
			return $output;
		}

		$id = ! empty( $lesson->ID ) ? $lesson->ID : $lesson->get_id();

		$course_id = $this->get_course_by_item_id( $id );

		if ( empty( $course_id ) ) {
			return $output;
		}

		$query = $wpdb->prepare( "SELECT status FROM {$wpdb->prefix}learnpress_user_items WHERE user_id=%d AND item_id=%d AND ref_id=%d", $user_id, $id, $course_id );

		$status = $wpdb->get_var( $query );

		$output['status'] = ! empty( $status ) ? $status : '';

		return $output;
	}

	public function get_course_meta( $id ) {
		if ( ! class_exists( 'LP_Meta_Box_Lesson' ) ) {
			include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/lesson/settings.php';
		}

		$metabox = new LP_Meta_Box_Lesson();

		$output = array();
		foreach ( $metabox->metabox( $id ) as $meta_key => $object ) {
			if ( is_a( $object, 'LP_Meta_Box_Field' ) ) {
				$object->id          = $meta_key;
				$output[ $meta_key ] = $object->meta_value( $id );
			}
		}

		return $output;
	}

	public function get_assigned( $id ) {
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
				'can_finish_course' => array(
					'description' => __( 'Can finish course', 'learnpress' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'duration'          => array(
					'description' => __( 'Duration', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
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
				'results'           => array(
					'description' => __( 'Retrieves the Lesson result..', 'learnpress' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'status' => array(
							'description' => __( 'Status.', 'learnpress' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
