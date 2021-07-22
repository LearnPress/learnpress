<?php

/**
 * Class LP_REST_Courses_Controller
 */
class LP_REST_Courses_Controller extends LP_Abstract_REST_Controller {
	/**
	 * LP_REST_Courses_Controller constructor.
	 */
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'courses';
		parent::__construct();
	}

	/**
	 * Register routes API
	 */
	public function register_routes() {
		$this->routes = array(
			'search'          => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_courses' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'purchase-course' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'purchase_course' ),
					'permission_callback' => '__return_true',
				),
			),
			'enroll-course'   => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'enroll_courses' ),
					'permission_callback' => '__return_true',
				),
			),
			'retake-course'   => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'retake_course' ),
					'permission_callback' => function () {
						return is_user_logged_in();
					},
					'schema'              => array(
						'type' => 'int',
					),
				),
			),
			'archive-course'  => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'archive_course' ),
					'permission_callback' => '__return_true',
				),
			),
			'(?P<key>[\w]+)'  => array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'learnpress' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			),
		);

		parent::register_routes();
	}

	public function check_admin_permission() {
		return LP_REST_Authentication::check_admin_permission();
	}

	public function archive_course( WP_REST_Request $request ) {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		$s        = isset( $request['s'] ) ? sanitize_text_field( $request['s'] ) : false;
		$page     = isset( $request['paged'] ) ? absint( wp_unslash( $request['paged'] ) ) : 1;
		$order    = isset( $request['order'] ) ? wp_unslash( $request['order'] ) : false;
		$orderby  = isset( $request['orderby'] ) ? wp_unslash( $request['orderby'] ) : false;
		$taxonomy = isset( $request['taxonomy'] ) ? wp_unslash( $request['taxonomy'] ) : false;
		$term_id  = isset( $request['term_id'] ) ? wp_unslash( $request['term_id'] ) : false;
		$user_id  = isset( $request['userID'] ) ? absint( wp_unslash( $request['userID'] ) ) : false;
		$limit    = LP_Settings::get_option( 'archive_course_limit', -1 );

		$args = array(
			'posts_per_page' => $limit,
			'paged'          => $page,
			'post_type'      => LP_COURSE_CPT,
		);

		if ( ! empty( $s ) ) {
			$args['s'] = $s;
		}

		if ( ! empty( $taxonomy ) && ! empty( $term_id ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			);

			$term_link = get_term_link( $term_id, $taxonomy );
		}

		if ( ! empty( $order ) ) {
			$args['order'] = $order;
		}

		if ( ! empty( $orderby ) ) {
			$args['orderby'] = $orderby;
		}

		if ( $user_id && learn_press_user_maybe_is_a_teacher( $user_id ) ) {
			$args['post_status'] = array( 'publish', 'private' );
		}

		$args = apply_filters( 'lp/rest-api/frontend/course/archive_course/query_args', $args, $request );

		$query = new WP_Query( $args );

		$num_pages = ! empty( $query->max_num_pages ) ? $query->max_num_pages : 1;

		$archive_link = get_post_type_archive_link( LP_COURSE_CPT );

		if ( isset( $term_link ) && ! is_wp_error( $term_link ) ) {
			$archive_link = $term_link;
		}

		$base = esc_url_raw( str_replace( 999999999, '%#%', get_pagenum_link( 999999999, false ) ) );

		global $wp;
		$base = str_replace( home_url( $wp->request ) . '/', $archive_link, $base );

		$response->data->pagination = learn_press_get_template_content(
			'loop/course/pagination.php',
			array(
				'total' => $num_pages,
				'paged' => $page,
				'base'  => $base,
			)
		);

		ob_start();

		if ( $query->have_posts() ) {
			global $post;

			while ( $query->have_posts() ) {
				$query->the_post();
				learn_press_get_template_part( 'content', 'course' );
			}

			wp_reset_postdata();
		} else {
			LP()->template( 'course' )->no_courses_found();
		}

		$response->status        = 'success';
		$response->data->content = ob_get_clean();

		return rest_ensure_response( apply_filters( 'lp/rest-api/frontend/course/archive_course/response', $response ) );
	}

	public function search_courses( $request ) {
		$s        = $request['s'];
		$page     = $request['page'];
		$limit    = $request['limit'];
		$offset   = $request['offset'];
		$order    = $request['order'];
		$orderby  = $request['orderby'];
		$response = array(
			'success' => true,
		);

		$limit  = $limit ? absint( $limit ) : 10;
		$offset = absint( $offset );
		$page   = $page ? absint( $page ) : 1;

		if ( empty( $offset ) ) {
			$offset = ( $page - 1 ) * $limit;
		}

		$args = array(
			's'              => $s,
			'posts_per_page' => $limit,
			'offset'         => $offset,
			'post_type'      => LP_COURSE_CPT,
		);

		if ( $orderby ) {
			$args['orderby'] = $orderby;
			$args['order']   = $order;
		}

		$count_posts = new WP_Query(
			array_merge(
				$args,
				array(
					'posts_per_page' => - 1,
					'offset'         => 0,
				)
			)
		);
		$query       = new WP_Query( $args );
		$num_pages   = $count_posts->post_count / $limit;
		$courses     = array();

		if ( $query->have_posts() ) {
			global $post;

			while ( $query->have_posts() ) {
				$query->the_post();
				$course = learn_press_get_course( $post->ID );

				if ( has_post_thumbnail() ) {
					$thumbnail = array(
						'full'  => get_the_post_thumbnail_url(),
						'small' => get_the_post_thumbnail_url( null, 'thumbnail' ),
					);
				} else {
					$thumbnail = array(
						'small' => $course->get_image_url(),
					);
				}

				ob_start();
				?>

				<div class="course-price">
					<?php if ( $course->has_sale_price() ) { ?>
						<span class="origin-price"> <?php echo $course->get_origin_price_html(); ?></span>
					<?php } ?>
					<span class="price"><?php echo $course->get_price_html(); ?></span>
				</div>

				<?php
				$price_html = ob_get_clean();

				$courses[] = array(
					'id'         => $post->ID,
					'title'      => get_the_title(),
					'url'        => get_permalink(),
					'content'    => get_the_content(),
					'thumbnail'  => $thumbnail,
					'author'     => $course->get_author_display_name(),
					'price_html' => $price_html,
				);
			}

			wp_reset_postdata();
		}

		$response['results'] = array(
			'courses'   => $courses,
			'count'     => $query->post_count,
			'total'     => $count_posts->post_count,
			'page'      => $page,
			'num_pages' => $count_posts->post_count % $limit ? floor( $num_pages ) + 1 : absint( $num_pages ),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Rest API for Enroll in single course.
	 *
	 * @param [] $request
	 *
	 * @throws Exception .
	 * @author Nhamdv
	 * @editor tungnx
	 */
	public function enroll_courses( $request ) {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			if ( empty( absint( $request['id'] ) ) ) {
				throw new Exception( esc_html__( 'Error: No course available!.', 'learnpress' ) );
			}

			$course_id = absint( $request['id'] );
			$course    = learn_press_get_course( $course_id );
			$user      = learn_press_get_current_user();

			if ( ! $course ) {
				throw new Exception( esc_html__( 'Invalid course!', 'learnpress' ) );
			}

			$can_enroll = $user->can_enroll_course( $course_id, false );

			if ( ! $can_enroll->check ) {
				throw new Exception( $can_enroll->message ?? esc_html__( 'Error: Cannot enroll course.', 'learnpress' ) );
			}

			// Check if course has in order.
			$user_item_api = new LP_User_Item_CURD();
			$find_query    = array(
				'item_id' => $course_id,
				'user_id' => get_current_user_id(),
			);

			$course_items = is_user_logged_in() ? $user_item_api->get_items_by( $find_query ) : false;

			// Auto enroll for Course.
			if ( $course_items && isset( $course_items[0]->user_item_id ) ) {
				if ( in_array( $course_items[0]->status, array( 'purchased' ) ) ) {
					$fields = array(
						'graduation' => 'in-progress',
						'status'     => 'enrolled',
						'start_time' => current_time( 'mysql', true ),
					);

					$update = learn_press_update_user_item_field(
						$fields,
						array(
							'user_item_id' => $course_items[0]->user_item_id,
						)
					);

					if ( ! $update ) {
						throw new Exception( esc_html__( 'Error: Can\'t Enroll course.', 'learnpress' ) );
					}
				}
			} else {
				LP()->session->set( 'order_awaiting_payment', '' );

				$cart     = LP()->cart;
				$checkout = LP_Checkout::instance();

				if ( ! learn_press_enable_cart() ) {
					$order_awaiting_payment = LP()->session->order_awaiting_payment;
					$cart->empty_cart();
					LP()->session->order_awaiting_payment = $order_awaiting_payment;
				}

				$cart_id = $cart->add_to_cart( $course_id, 1, array() );

				if ( ! $cart_id ) {
					throw new Exception( esc_html__( 'Error: Can\'t add Course to cart.', 'learnpress' ) );
				}

				if ( is_user_logged_in() ) {
					$order_id = $checkout->create_order();

					if ( is_wp_error( $order_id ) ) {
						throw new Exception( $order_id->get_error_message() );
					}

					$order = new LP_Order( $order_id );

					$order->payment_complete(); // Slow query in action 'learn-press/order/status-completed' send email.

					$cart->empty_cart();
				}
			}

			if ( is_user_logged_in() ) {
				$response->status = 'success';
				// Course has no items
				$response->message = esc_html__(
					'Congrats! You enroll course successfully. Redirecting...',
					'learnpress'
				);

				$response->data->redirect = $course->get_redirect_url_after_enroll();

				if ( empty( $course->get_item_ids() ) ) {
					$response->data->redirect = get_permalink( $course->get_id() );
				}

				// Send mail when course enrolled
				$user->enrolled_sendmail( get_current_user_id(), $course_id );
			} else {
				$response->message        = esc_html__( 'Redirecting...', 'learnpress' );
				$response->data->redirect = learn_press_get_page_link( 'checkout' );
			}
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Rest API for Purchase course in single course.
	 *
	 * @param WP_REST_Request $request .
	 *
	 * @return WP_REST_Response|WP_Error
	 * @throws Exception .
	 * @author Nhamdv
	 */
	public function purchase_course( WP_REST_Request $request ) {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();
		$params         = $request->get_params();

		try {
			$course_id             = $params['id'];
			$allow_repurchase_type = $params['repurchaseType'] ?? false;

			if ( ! $course_id ) {
				throw new Exception( __( 'Error: Invalid Course ID.', 'learnpress' ) );
			}

			$course = learn_press_get_course( $course_id );

			if ( ! $course ) {
				throw new Exception( __( 'Error: No Course available.', 'learnpress' ) );
			}

			$user = learn_press_get_current_user();

			if ( ! $user->can_purchase_course( $course_id ) ) {
				throw new Exception( esc_html__( 'Error: Cannot purchase course!.', 'learnpress' ) );
			}

			global $wpdb;

			// Allow Repurchase.
			$latest_user_item_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(user_item_id) user_item_id
					FROM {$wpdb->learnpress_user_items}
					WHERE ref_type = %s
					AND item_type = %s
					AND item_id = %d
					AND user_id = %d",
					LP_ORDER_CPT,
					LP_COURSE_CPT,
					$course_id,
					$user->get_id()
				)
			);

			if ( $course->allow_repurchase() && ! empty( $latest_user_item_id ) && empty( $allow_repurchase_type ) ) {
				if ( $course->allow_repurchase_course_option() === 'popup' ) {
					ob_start();
					?>
					<div class="lp_allow_repuchase_select">
						<ul>
							<li>
								<label>
									<input name="_lp_allow_repurchase_type" value="reset" type="radio" checked="checked" />
									<?php esc_html_e( 'Reset Course progress', 'learnpress' ); ?>
								</label>
							</li>
							<li>
								<label>
									<input name="_lp_allow_repurchase_type" value="keep" type="radio" />
									<?php esc_html_e( 'Continue Course progress', 'learnpress' ); ?>
								</label>
							</li>
						</ul>
					</div>
					<?php
					$response->data->html       = ob_get_clean();
					$response->data->type       = 'allow_repurchase';
					$response->data->titlePopup = esc_html__( 'Repurchase Options', 'learnpress' );
					$response->status           = 'success';

					return rest_ensure_response( $response );
				} else {
					learn_press_update_user_item_meta( $latest_user_item_id, '_lp_allow_repurchase_type', $course->allow_repurchase_course_option() );
				}
			}

			LP()->session->set( 'order_awaiting_payment', '' );

			$cart     = LP()->cart;
			$checkout = LP_Checkout::instance();

			if ( ! learn_press_enable_cart() ) {
				$order_awaiting_payment = LP()->session->order_awaiting_payment;
				$cart->empty_cart();
				LP()->session->order_awaiting_payment = $order_awaiting_payment;
			}

			do_action( 'learnpress/rest-api/courses/purchase/before-add-to-cart' );

			$cart_id = $cart->add_to_cart( $course_id, 1, array() );

			if ( ! $cart_id ) {
				throw new Exception( __( 'Error: Can\'t add Course to cart.', 'learnpress' ) );
			}

			if ( ! empty( $allow_repurchase_type ) ) {
				learn_press_update_user_item_meta( $latest_user_item_id, '_lp_allow_repurchase_type', $allow_repurchase_type );
			}

			$redirect = apply_filters(
				'learnpress/rest-api/courses/purchase/redirect',
				learn_press_get_page_link( 'checkout' ),
				$course_id,
				$cart_id
			);

			if ( empty( $redirect ) ) {
				throw new Exception( __( 'Error: Please setup page for checkout.', 'learnpress' ) );
			}

			$response->status         = 'success';
			$response->message        = sprintf(
				esc_html__( '"%s" has been added to your cart.', 'learnpress' ),
				$course->get_title()
			);
			$response->data->redirect = $redirect;
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Rest API for retake course.
	 *
	 * @param WP_REST_Request $request .
	 *
	 * @throws Exception .
	 */
	public function retake_course( WP_REST_Request $request ) {
		$response = new LP_REST_Response();

		try {
			$course_id = $request->get_param( 'id' );

			if ( ! $course_id ) {
				throw new Exception( __( 'Invalid params', 'learnpress' ) );
			}

			$course = learn_press_get_course( $course_id );

			if ( ! $course ) {
				throw new Exception( __( 'Invalid course', 'learnpress' ) );
			}

			$user = LP_Global::user();

			// if ( ! is_user_logged_in() ) {
			// throw new Exception( esc_html__( 'Please login!', 'learnpress' ) );
			// }

			$can_retry = $user->can_retry_course( $course_id );

			if ( ! $can_retry ) {
				throw new Exception( __( 'You can\'t retry course', 'learnpress' ) );
			}

			$user_course_data = $user->get_course_data( $course_id );

			// Up retaken.
			$user_course_data->increase_retake_count();

			// Set status, start_time, end_time of course to enrolled.
			$user_course_data->set_status( LP_COURSE_ENROLLED )
							 ->set_start_time( current_time( 'mysql', true ) )
							 ->set_end_time( '' )
							 ->set_graduation( 'in-progress' )
							 ->update();

			// Remove items' course user learned.
			$filter_remove            = new LP_User_Items_Filter();
			$filter_remove->parent_id = $user_course_data->get_user_item_id();
			$filter_remove->user_id   = $user_course_data->get_user_id();
			$filter_remove->limit     = - 1;
			LP_User_Items_DB::getInstance()->remove_items_of_user_course( $filter_remove );

			// Create new result in table learnpress_user_item_results.
			LP_User_Items_Result_DB::instance()->insert( $user_course_data->get_user_item_id() );

			$response->status             = 'success';
			$response->message            = esc_html__( 'Now you can learn this course', 'learnpress' );
			$response->data->url_redirect = $course->get_redirect_url_after_enroll();
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$settings = LP()->settings();
		$response = array(
			'result' => $settings->get(),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_item( $request ) {
		$settings = LP()->settings();
		$response = array(
			'result' => $settings->get( $request['key'] ),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function update_item( $request ) {
		$response = array();
		$settings = LP()->settings();
		$option   = $settings->get( $request['key'] );

		$settings->update( $request['key'], $request['data'] );
		$new_option = $settings->get( $request['key'] );
		$success    = maybe_serialize( $option ) !== maybe_serialize( $new_option );

		$response['success'] = $success;
		$response['result']  = $success ? $new_option : $option;

		return rest_ensure_response( $response );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function delete_item( $request ) {
		$response = array();

		return rest_ensure_response( $response );
	}

}
