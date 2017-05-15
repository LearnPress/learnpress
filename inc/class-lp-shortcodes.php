<?php
/**
 * @author  ThimPress
 * @package LearnPress/Shortcodes
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LP_Shortcodes class
 */
class LP_Shortcodes {
	/**
	 * Init shortcodes
	 */
	public static function init() {
		$shortcodes = array(
			'learn_press_confirm_order'       => __CLASS__ . '::confirm_order',
			'learn_press_profile'             => __CLASS__ . '::profile',
			'learn_press_become_teacher_form' => __CLASS__ . '::become_teacher_form',
			'learn_press_login_form'          => __CLASS__ . '::login_form',
			'learn_press_checkout'            => __CLASS__ . '::checkout',
			'learn_press_recent_courses'      => __CLASS__ . '::recent_courses',
			'learn_press_featured_courses'    => __CLASS__ . '::featured_courses',
			'learn_press_popular_courses'     => __CLASS__ . '::popular_courses'
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}

		add_action( 'template_redirect', array( __CLASS__, 'auto_shortcode' ) );

	}

	public static function auto_shortcode( $template ) {
		if ( is_page() ) {
			global $post, $wp_query, $wp;
			$page_id = !empty( $wp_query->queried_object_id ) ?
				$wp_query->queried_object_id :
				( !empty( $wp_query->query_vars['page_id'] ) ? $wp_query->query_vars['page_id'] : - 1 );
			if ( $page_id == learn_press_get_page_id( 'checkout' ) ) {
				if ( !preg_match( '/\[learn_press_checkout\s?(.*)\]/', $post->post_content ) ) {
					$post->post_content .= '[learn_press_checkout]';
				}
			} elseif ( $page_id == learn_press_get_page_id( 'profile' ) ) {
				if ( empty( $wp->query_vars['user'] ) ) {
					$current_user = wp_get_current_user();
					if ( !empty( $current_user->user_login ) ) {
						$redirect = learn_press_get_endpoint_url( '', $current_user->user_login, learn_press_get_page_link( 'profile' ) );
						if ( $redirect && !learn_press_is_current_url( $redirect ) ) {
							wp_redirect( $redirect );
							die();
						}
					} else {
						if ( !preg_match( '/\[learn_press_login_form\s?(.*)\]/', $post->post_content ) ) {
							if ( !empty( $_REQUEST['redirect_to'] ) ) {
								$redirect = $_REQUEST['redirect_to'];
							} else {
								$redirect = '';
							}
							$post->post_content .= '[learn_press_login_form redirect="' . esc_attr( $redirect ) . '"]';
						}
					}
				} else {
					$query = array();
					parse_str( $wp->matched_query, $query );
					if ( empty( $query['view'] ) ) {
						$redirect = learn_press_user_profile_link( $wp->query_vars['user'] );
						if ( !empty( $redirect ) ) {
							wp_redirect( $redirect );
							die();
						}

					}
					if ( $query ) {

						$endpoints = learn_press_get_profile_endpoints();
						foreach ( $query as $k => $v ) {
							if ( ( $k == 'view' ) ) {
								if ( !$v ) {
									$v = reset( $endpoints );
								}
								if ( !in_array( $v, $endpoints) ) {
									learn_press_is_404();
								}
							}
							if ( !empty( $v ) ) {
								$wp->query_vars[$k] = $v;
							}
						}
					}
					if ( !preg_match( '/\[learn_press_profile\s?(.*)\]/', $post->post_content ) ) {
						$post->post_content .= '[learn_press_profile]';
					}

				}

			} elseif ( $page_id == learn_press_get_page_id( 'become_a_teacher' ) ) {
				if ( !preg_match( '/\[learn_press_become_teacher_form\s?(.*)\]/', $post->post_content ) ) {
					$post->post_content .= '[learn_press_become_teacher_form]';
				}
			}

			do_action( 'learn_press_auto_shortcode', $post, $template );
		}
		return $template;
	}

	public static function _login_form_bottom( $content, $args ) {
		if ( !( !empty( $args['context'] ) && $args['context'] == 'learn-press-login' ) ) {
			return;
		}
	}

