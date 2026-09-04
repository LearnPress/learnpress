<?php

use LearnPress\Models\UserItems\UserCourseModel;

/**
 * REST API for checkout / payment methods.
 *
 * @package LearnPress/JWT/RESTAPI
 */
class LP_Jwt_Checkout_V1_Controller extends LP_REST_Jwt_Controller {
	protected $namespace = 'learnpress/v1';

	protected $rest_base = 'checkout';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/payment-methods',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_payment_methods' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'process_checkout' ),
					'permission_callback' => array( $this, 'checkout_permissions_check' ),
					'args'                => array(
						'course_id'      => array(
							'required' => true,
							'type'     => 'integer',
						),
						'payment_method' => array(
							'required' => false,
							'type'     => 'string',
						),
						'notes'          => array(
							'required' => false,
							'type'     => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/paypal/create-order',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'paypal_create_order' ),
					'permission_callback' => array( $this, 'checkout_permissions_check' ),
					'args'                => array(
						'course_id' => array(
							'required' => true,
							'type'     => 'integer',
						),
						'notes'     => array(
							'required' => false,
							'type'     => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/paypal/capture',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'paypal_capture' ),
					'permission_callback' => array( $this, 'checkout_permissions_check' ),
					'args'                => array(
						'paypal_order_id' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				),
			)
		);
	}

	public function checkout_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You must be logged in to checkout.', 'learnpress' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * List all enabled payment gateways.
	 */
	public function list_payment_methods( $request ) {
		$response = new LP_REST_Response();
		$gateways = LP_Gateways::instance()->get_available_payment_gateways();

		$methods = array();
		foreach ( $gateways as $gateway ) {
			if ( ! is_object( $gateway ) ) {
				continue;
			}

			$method = array(
				'id'          => $gateway->get_id(),
				'title'       => $gateway->title ? wp_strip_all_tags( $gateway->title ) : $gateway->get_method_title(),
				'description' => $gateway->description ? wp_strip_all_tags( $gateway->description ) : $gateway->get_method_description(),
				'icon'        => esc_url_raw( (string) $gateway->icon ),
				'is_default'  => (bool) ( $gateway->is_selected ?? false ),
				'config'      => new stdClass(),
			);

			// Expose PayPal env so the frontend can render Smart Buttons via @paypal/react-paypal-js.
			if ( $gateway->get_id() === 'paypal' ) {
				$paypal_settings = LP_Settings::instance()->get_group( 'paypal' );
				$method['config'] = array(
					'client_id' => (string) $paypal_settings->get( 'app_client_id', '' ),
					'sandbox'   => $paypal_settings->get( 'paypal_sandbox', 'no' ) === 'yes',
					'currency'  => learn_press_get_currency(),
				);
			}

			$methods[] = $method;
		}

		$response->status = 'success';
		$response->data   = $methods;

		return rest_ensure_response( $response );
	}

	/**
	 * Create an order for the given course + payment method.
	 *
	 * Body: { course_id, payment_method?, notes? }
	 * Returns: { status, message, data: { order_id, redirect } }
	 */
	public function process_checkout( $request ) {
		$response = new LP_REST_Response();

		try {
			$course_id          = absint( $request['course_id'] );
			$payment_method_str = isset( $request['payment_method'] ) ? sanitize_text_field( $request['payment_method'] ) : '';
			$notes              = isset( $request['notes'] ) ? sanitize_textarea_field( $request['notes'] ) : '';

			if ( ! $course_id ) {
				throw new Exception( esc_html__( 'Missing course_id.', 'learnpress' ) );
			}

			$course = learn_press_get_course( $course_id );
			if ( ! $course ) {
				throw new Exception( esc_html__( 'Invalid course.', 'learnpress' ) );
			}

			$user_id = get_current_user_id();

			// Check if already enrolled.
			$userCourse = UserCourseModel::find( $user_id, $course_id, true );
			if ( $userCourse && $userCourse->get_status() ) {
				throw new Exception( esc_html__( 'You are already enrolled in this course.', 'learnpress' ) );
			}

			$cart     = LearnPress::instance()->cart;
			$checkout = LP_Checkout::instance();

			// Single-course checkout — reset cart to avoid stale items.
			$cart->empty_cart();
			$cart_id = $cart->add_to_cart( $course_id, 1, array() );
			if ( ! $cart_id ) {
				throw new Exception( esc_html__( 'Could not add the course to the cart.', 'learnpress' ) );
			}

			$checkout->payment_method_str = $payment_method_str;
			$checkout->order_comment      = $notes;

			$needs_payment = $cart->needs_payment();

			if ( $needs_payment ) {
				if ( ! $payment_method_str ) {
					throw new Exception( esc_html__( 'No payment method selected.', 'learnpress' ) );
				}

				$available = LP_Gateways::instance()->get_available_payment_gateways();
				if ( ! isset( $available[ $payment_method_str ] ) ) {
					throw new Exception( esc_html__( 'Invalid payment method.', 'learnpress' ) );
				}

				$checkout->payment_method = $available[ $payment_method_str ];
			}

			// Match LP core's pattern: reuse the pending order stashed in the LP
			// session (`order_awaiting_payment`) to avoid creating orphans on retry.
			$order_id = $this->reuse_or_create_order( $checkout, $course_id );

			$redirect          = '';
			$requires_redirect = false;
			$payment_label     = '';

			if ( $checkout->payment_method instanceof LP_Gateway_Abstract ) {
				$payment_label  = $checkout->payment_method->get_title();
				$payment_result = $checkout->payment_method->process_payment( $order_id );

				// Order status after process_payment: PENDING means the gateway needs an
				// external redirect (e.g. PayPal/Stripe) to actually take payment.
				// PROCESSING / COMPLETED means the gateway settled the order in our DB
				// (e.g. offline, sandbox) and no external redirect is necessary.
				$order_after       = new LP_Order( $order_id );
				$requires_redirect = ( $order_after->get_status() === 'pending' );

				if ( $requires_redirect && ! empty( $payment_result['redirect'] ) ) {
					$redirect = $payment_result['redirect'];
				}
			} else {
				// Free course — complete the order directly. No external redirect.
				$order_free = new LP_Order( $order_id );
				$order_free->payment_complete();
				$payment_label = esc_html__( 'Free', 'learnpress' );
			}

			$cart->empty_cart();
			// Order has been settled (free/offline/sandbox). Drop the session pointer
			// so the next checkout starts a fresh order. External redirects (PayPal
			// browser flow) keep it so a retry can reuse the still-pending order.
			if ( ! $requires_redirect ) {
				$this->clear_awaiting_session();
			}

			$order = new LP_Order( $order_id );

			$response->status                  = 'success';
			$response->message                 = esc_html__( 'Order created.', 'learnpress' );
			$response->data                    = new stdClass();
			$response->data->order_id          = $order_id;
			$response->data->redirect          = $redirect;
			$response->data->requires_redirect = $requires_redirect;
			$response->data->order             = $this->build_order_summary( $order, $payment_label );
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * POST /checkout/paypal/create-order
	 * Creates a pending LP order + a PayPal v2 order, returns the PayPal order id for SDK use.
	 */
	public function paypal_create_order( $request ) {
		$response = new LP_REST_Response();

		try {
			$course_id = absint( $request['course_id'] );
			$notes     = isset( $request['notes'] ) ? sanitize_textarea_field( $request['notes'] ) : '';

			if ( ! $course_id ) {
				throw new Exception( esc_html__( 'Missing course_id.', 'learnpress' ) );
			}

			$course = learn_press_get_course( $course_id );
			if ( ! $course ) {
				throw new Exception( esc_html__( 'Invalid course.', 'learnpress' ) );
			}

			$user_id    = get_current_user_id();
			$userCourse = UserCourseModel::find( $user_id, $course_id, true );
			if ( $userCourse && $userCourse->get_status() ) {
				throw new Exception( esc_html__( 'You are already enrolled in this course.', 'learnpress' ) );
			}

			$available = LP_Gateways::instance()->get_available_payment_gateways();
			if ( ! isset( $available['paypal'] ) ) {
				throw new Exception( esc_html__( 'PayPal is not enabled.', 'learnpress' ) );
			}
			$paypal_gateway = $available['paypal'];

			$cart     = LearnPress::instance()->cart;
			$checkout = LP_Checkout::instance();

			$cart->empty_cart();
			$cart_id = $cart->add_to_cart( $course_id, 1, array() );
			if ( ! $cart_id ) {
				throw new Exception( esc_html__( 'Could not add the course to the cart.', 'learnpress' ) );
			}

			$checkout->payment_method_str = 'paypal';
			$checkout->order_comment      = $notes;
			$checkout->payment_method     = $paypal_gateway;

			// Same session-based reuse pattern as LP_Checkout::process_checkout.
			$lp_order_id = $this->reuse_or_create_order( $checkout, $course_id );
			$lp_order    = new LP_Order( $lp_order_id );

			// Create PayPal order via v2 API directly so we can read the id (for SDK).
			$data_token = $paypal_gateway->get_access_token();
			if ( ! isset( $data_token->access_token ) || ! isset( $data_token->token_type ) ) {
				throw new Exception( esc_html__( 'Invalid PayPal access token.', 'learnpress' ) );
			}

			$params = $paypal_gateway->get_order_args( $lp_order );

			$paypal_response = wp_remote_post(
				$paypal_gateway->api_url . 'v2/checkout/orders',
				array(
					'body'    => json_encode( $params ),
					'headers' => array(
						'Authorization' => $data_token->token_type . ' ' . $data_token->access_token,
						'Content-Type'  => 'application/json',
					),
					'timeout' => 60,
				)
			);

			$paypal_result = LP_Helper::json_decode( wp_remote_retrieve_body( $paypal_response ) );

			if ( isset( $paypal_result->error ) ) {
				throw new Exception( $paypal_result->error_description ?? 'PayPal error' );
			}
			if ( isset( $paypal_result->name ) && isset( $paypal_result->details[0] ) ) {
				throw new Exception( $paypal_result->details[0]->description );
			}
			if ( empty( $paypal_result->id ) ) {
				throw new Exception( esc_html__( 'Invalid PayPal order response.', 'learnpress' ) );
			}

			$cart->empty_cart();

			$response->status                = 'success';
			$response->data                  = new stdClass();
			$response->data->lp_order_id     = $lp_order_id;
			$response->data->paypal_order_id = $paypal_result->id;
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * POST /checkout/paypal/capture
	 * Captures an authorized PayPal order and marks the matching LP order completed.
	 */
	public function paypal_capture( $request ) {
		$response = new LP_REST_Response();

		try {
			$paypal_order_id = sanitize_text_field( $request['paypal_order_id'] );
			if ( ! $paypal_order_id ) {
				throw new Exception( esc_html__( 'Missing paypal_order_id.', 'learnpress' ) );
			}

			$available = LP_Gateways::instance()->get_available_payment_gateways();
			if ( ! isset( $available['paypal'] ) ) {
				throw new Exception( esc_html__( 'PayPal is not enabled.', 'learnpress' ) );
			}
			$paypal_gateway = $available['paypal'];

			$data_token_str = LP_Settings::get_option( 'paypal_token' );
			$data_token     = json_decode( $data_token_str );
			if ( ! isset( $data_token->access_token ) || ! isset( $data_token->token_type ) ) {
				$data_token = $paypal_gateway->get_access_token();
			}

			$capture_response = wp_remote_post(
				$paypal_gateway->api_url . 'v2/checkout/orders/' . $paypal_order_id . '/capture',
				array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => $data_token->token_type . ' ' . $data_token->access_token,
					),
					'timeout' => 60,
				)
			);

			$code        = wp_remote_retrieve_response_code( $capture_response );
			$body        = wp_remote_retrieve_body( $capture_response );
			$transaction = LP_Helper::json_decode( $body );

			if ( $code !== 201 || ! isset( $transaction->status ) || $transaction->status !== 'COMPLETED' ) {
				$msg = isset( $transaction->details[0]->description )
					? $transaction->details[0]->description
					: esc_html__( 'Could not capture PayPal payment.', 'learnpress' );
				throw new Exception( $msg );
			}

			$lp_order_id = 0;
			if ( isset( $transaction->purchase_units[0]->payments->captures[0]->custom_id ) ) {
				$lp_order_id = absint( $transaction->purchase_units[0]->payments->captures[0]->custom_id );
			}
			if ( ! $lp_order_id ) {
				throw new Exception( esc_html__( 'Could not resolve order.', 'learnpress' ) );
			}

			$lp_order = new LP_Order( $lp_order_id );

			// Defense in depth: only allow the LP order's owner to finalize it.
			if ( (int) $lp_order->get_user_id() !== get_current_user_id() ) {
				throw new Exception( esc_html__( 'You are not allowed to capture this order.', 'learnpress' ) );
			}

			$lp_order->update_status( LP_ORDER_COMPLETED );
			$this->clear_awaiting_session();

			$response->status      = 'success';
			$response->message     = esc_html__( 'Payment captured.', 'learnpress' );
			$response->data        = new stdClass();
			$response->data->order = $this->build_order_summary( $lp_order, $paypal_gateway->get_title() );
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Per-user meta key for tracking the in-flight LP order awaiting payment.
	 *
	 * LP core uses LP_Session for this. In a JWT/headless context the session
	 * isn't reliable (it inits at plugins_loaded before JWT auth fires, and the
	 * proxy strips cookies), so we use user_meta instead — same semantics,
	 * scoped per user, persistent across requests.
	 */
	private const AWAITING_META_KEY = '_lp_jwt_order_awaiting_payment';

	private function reuse_or_create_order( LP_Checkout $checkout, int $course_id ): int {
		$user_id = get_current_user_id();

		$existing_id = (int) get_user_meta( $user_id, self::AWAITING_META_KEY, true );
		if ( $existing_id ) {
			$existing = learn_press_get_order( $existing_id );
			if ( $existing && $existing->has_status( array( 'pending', 'failed', 'cancelled' ) ) ) {
				$contains_course = false;
				foreach ( (array) $existing->get_items() as $oi ) {
					if ( isset( $oi['course_id'] ) && (int) $oi['course_id'] === $course_id ) {
						$contains_course = true;
						break;
					}
				}
				if ( $contains_course ) {
					// User may have switched payment method between attempts — sync it.
					if ( $checkout->payment_method instanceof LP_Gateway_Abstract ) {
						$existing->set_data( 'payment_method', $checkout->payment_method->get_id() );
						$existing->set_data( 'payment_method_title', $checkout->payment_method->get_title() );
						$existing->save();
					}
					return $existing_id;
				}
			}
			// Stale — clear so a fresh order is created.
			delete_user_meta( $user_id, self::AWAITING_META_KEY );
		}

		$new_id = $checkout->create_order();
		if ( is_wp_error( $new_id ) ) {
			throw new Exception( $new_id->get_error_message() );
		}
		update_user_meta( $user_id, self::AWAITING_META_KEY, $new_id );
		return $new_id;
	}

	/**
	 * Clear the awaiting-payment marker once an order has been settled.
	 */
	private function clear_awaiting_session(): void {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			delete_user_meta( $user_id, self::AWAITING_META_KEY );
		}
	}

	/**
	 * Build the order summary payload shared by /checkout and /checkout/paypal/capture.
	 */
	private function build_order_summary( LP_Order $order, string $payment_label ): array {
		$status     = $order->get_status();
		$items_raw  = $order->get_items();
		$items_data = array();
		foreach ( (array) $items_raw as $oi ) {
			$items_data[] = array(
				'name'      => isset( $oi['name'] ) ? wp_strip_all_tags( (string) $oi['name'] ) : '',
				'course_id' => isset( $oi['course_id'] ) ? absint( $oi['course_id'] ) : 0,
				'total'     => isset( $oi['total'] ) ? (float) $oi['total'] : 0,
			);
		}

		return array(
			'id'              => $order->get_id(),
			'number'          => $order->get_order_number(),
			'status'          => $status,
			'status_label'    => LP_Order::get_status_label( $status ),
			'total'           => (float) $order->get_total(),
			'total_formatted' => $order->get_formatted_order_total(),
			'subtotal'        => (float) $order->get_subtotal(),
			'created_at'      => $order->get_order_date( 'c' ),
			'payment_method'  => $payment_label,
			'items'           => $items_data,
		);
	}
}
