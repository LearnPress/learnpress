<?php

class LP_REST_Courses_Controller extends LP_Abstract_REST_Controller {
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'courses';
		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'search' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_courses' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
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
			)
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
			'success' => true
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
			'post_type'      => LP_COURSE_CPT
		);

		if ( $orderby ) {
			$args['orderby'] = $orderby;
			$args['order']   = $order;
		}

		$count_posts = new WP_Query( array_merge( $args, array( 'posts_per_page' => - 1, 'offset' => 0 ) ) );
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
						'small' => get_the_post_thumbnail_url( null, 'thumbnail' )
					);
				} else {
					$thumbnail = array(
						'small' => $course->get_image_url()
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
					'price_html' => $price_html
				);
			}
			wp_reset_postdata();
		}

		$response['results'] = array(
			'courses'   => $courses,
			'count'     => $query->post_count,
			'total'     => $count_posts->post_count,
			'page'      => $page,
			'num_pages' => $count_posts->post_count % $limit ? floor( $num_pages ) + 1 : absint( $num_pages )
		);

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
			'result' => $settings->get()
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
			'result' => $settings->get( $request['key'] )
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