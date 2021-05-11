<?php
class LP_Jwt_Users_V1_Controller extends LP_REST_Jwt_Controller {
	protected $namespace = 'learnpress/v1';

	protected $rest_base = 'users';

	protected $hierarchical = true;

	protected $meta;

	public function __construct() {
		$this->meta = new WP_REST_User_Meta_Fields();
	}

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
			'/' . $this->rest_base . '/(?P<id>[\d]+)/(?P<tab>[\w]+)',
			array(
				'args'   => array(
					'id'  => array(
						'description' => esc_html__( 'Unique identifier for the resource.', 'learnpress' ),
						'type'        => 'integer',
					),
					'tab' => array(
						'description' => esc_html__( 'Get content in profile learnpress tab.', 'learnpress' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item_tab' ),
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

	public function get_items_permissions_check( $request ) {
		if ( ! empty( $request['roles'] ) && ! current_user_can( 'list_users' ) ) {
			return new WP_Error(
				'rest_user_cannot_view',
				__( 'Sorry, you are not allowed to filter users by role.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	public function get_item_permissions_check( $request ) {
		$user = $this->get_user( $request['id'] );

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$types = get_post_types( array( 'show_in_rest' => true ), 'names' );

		if ( get_current_user_id() === $user->ID ) {
			return true;
		}

		if ( 'edit' === $request['context'] && ! current_user_can( 'list_users' ) ) {
			return new WP_Error(
				'rest_user_cannot_view',
				__( 'Sorry, you are not allowed to list users.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		} elseif ( ! count_user_posts( $user->ID, $types ) && ! current_user_can( 'edit_user', $user->ID ) && ! current_user_can( 'list_users' ) ) {
			return new WP_Error(
				'rest_user_cannot_view',
				__( 'Sorry, you are not allowed to list users.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	protected function get_user( $id ) {
		$error = new WP_Error(
			'rest_user_invalid_id',
			__( 'Invalid user ID.' ),
			array( 'status' => 404 )
		);

		if ( (int) $id <= 0 ) {
			return $error;
		}

		$user = get_userdata( (int) $id );
		if ( empty( $user ) || ! $user->exists() ) {
			return $error;
		}

		if ( is_multisite() && ! is_user_member_of_blog( $user->ID ) ) {
			return $error;
		}

		return $user;
	}

	public function get_item_tab( $request ) {
		$user   = $this->get_user( $request['id'] );
		$output = array();

		switch ( $request['tab'] ) {
			case 'overview':
				$output = $this->get_overview_tab_contents( $user );
				break;

			case 'courses':
				$output = $this->get_course_tab_contents( $request );
				break;

			case 'quiz':
				$output = $this->get_quiz_tab_contents( $request );
				break;

			default:
				$output = esc_html__( 'No Tab content!', 'learnpress' );
		}

		$return = apply_filters( 'learnpress/jwt/rest-api/user/tab-content', $output, $request );

		$response = rest_ensure_response( $return );

		return $response;
	}

	public function get_overview_tab_contents( $user ) {
		$output = array();

		$query     = LP_Profile::instance()->query_courses( 'purchased' );
		$counts    = $query['counts'];
		$statistic = array(
			'enrolled_courses'  => isset( $counts['all'] ) ? $counts['all'] : 0,
			'active_courses'    => isset( $counts['in-progress'] ) ? $counts['in-progress'] : 0,
			'completed_courses' => isset( $counts['finished'] ) ? $counts['finished'] : 0,
			'total_courses'     => count_user_posts( $user->ID, LP_COURSE_CPT ),
			'total_users'       => learn_press_count_instructor_users( $user->ID ),
		);

		$output['statistic'] = array_map( 'absint', $statistic );

		$featured_query = new LP_Course_Query(
			array(
				'paginate' => false,
				'featured' => 'yes',
				'return'   => 'ids',
				'author'   => $user->ID,
			)
		);

		$output['featured'] = $featured_query->get_courses();

		$latest_query = new LP_Course_Query(
			array(
				'paginate' => false,
				'return'   => 'ids',
				'author'   => $user->get_id(),
				'limit'    => '-1',
			)
		);

		$output['latest'] = $latest_query->get_courses();

		return $output;
	}

	public function get_course_tab_contents( $request ) {
		$output = array(
			'enrolled' => array(),
			'created'  => array(),
		);

		$profile          = learn_press_get_profile( $request['id'] );
		$user             = learn_press_get_user( $request['id'] );
		$filters_enrolled = array(
			'all'      => 'all',
			'finished' => 'finished',
			'passed'   => 'passed',
			'failed'   => 'failed',
		);

		if ( method_exists( $profile, 'query_courses' ) ) {
			foreach ( $filters_enrolled as $key => $filter ) {
				$query_enrolled = $profile->query_courses(
					'purchased',
					array(
						'status' => $filter,
						'limit'  => $request['per_page'] ?? '100',
						'paged'  => $request['paged'] ?? 1,
					)
				);

				$enrolled_ids = array();
				if ( ! empty( $query_enrolled['items'] ) ) {
					foreach ( $query_enrolled['items'] as $enrolled_item ) {
						$course_data    = $user->get_course_data( $enrolled_item->get_id() );
						$enrolled_ids[] = array(
							'id'         => $enrolled_item->get_id() ?? '',
							'graduation' => $course_data->get_graduation() ?? '',
							'status'     => $course_data->get_status() ?? '',
							'start_time' => lp_jwt_prepare_date_response( $course_data->get_start_time() ? $course_data->get_start_time()->toSql( false ) : '' ),
							'end_time'   => lp_jwt_prepare_date_response( $course_data->get_end_time() ? $course_data->get_end_time()->toSql( false ) : '' ),
							'expiration' => lp_jwt_prepare_date_response( $course_data->get_expiration_time() ? $course_data->get_expiration_time()->toSql( false ) : '' ),
							'results'    => array_map( 'json_decode', LP_User_Items_Result_DB::instance()->get_results( $course_data->get_user_item_id(), 4, false ) ),
						);
					}
				}

				$output['enrolled'][ $key ] = $enrolled_ids;
			}
		}

		$filters_created = array(
			'all'     => '',
			'publish' => 'publish',
			'pending' => 'pending',
		);

		if ( method_exists( $profile, 'query_courses' ) ) {
			foreach ( $filters_created as $created_key => $filter ) {
				$query_created = $profile->query_courses(
					'own',
					array(
						'status' => $filter,
						'limit'  => $request['per_page'] ?? '100',
						'paged'  => $request['paged'] ?? 1,
					)
				);

				$created_ids = array();
				if ( ! empty( $query_created['items'] ) ) {
					foreach ( $query_created['items'] as $created_item ) {
						$created_ids[] = $created_item;
					}
				}

				$output['created'][ $created_key ] = array_map( 'absint', $created_ids );
			}
		}

		return $output;
	}

	/**
	 * Get Profile Quiz Tab content
	 *
	 * @param [type] $request
	 * @return void
	 *
	 * @author Nhamdv <daonham@gmail.com>
	 */
	public function get_quiz_tab_contents( $request ) {
		$output = array();

		$profile = learn_press_get_profile( $request['id'] );
		$filters = array(
			'all'         => '',
			'finished'    => 'completed',
			'passed'      => 'passed',
			'failed'      => 'failed',
			'in-progress' => 'in-progress',
		);

		if ( method_exists( $profile, 'query_quizzes' ) ) {
			foreach ( $filters as $key => $filter ) {
				$query = $profile->query_quizzes(
					array(
						'status' => $filter,
						'limit'  => $request['per_page'] ?? '100',
						'paged'  => $request['paged'] ?? 1,
					)
				);

				$ids = array();
				if ( ! empty( $query['items'] ) ) {
					foreach ( $query['items'] as $item ) {
						$ids[] = array(
							'id'         => $item->get_id(),
							'result'     => $item->get_percent_result() ?? '',
							'graduation' => $item->get_graduation() ?? '',
							'start_time' => $item->get_start_time() ? lp_jwt_prepare_date_response( $item->get_start_time()->toSql( false ) ) : '',
							'end_time'   => $item->get_end_time() ? lp_jwt_prepare_date_response( $item->get_end_time()->toSql( false ) ) : '',
							'data'       => $filter !== 'in-progress' ? LP_User_Items_Result_DB::instance()->get_result( $item->get_user_item_id() ) : array(),
							'attempt'    => $filter !== 'in-progress' ? array_map( 'json_decode', LP_User_Items_Result_DB::instance()->get_results( $item->get_user_item_id(), 4, false ) ) : array(),
						);
					}
				}

				$output[ $key ] = $ids;
			}
		}

		return $output;
	}

	/**
	 * Retrieves a single user.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$user = $this->get_user( $request['id'] );

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$user     = $this->prepare_item_for_response( $user, $request );
		$response = rest_ensure_response( $user );

		return $response;
	}

	/**
	 * Retrieves all users.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {

		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();

		/*
		 * This array defines mappings between public API query parameters whose
		 * values are accepted as-passed, and their internal WP_Query parameter
		 * name equivalents (some are the same). Only values which are also
		 * present in $registered will be set.
		 */
		$parameter_mappings = array(
			'exclude'  => 'exclude',
			'include'  => 'include',
			'order'    => 'order',
			'per_page' => 'number',
			'search'   => 'search',
			'roles'    => 'role__in',
			'slug'     => 'nicename__in',
		);

		$prepared_args = array();

		/*
		 * For each known parameter which is both registered and present in the request,
		 * set the parameter's value on the query $prepared_args.
		 */
		foreach ( $parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$prepared_args[ $wp_param ] = $request[ $api_param ];
			}
		}

		if ( isset( $registered['offset'] ) && ! empty( $request['offset'] ) ) {
			$prepared_args['offset'] = $request['offset'];
		} else {
			$prepared_args['offset'] = ( $request['page'] - 1 ) * $prepared_args['number'];
		}

		if ( isset( $registered['orderby'] ) ) {
			$orderby_possibles        = array(
				'id'              => 'ID',
				'include'         => 'include',
				'name'            => 'display_name',
				'registered_date' => 'registered',
				'slug'            => 'user_nicename',
				'include_slugs'   => 'nicename__in',
				'email'           => 'user_email',
				'url'             => 'user_url',
			);
			$prepared_args['orderby'] = $orderby_possibles[ $request['orderby'] ];
		}

		if ( isset( $registered['who'] ) && ! empty( $request['who'] ) && 'authors' === $request['who'] ) {
			$prepared_args['who'] = 'authors';
		} elseif ( ! current_user_can( 'list_users' ) ) {
			$prepared_args['has_published_posts'] = get_post_types( array( 'show_in_rest' => true ), 'names' );
		}

		if ( ! empty( $prepared_args['search'] ) ) {
			$prepared_args['search'] = '*' . $prepared_args['search'] . '*';
		}
		/**
		 * Filters WP_User_Query arguments when querying users via the REST API.
		 *
		 * @link https://developer.wordpress.org/reference/classes/wp_user_query/
		 *
		 * @since 4.7.0
		 *
		 * @param array           $prepared_args Array of arguments for WP_User_Query.
		 * @param WP_REST_Request $request       The REST API request.
		 */
		$prepared_args = apply_filters( 'rest_user_query', $prepared_args, $request );

		$query = new WP_User_Query( $prepared_args );

		$users = array();

		foreach ( $query->results as $user ) {
			$data    = $this->prepare_item_for_response( $user, $request );
			$users[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $users );

		// Store pagination values for headers then unset for count query.
		$per_page = (int) $prepared_args['number'];
		$page     = ceil( ( ( (int) $prepared_args['offset'] ) / $per_page ) + 1 );

		$prepared_args['fields'] = 'ID';

		$total_users = $query->get_total();

		if ( $total_users < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $prepared_args['number'], $prepared_args['offset'] );
			$count_query = new WP_User_Query( $prepared_args );
			$total_users = $count_query->get_total();
		}

		$response->header( 'X-WP-Total', (int) $total_users );

		$max_pages = ceil( $total_users / $per_page );

		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base = add_query_arg( urlencode_deep( $request->get_query_params() ), rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );
		if ( $page > 1 ) {
			$prev_page = $page - 1;

			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}

			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );

			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Prepares a single user output for response.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_User         $user    User object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $user, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'embed';
		$data    = $this->get_users_data( $user, $context, $request );

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $user ) );

		return apply_filters( 'lp_rest_prepare_user', $response, $user, $request );
	}

	public function get_users_data( $user, $context = 'view' ) {
		$request = func_num_args() >= 2 ? func_get_arg( 2 ) : new WP_REST_Request( '', '', array( 'context' => $context ) );
		$fields  = $this->get_fields_for_response( $request );
		$data    = array();

		foreach ( $fields as $field ) {
			switch ( $field ) {
				case 'id':
					$data['id'] = $user->ID;
					break;
				case 'username':
					$data['username'] = $user->user_login;
					break;
				case 'name':
					$data['name'] = $user->display_name;
					break;
				case 'first_name':
					$data['first_name'] = $user->first_name;
					break;
				case 'last_name':
					$data['last_name'] = $user->last_name;
					break;
				case 'email':
					$data['email'] = $user->user_email;
					break;
				case 'url':
					$data['url'] = $user->user_url;
					break;
				case 'description':
					$data['description'] = $user->description;
					break;
				case 'link':
					$data['link'] = get_author_posts_url( $user->ID, $user->user_nicename );
					break;
				case 'locale':
					$data['locale'] = get_user_locale( $user );
					break;
				case 'nickname':
					$data['nickname'] = $user->nickname;
					break;
				case 'slug':
					$data['slug'] = $user->user_nicename;
					break;
				case 'roles':
					$data['roles'] = array_values( $user->roles );
					break;
				case 'registered_date':
					$data['registered_date'] = gmdate( 'c', strtotime( $user->user_registered ) );
					break;
				case 'capabilities':
					$data['capabilities'] = (object) $user->allcaps;
					break;
				case 'extra_capabilities':
					$data['extra_capabilities'] = (object) $user->caps;
					break;
				case 'avatar_urls':
					$data['avatar_urls'] = rest_get_avatar_urls( $user );
					break;
				case 'meta':
					$data['meta'] = $this->meta->get_value( $user->ID, $request );
					break;
				case 'custom_fields':
					$data['custom_fields'] = $this->custom_register( $user );
					break;
				case 'tabs':
					$data['tabs'] = $this->get_lp_data_tabs( $user );
					break;
			}
		}

		return $data;
	}

	/**
	 * Display register cutom form in LearnPress Setting
	 *
	 * @param object $user
	 * @return array
	 */
	public function custom_register( $user ) {
		$output = array();

		if ( function_exists( 'lp_get_user_custom_register_fields' ) ) {
			$custom_fields  = LP()->settings()->get( 'register_profile_fields' );
			$custom_profile = lp_get_user_custom_register_fields( $user->ID );

			if ( $custom_fields ) {
				foreach ( $custom_fields as $field ) {
					$value            = sanitize_key( $field['name'] );
					$output[ $value ] = isset( $custom_profile[ $value ] ) ? $custom_profile[ $value ] : '';
				}
			}
		}

		return $output;
	}

	public function get_lp_data_tabs( $user ) {
		$output = array();

		if ( function_exists( 'learn_press_get_user_profile_tabs' ) ) {
			$tabs = learn_press_get_user_profile_tabs();

			foreach ( $tabs->get() as $key => $tab ) {
				$output[ $key ] = array(
					'title'    => $tab['title'] ?? '',
					'slug'     => $tab['slug'] ?? '',
					'priority' => $tab['priority'] ?? '',
					'icon'     => $tab['icon'] ?? '',
				);

				if ( ! empty( $tab['sections'] ) ) {
					foreach ( $tab['sections'] as $section_key => $section ) {
						$output[ $key ]['section'][ $section_key ] = array(
							'title'    => $section['title'] ?? '',
							'slug'     => $section['slug'] ?? '',
							'priority' => $section['priority'] ?? '',
						);
					}
				}
			}
		}

		return $output;
	}

	/**
	 * Prepares links for the user request.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_User $user User object.
	 * @return array Links for the given user.
	 */
	protected function prepare_links( $user ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $user->ID ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;
	}

	public function check_username( $value, $request, $param ) {
		$username = (string) $value;

		if ( ! validate_username( $username ) ) {
			return new WP_Error(
				'rest_user_invalid_username',
				__( 'This username is invalid because it uses illegal characters. Please enter a valid username.' ),
				array( 'status' => 400 )
			);
		}

		/** This filter is documented in wp-includes/user.php */
		$illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );

		if ( in_array( strtolower( $username ), array_map( 'strtolower', $illegal_logins ), true ) ) {
			return new WP_Error(
				'rest_user_invalid_username',
				__( 'Sorry, that username is not allowed.' ),
				array( 'status' => 400 )
			);
		}

		return $username;
	}

	/**
	 * Check a user password for the REST API.
	 *
	 * Performs a couple of checks like edit_user() in wp-admin/includes/user.php.
	 *
	 * @since 4.7.0
	 *
	 * @param string          $value   The password submitted in the request.
	 * @param WP_REST_Request $request Full details about the request.
	 * @param string          $param   The parameter name.
	 * @return string|WP_Error The sanitized password, if valid, otherwise an error.
	 */
	public function check_user_password( $value, $request, $param ) {
		$password = (string) $value;

		if ( empty( $password ) ) {
			return new WP_Error(
				'rest_user_invalid_password',
				__( 'Passwords cannot be empty.' ),
				array( 'status' => 400 )
			);
		}

		if ( false !== strpos( $password, '\\' ) ) {
			return new WP_Error(
				'rest_user_invalid_password',
				sprintf(
					/* translators: %s: The '\' character. */
					__( 'Passwords cannot contain the "%s" character.' ),
					'\\'
				),
				array( 'status' => 400 )
			);
		}

		return $password;
	}

	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'user',
			'type'       => 'object',
			'properties' => array(
				'id'                 => array(
					'description' => __( 'Unique identifier for the user.' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'username'           => array(
					'description' => __( 'Login name for the user.' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'check_username' ),
					),
				),
				'name'               => array(
					'description' => __( 'Display name for the user.' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'first_name'         => array(
					'description' => __( 'First name for the user.' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'last_name'          => array(
					'description' => __( 'Last name for the user.' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'email'              => array(
					'description' => __( 'The email address for the user.' ),
					'type'        => 'string',
					'format'      => 'email',
					'context'     => array( 'edit' ),
					'required'    => true,
				),
				'url'                => array(
					'description' => __( 'URL of the user.' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'description'        => array(
					'description' => __( 'Description of the user.' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'link'               => array(
					'description' => __( 'Author URL of the user.' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'locale'             => array(
					'description' => __( 'Locale for the user.' ),
					'type'        => 'string',
					'enum'        => array_merge( array( '', 'en_US' ), get_available_languages() ),
					'context'     => array( 'edit' ),
				),
				'nickname'           => array(
					'description' => __( 'The nickname for the user.' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'slug'               => array(
					'description' => __( 'An alphanumeric identifier for the user.' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'sanitize_slug' ),
					),
				),
				'registered_date'    => array(
					'description' => __( 'Registration date for the user.' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'roles'              => array(
					'description' => __( 'Roles assigned to the user.' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
					),
					'context'     => array( 'edit' ),
				),
				'password'           => array(
					'description' => __( 'Password for the user (never included).' ),
					'type'        => 'string',
					'context'     => array(), // Password is never displayed.
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'check_user_password' ),
					),
				),
				'capabilities'       => array(
					'description' => __( 'All capabilities assigned to the user.' ),
					'type'        => 'object',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'extra_capabilities' => array(
					'description' => __( 'Any extra capabilities assigned to the user.' ),
					'type'        => 'object',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'custom_fields'      => array(
					'description' => __( 'Display custom Register' ),
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'tabs'               => array(
					'description' => __( 'Get all items in user like course, lesson, quiz...' ),
					'type'        => 'array',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			),
		);

		if ( get_option( 'show_avatars' ) ) {
			$avatar_properties = array();

			$avatar_sizes = rest_get_avatar_sizes();

			foreach ( $avatar_sizes as $size ) {
				$avatar_properties[ $size ] = array(
					/* translators: %d: Avatar image size in pixels. */
					'description' => sprintf( __( 'Avatar URL with image size of %d pixels.' ), $size ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'embed', 'view', 'edit' ),
				);
			}

			$schema['properties']['avatar_urls'] = array(
				'description' => __( 'Avatar URLs for the user.' ),
				'type'        => 'object',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => $avatar_properties,
			);
		}

		$schema['properties']['meta'] = $this->meta->get_field_schema();

		return $this->add_additional_fields_schema( $schema );
	}

	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['context']['default'] = 'view';

		$query_params['exclude'] = array(
			'description' => __( 'Ensure result set excludes specific IDs.' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);

		$query_params['include'] = array(
			'description' => __( 'Limit result set to specific IDs.' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);

		$query_params['offset'] = array(
			'description' => __( 'Offset the result set by a specific number of items.' ),
			'type'        => 'integer',
		);

		$query_params['order'] = array(
			'default'     => 'asc',
			'description' => __( 'Order sort attribute ascending or descending.' ),
			'enum'        => array( 'asc', 'desc' ),
			'type'        => 'string',
		);

		$query_params['orderby'] = array(
			'default'     => 'name',
			'description' => __( 'Sort collection by object attribute.' ),
			'enum'        => array(
				'id',
				'include',
				'name',
				'registered_date',
				'slug',
				'include_slugs',
				'email',
				'url',
			),
			'type'        => 'string',
		);

		$query_params['slug'] = array(
			'description' => __( 'Limit result set to users with one or more specific slugs.' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
		);

		$query_params['roles'] = array(
			'description' => __( 'Limit result set to users matching at least one specific role provided. Accepts csv list or single role.' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
		);

		$query_params['who'] = array(
			'description' => __( 'Limit result set to users who are considered authors.' ),
			'type'        => 'string',
			'enum'        => array(
				'authors',
			),
		);

		/**
		 * Filters REST API collection parameters for the users controller.
		 *
		 * This filter registers the collection parameter, but does not map the
		 * collection parameter to an internal WP_User_Query parameter.  Use the
		 * `rest_user_query` filter to set WP_User_Query arguments.
		 *
		 * @since 4.7.0
		 *
		 * @param array $query_params JSON Schema-formatted collection parameters.
		 */
		return apply_filters( 'rest_user_collection_params', $query_params );
	}
}