	public static function wrapper_shortcode( $content ) {
		ob_start();
		learn_press_print_messages();
		$html = ob_get_clean();
		return '<div class="learnpress">' . $html . $content . '</div>';
	}

	/**
	 * Checkout form
	 *
	 * @param array
	 *
	 *
	 *
	 *
	 *
	 * @return string
	 */
	public static function checkout( $atts ) {
		global $wp;
		ob_start();

		if ( isset( $wp->query_vars['lp-order-received'] ) ) {

			self::order_received( $wp->query_vars['lp-order-received'] );

		} else {
			$cart = learn_press_get_checkout_cart();
			// Check cart has contents
			if ( $cart->is_empty() ) {
				learn_press_get_template( 'cart/empty-cart.php', array( 'checkout' => LP()->checkout() ) );
			} else {
				learn_press_get_template( 'checkout/form.php', array( 'checkout' => LP()->checkout() ) );
			}
		}
		return self::wrapper_shortcode( ob_get_clean() );
	}

	public static function recent_courses( $atts ) {

		$limit = $order_by = $order = '';

		$atts = shortcode_atts( array(
			'limit'    => 10,
			'order_by' => 'date', // select one of [date, title, status, comment_count]
			'order'    => 'DESC' // select on of [DESC, ASC]
		), $atts );

		extract( $atts );

		// Validation date
		$arr_orders_by = array( 'post_date', 'post_title', 'post_status', 'comment_count' );
		$arr_orders    = array( 'DESC', 'ASC' );
		$order         = strtoupper( $order );

		if ( !in_array( $order_by, $arr_orders_by ) || !in_array( 'post_' . $order_by, $arr_orders_by ) ) {
			$order_by = 'post_date';
		} else {
			if ( $order_by !== 'comment_count' ) {
				$order_by = 'post_' . $order_by;
			}
		}

		if ( !in_array( $order, $arr_orders ) ) {
			$order = 'DESC';
		}
		if ( !absint( $limit ) ) {
			$limit = 10;
		}

		global $wpdb;

		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT p.*
						FROM $wpdb->posts AS p
						WHERE p.post_type = %s
						AND p.post_status = %s
						ORDER BY p.{$order_by} {$order}
						LIMIT %d
					",
				LP_COURSE_CPT,
				'publish',
				(int) absint( $limit )
			)
		);

		ob_start();

		self::render_shortcode_archive( $posts );

		return self::wrapper_shortcode( ob_get_clean() );

	}

	public static function featured_courses( $atts ) {

		$limit = $order_by = $order = '';

		$atts = shortcode_atts( array(
			'limit'    => 10,
			'order_by' => 'date', // select one of [date, title, status, comment_count]
			'order'    => 'DESC' // select on of [DESC, ASC]
		), $atts );

		extract( $atts );

		// Validation date
		$arr_orders_by = array( 'post_date', 'post_title', 'post_status', 'comment_count' );
		$arr_orders    = array( 'DESC', 'ASC' );
		$order         = strtoupper( $order );

		if ( !in_array( $order_by, $arr_orders_by ) || !in_array( 'post_' . $order_by, $arr_orders_by ) ) {
			$order_by = 'post_date';
		} else {
			if ( $order_by !== 'comment_count' ) {
				$order_by = 'post_' . $order_by;
			}
		}

		if ( !in_array( $order, $arr_orders ) ) {
			$order = 'DESC';
		}
		if ( !absint( $limit ) ) {
			$limit = 10;
		}

		global $wpdb;

		$posts = $wpdb->get_results(
			$wpdb->prepare( "
				SELECT DISTINCT *
                    FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->postmeta} as pmeta ON p.ID=pmeta.post_id AND pmeta.meta_key = %s
                    WHERE p.post_type = %s
						AND p.post_status = %s
						AND meta_value = %s
                    ORDER BY p.{$order_by} {$order}
                    LIMIT %d
                ", '_lp_featured', LP_COURSE_CPT, 'publish', 'yes', absint( $limit )
			)
		);

		ob_start();

		self::render_shortcode_archive( $posts );

		return self::wrapper_shortcode( ob_get_clean() );

	}

	public static function popular_courses( $atts ) {

		$limit = $order_by = $order = '';

		$atts = shortcode_atts( array(
			'limit' => 10,
			'order' => 'DESC' // select on of [DESC, ASC]
		), $atts );

		extract( $atts );

		// Validation date
		$arr_orders = array( 'DESC', 'ASC' );
		$order      = strtoupper( $order );

		if ( !in_array( $order, $arr_orders ) ) {
			$order = 'DESC';
		}
		if ( !absint( $limit ) ) {
			$limit = 10;
		}

		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT po.*, count(*) as number_enrolled
					FROM {$wpdb->prefix}learnpress_user_items ui
					INNER JOIN {$wpdb->posts} po ON po.ID = ui.item_id
					WHERE ui.item_type = %s
						AND ( ui.status = %s OR ui.status = %s )
						AND po.post_status = %s
					GROUP BY ui.item_id
					ORDER BY ui.item_id {$order}
					LIMIT %d
				",
			LP_COURSE_CPT,
			'enrolled',
			'finished',
			'publish',
			absint( $limit )
		);

		$posts = $wpdb->get_results(
			$query
		);

		ob_start();

		self::render_shortcode_archive( $posts );

		return self::wrapper_shortcode( ob_get_clean() );

	}

	public static function render_shortcode_archive( $lp_posts = array() ) {
		global $post;
		if ( !empty( $lp_posts ) ) {
			do_action( 'learn_press_before_courses_loop' );

			learn_press_begin_courses_loop();

			foreach ( $lp_posts as $post ) {
				setup_postdata( $post );
				learn_press_get_template_part( 'content', 'course' );
			}

			learn_press_end_courses_loop();
		} else {
			learn_press_display_message( __( 'No course found.', 'learnpress' ), 'error' );

		}

		wp_reset_postdata();
	}

	private static function order_received( $order_id = 0 ) {

		learn_press_print_notices();

		$order = false;

		// Get the order
		$order_id  = absint( $order_id );
		$order_key = !empty( $_GET['key'] ) ? $_GET['key'] : '';

		if ( $order_id > 0 && ( $order = learn_press_get_order( $order_id ) ) && $order->post->post_status != 'trash' ) {
			if ( $order->order_key != $order_key )
				unset( $order );
		} else {
			learn_press_display_message( __( 'Invalid order!', 'learnpress' ), 'error' );
			return;
		}

		LP()->session->order_awaiting_payment = null;

		learn_press_get_template( 'checkout/order-received.php', array( 'order' => $order ) );
	}

	/**
	 * Shortcode content for "Confirm Order" page
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public static function confirm_order( $atts = null ) {
		$atts = shortcode_atts(
			array(
				'order_id' => !empty( $_REQUEST['order_id'] ) ? intval( $_REQUEST['order_id'] ) : 0
			),
			$atts
		);

		$order_id = null;

		extract( $atts );
		ob_start();

		$order = learn_press_get_order( $order_id );

		if ( $order ) {
			learn_press_get_template( 'order/confirm.php', array( 'order' => $order ) );
		}

		return self::wrapper_shortcode( ob_get_clean() );
	}

	/**
	 * Display a form let the user can be join as a teacher
	 *
	 * @param array|null
	 *
	 * @return string
	 */
	public static function become_teacher_form( $atts ) {
		$user    = learn_press_get_current_user();
		$message = '';
		$code    = 0;

		if ( !is_user_logged_in() ) {
			$message = __( "Please login to fill in this form.", 'learnpress' );
			$code    = 1;
		} elseif ( in_array( LP_TEACHER_ROLE, $user->user->roles ) ) {
			$message = __( "You are a teacher now.", 'learnpress' );
			$code    = 2;
		} elseif ( get_transient( 'learn_press_become_teacher_sent_' . $user->id ) == 'yes' ) {
			$message = __( 'Your request has been sent! We will get in touch with you soon!', 'learnpress' );
			$code    = 3;
		} elseif ( learn_press_user_maybe_is_a_teacher() ) {
			$message = __( 'Your role is allowed to create a course.', 'learnpress' );
			$code    = 4;
		}

		if ( !apply_filters( 'learn_press_become_a_teacher_display_form', true, $code, $message ) ) {
			return;
		}

		$atts   = shortcode_atts(
			array(
				'method'                     => 'post',
				'action'                     => '',
				'title'                      => __( 'Become a Teacher', 'learnpress' ),
				'description'                => __( 'Fill in your information and send us to become a teacher.', 'learnpress' ),
				'submit_button_text'         => __( 'Submit', 'learnpress' ),
				'submit_button_process_text' => __( 'Processing', 'learnpress' )
			),
			$atts
		);
		$fields = learn_press_get_become_a_teacher_form_fields();
		ob_start();
		$args = array_merge(
			array(
				'fields'  => $fields,
				'code'    => $code,
				'message' => $message
			),
			$atts
		);

		learn_press_get_template( 'global/become-teacher-form.php', $args );

		$html = ob_get_clean();

		LP_Assets::enqueue_script( 'become-teacher' );

		return self::wrapper_shortcode( $html );
	}

	public static function profile() {
		global $wp_query, $wp;
		if ( isset( $wp_query->query['user'] ) ) {
			$user = get_user_by( apply_filters( 'learn_press_get_user_requested_by', 'login' ), urldecode( $wp_query->query['user'] ) );
		} else {
			$user = get_user_by( 'id', get_current_user_id() );
		}
		$output = '';

		ob_start();
		if ( !$user ) {
			if ( empty( $wp_query->query['user'] ) ) {

			} else {
				learn_press_display_message( sprintf( __( 'The user %s is not available!', 'learnpress' ), $wp_query->query['user'] ), 'error' );
			}

		} else {
			$user = LP_User_Factory::get_user( $user->ID );
			$tabs = learn_press_user_profile_tabs( $user );
			if ( !empty( $wp->query_vars['view'] ) ) {
				$current = $wp->query_vars['view'];
			} else {
				$current = '';
			}
			if ( empty( $tabs[$current] ) && empty( $wp->query_vars['view'] ) ) {
				$tab_keys = array_keys( $tabs );
				$current  = reset( $tab_keys );
			}
			$_REQUEST['tab'] = $current;
			$_POST['tab']    = $current;
			$_GET['tab']     = $current;
			if ( !learn_press_current_user_can_view_profile_section( $current, $user ) ) {
				learn_press_get_template( 'profile/private-area.php' );
			} else {
				if ( !empty( $tabs ) && !empty( $tabs[$current] ) ) :
					learn_press_get_template( 'profile/index.php',
						array(
							'user'    => $user,
							'tabs'    => $tabs,
							'current' => $current
						)
					);
				else:
					if ( $wp->query_vars['view'] == LP()->settings->get( 'profile_endpoints.profile-order-details' ) ) {
						$order_id = 0;
						if ( !empty( $wp->query_vars['id'] ) ) {
							$order_id = $wp->query_vars['id'];
						}
						$order = learn_press_get_order( $order_id );
						if ( !$order ) {
							learn_press_display_message( __( 'Invalid order!', 'learnpress' ), 'error' );
						} else {
							learn_press_get_template( 'profile/order-details.php',
								array(
									'user'  => $user,
									'order' => $order
								)
							);
						}
					}
				endif;
			}
		}
		$output .= ob_get_clean();

		return self::wrapper_shortcode( $output );
	}

	static function login_form( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'redirect' => ''
			),
			$atts
		);
		add_filter( 'login_form_bottom', array( __CLASS__, 'login_form_bottom' ), 10, 2 );
		return self::wrapper_shortcode( learn_press_get_template_content( 'profile/login-form.php', $atts ) );
	}

	public static function login_form_bottom ($html, $args) {
		ob_start();
		?>
		<p>
			<a href="<?php echo wp_lostpassword_url(); ?>"><?php _e( 'Forgot password?', 'learnpress' ); ?></a>
			&nbsp;|&nbsp;
			<a href="<?php echo wp_registration_url(); ?>"><?php _e( 'Create new account', 'learnpress' ); ?></a>
		</p>
		<?php $html .= ob_get_clean();
		return $html;
	}
}

LP_Shortcodes::init();
