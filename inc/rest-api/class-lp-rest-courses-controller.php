<?php

class LP_REST_Courses_Controller extends LP_Abstract_REST_Controller {
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'courses';
		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'search'         => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_courses' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'enroll-course'  => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'enroll_courses' ),
					'permission_callback' => function() {
						return is_user_logged_in();
					},
				),
			),

			'(?P<key>[\w]+)' => array(
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

				ob_start(); ?>

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
	 * @param [type] $request
	 * @author Nhamdv <email@email.com>
	 */
	public function enroll_courses( $request ) {
		global $wpdb;

		if ( ! is_user_logged_in() ) {
			return;
		}

		try {
			if ( empty( absint( $request['id'] ) ) ) {
				throw new Exception( esc_html__( 'Error: No course avaliable!.', 'learnpress' ) );
			}

			$course_id = absint( $request['id'] );
			$course    = learn_press_get_course( $course_id );

			// Check if course has in order.
			$user_item_api = new LP_User_Item_CURD();
			$find_query    = array(
				'item_id' => $course_id,
				'user_id' => get_current_user_id(),
			);

			$course_items = $user_item_api->get_items_by( $find_query );

			// Auto enroll for Course.
			if ( $course_items && isset( $course_items[0]->user_item_id ) ) {
				if ( in_array( $course_items[0]->status, array( 'purchased' ) ) ) {
					$date            = new LP_Datetime();
					$course_duration = $course->get_duration();

					$fields = array(
						'graduation' => 'in-progress',
						'status'     => 'enrolled',
						'start_time' => $date->toSql( false ),
					);

					if ( $course_duration ) {
						$expiration                = new LP_Datetime( $date->getPeriod( $course_duration, false ) );
						$fields['expiration_time'] = $expiration->toSql( true );
					}

					learn_press_update_user_item_field(
						$fields,
						array(
							'user_item_id' => $course_items[0]->user_item_id,
						)
					);

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

				$cart->add_to_cart( $course_id, 1, array() );

				$order_id = $checkout->create_order();

				if ( is_wp_error( $order_id ) ) {
					throw new Exception( $order_id->get_error_message() );
				}

				$order = new LP_Order( $order_id );

				$order->payment_complete(); // Slow query in action 'learn-press/order/status-completed' send email.

				$cart->empty_cart();
			}

			$items    = $course->get_items( '', false );
			$redirect = ! empty( $items ) ? $course->get_item_link( $items[0] ) : get_the_permalink( $course_id );

			$response = array(
				'status'   => 'success',
				'message'  => esc_html__( 'Congrats! You enroll course successfully.', 'learnpress' ),
				'redirect' => apply_filters( 'learnpress/rest-api/enroll-course/redirect', $redirect ),
			);

		} catch ( Exception $e ) {
			$response = array(
				'status'  => 'error',
				'message' => $e->getMessage(),
			);
		}

		return rest_ensure_response( $response );
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
