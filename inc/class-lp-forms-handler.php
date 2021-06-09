<?php
/**
 * Class LP_Forms_Handler
 *
 * Process action for submitting forms
 *
 * @since 4.0.0
 * @author ThimPress <nhamdv>
 */
class LP_Forms_Handler {

	/**
	 * Become a teacher form
	 */
	public static function process_become_teacher() {
		$args = array(
			'bat_name'    => isset( $_POST['bat_name'] ) ? wp_unslash( $_POST['bat_name'] ) : '',
			'bat_email'   => isset( $_POST['bat_email'] ) ? wp_unslash( $_POST['bat_email'] ) : '',
			'bat_phone'   => isset( $_POST['bat_phone'] ) ? wp_unslash( $_POST['bat_phone'] ) : '',
			'bat_message' => isset( $_POST['bat_message'] ) ? wp_unslash( $_POST['bat_message'] ) : '',
		);

		$result = array(
			'message' => array(),
			'result'  => 'success',
		);

		if ( ( empty( $args['bat_name'] ) ) && $result['result'] !== 'error' ) {
			$result = array(
				'message' => learn_press_get_message( __( 'Please enter a valid account username.', 'learnpress' ), 'error' ),
				'result'  => 'error',
			);
		}

		if ( ( empty( $args['bat_email'] ) || ! is_email( $args['bat_email'] ) ) && $result['result'] !== 'error' ) {
			$result = array(
				'message' => learn_press_get_message( __( 'Please provide a valid email address.', 'learnpress' ), 'error' ),
				'result'  => 'error',
			);
		}

		if ( ( ! email_exists( $args['bat_email'] ) ) && $result['result'] !== 'error' ) {
			$result = array(
				'message' => learn_press_get_message( __( 'Your email does not exist!', 'learnpress' ), 'error' ),
				'result'  => 'error',
			);
		}

		$result = apply_filters( 'learn-press/become-teacher-request-result', $result );

		if ( $result['result'] === 'success' ) {
			$result['message'][] = learn_press_get_message( __( 'Thank you! Your message has been sent.', 'learnpress' ), 'success' );
			$user                = get_user_by( 'email', $args['bat_email'] );

			update_user_meta( $user->ID, '_requested_become_teacher', 'yes' );
			do_action( 'learn-press/become-a-teacher-sent', $args );
		}

		learn_press_maybe_send_json( $result );
	}

	/**
	 * Process the login form.
	 *
	 * @throws Exception On login error.
	 * @author Thimpress <nhamdv>
	 */
	public static function process_login() {
		if ( ! LP_Request::verify_nonce( 'learn-press-login' ) ) {
			return;
		}

		if ( isset( $_POST['username'], $_POST['password'] ) ) {
			try {
				$username = trim( wp_unslash( $_POST['username'] ) );
				$password = $_POST['password'];
				$remember = LP_Request::get_string( 'rememberme' );

				if ( empty( $username ) ) {
					throw new Exception( '<strong>' . __( 'Error:', 'learnpress' ) . '</strong> ' . __( 'Username is required', 'learnpress' ) );
				}

				// On multisite, ensure user exists on current site, if not add them before allowing login.
				if ( is_multisite() ) {
					$user_data = get_user_by( is_email( $username ) ? 'email' : 'login', $username );

					if ( $user_data && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
						add_user_to_blog( get_current_blog_id(), $user_data->ID, 'customer' );
					}
				}

				$user = wp_signon(
					apply_filters(
						'learnpress_login_credentials',
						array(
							'user_login'    => $username,
							'user_password' => $password,
							'remember'      => $remember,
						)
					),
					is_ssl()
				);

				if ( is_wp_error( $user ) ) {
					throw new Exception( $user->get_error_message() );
				} else {
					if ( ! empty( $_POST['redirect'] ) ) {
						$redirect = wp_unslash( $_POST['redirect'] );
					} elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
						$redirect = wp_unslash( $_REQUEST['_wp_http_referer'] );
					} else {
						$redirect = LP_Request::get_redirect( learn_press_get_page_link( 'profile' ) );
					}

					wp_redirect( wp_validate_redirect( $redirect, learn_press_get_current_url() ) );
					exit();
				}
			} catch ( Exception $e ) {
				learn_press_add_message( $e->getMessage(), 'error' );
			}
		}
	}

	/**
	 * Process register form.
	 *
	 * @throws Exception On Error register.
	 * @author ThimPress <nhamdv>
	 */
	public static function process_register() {
		if ( ! LP_Request::verify_nonce( 'learn-press-register' ) ) {
			return;
		}

		$username         = isset( $_POST['reg_username'] ) ? wp_unslash( $_POST['reg_username'] ) : '';
		$email            = isset( $_POST['reg_email'] ) ? wp_unslash( $_POST['reg_email'] ) : '';
		$password         = isset( $_POST['reg_password'] ) ? wp_unslash( $_POST['reg_password'] ) : '';
		$confirm_password = isset( $_POST['reg_password2'] ) ? wp_unslash( $_POST['reg_password2'] ) : '';
		$first_name       = isset( $_POST['reg_first_name'] ) ? wp_unslash( $_POST['reg_first_name'] ) : '';
		$last_name        = isset( $_POST['reg_last_name'] ) ? wp_unslash( $_POST['reg_last_name'] ) : '';
		$display_name     = isset( $_POST['reg_display_name'] ) ? wp_unslash( $_POST['reg_display_name'] ) : '';
		$update_meta      = isset( $_POST['_lp_custom_register_form'] ) ? wp_unslash( $_POST['_lp_custom_register_form'] ) : array();

		try {
			$new_customer = self::learnpress_create_new_customer(
				sanitize_email( $email ),
				$username,
				$password,
				$confirm_password,
				array(
					'first_name'   => $first_name,
					'last_name'    => $last_name,
					'display_name' => $display_name,
				),
				$update_meta
			);

			if ( is_wp_error( $new_customer ) ) {
				throw new Exception( $new_customer->get_error_message() );
			}

			wp_set_current_user( $new_customer );
			wp_set_auth_cookie( $new_customer, true );

			learn_press_add_message( $username . __( ' was successfully created!', 'learnpress' ), 'success' );

			// Send email when check enable Instructor.
			if ( LP()->settings->get( 'instructor_registration' ) == 'yes' && isset( $_POST['become_teacher'] ) ) {
				update_user_meta( $new_customer, '_requested_become_teacher', 'yes' );
				do_action(
					'learn-press/become-a-teacher-sent',
					array(
						'bat_email'   => $email,
						'bat_phone'   => '',
						'bat_message' => apply_filters( 'learnpress_become_instructor_message', esc_html__( 'I need become a instructor', 'learnpress' ) ),
					)
				);

				learn_press_add_message( __( 'Your request become a instructor has been sent. We will get back to you soon!', 'learnpress' ), 'success' );
			}

			if ( ! empty( $_POST['redirect'] ) ) {
				$redirect = wp_sanitize_redirect( wp_unslash( $_POST['redirect'] ) );
			} elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
				$redirect = wp_unslash( $_REQUEST['_wp_http_referer'] );
			} else {
				$redirect = LP_Request::get_redirect( learn_press_get_page_link( 'profile' ) );
			}

			wp_redirect( wp_validate_redirect( $redirect, learn_press_get_current_url() ) );
			exit();

		} catch ( Exception $e ) {
			if ( $e->getMessage() ) {
				learn_press_add_message( $e->getMessage(), 'error' );
			}
		}
	}

	/**
	 * New create customer.
	 *
	 * @author ThimPress <nhamdv>
	 */
	public static function learnpress_create_new_customer( $email, $username = '', $password = '', $confirm_password = '', $args = array(), $update_meta = array() ) {
		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'registration-error-invalid-email', __( 'Please provide a valid email address.', 'learnpress' ) );
		}

		if ( email_exists( $email ) ) {
			return new WP_Error( 'registration-error-email-exists', apply_filters( 'learnpress_registration_error_email_exists', __( 'An account is already registered with your email address.', 'learnpress' ), $email ) );
		}

		$username = sanitize_user( $username );

		if ( empty( $username ) || ! validate_username( $username ) ) {
			return new WP_Error( 'registration-error-invalid-username', __( 'Please enter a valid account username.', 'learnpress' ) );
		}

		if ( username_exists( $username ) ) {
			return new WP_Error( 'registration-error-username-exists', __( 'An account is already registered with that username. Please choose another.', 'learnpress' ) );
		}

		if ( apply_filters( 'learnpress_registration_generate_password', false ) ) {
			$password = wp_generate_password();
		}

		if ( empty( $password ) ) {
			return new WP_Error( 'registration-error-missing-password', __( 'Please enter an account password.', 'learnpress' ) );
		}

		if ( strlen( $password ) < 6 ) {
			return new WP_Error( 'registration-error-short-password', __( 'Password is too short!', 'learnpress' ) );
		}

		if ( preg_match( '#\s+#', $password ) ) {
			return new WP_Error( 'registration-error-spacing-password', __( 'Password can not have spacing!', 'learnpress' ) );
		}

		if ( empty( $confirm_password ) ) {
			return new WP_Error( 'registration-error-missing-confirm-password', __( 'Please enter confirm password.', 'learnpress' ) );
		}

		if ( $password !== $confirm_password ) {
			return new WP_Error( 'registration-error-confirm-password', __( 'Confirmation password incorrect!.', 'learnpress' ) );
		}

		$custom_fields = LP()->settings()->get( 'register_profile_fields' );

		if ( $custom_fields && ! empty( $update_meta ) ) {
			foreach ( $custom_fields as $field ) {
				if ( ! isset( $field['id'] ) ) {
					return new WP_Error( 'registration-custom-exists', __( 'Please go to LearnPress > Settings and save again.', 'learnpress' ) );
				}
				if ( $field['required'] === 'yes' && empty( $update_meta[ $field['id'] ] ) ) {
					return new WP_Error( 'registration-custom-exists', $field['name'] . __( ' is required field.', 'learnpress' ) );
				}
			}
		}

		$errors = new WP_Error();

		do_action( 'learnpress_register_post', $username, $email, $errors );

		$errors = apply_filters( 'learnpress_registration_errors', $errors, $username, $email );

		if ( $errors->get_error_code() ) {
			return $errors;
		}

		$new_customer_data = apply_filters(
			'learnpress_new_customer_data',
			array_merge(
				$args,
				array(
					'user_login' => $username,
					'user_pass'  => $password,
					'user_email' => $email,
				)
			)
		);

		$customer_id = wp_insert_user( $new_customer_data );

		if ( ! empty( $update_meta ) ) {
			lp_user_custom_register_fields( $customer_id, $update_meta );
		}

		if ( is_wp_error( $customer_id ) ) {
			return $customer_id;
		}

		return $customer_id;
	}

	public static function update_user_data( $update_data, $update_meta ) {
		$user_id      = get_current_user_id();
		$current_user = get_user_by( 'id', $user_id );

		if ( empty( $update_data['user_email'] ) ) {
			return new WP_Error( 'exist_email', esc_html__( 'Email is required', 'learnpress' ) );
		}

		if ( empty( $update_data['display_name'] ) ) {
			return new WP_Error( 'exist_display_name', esc_html__( 'Display name is required', 'learnpress' ) );
		}

		if ( is_email( $update_data['display_name'] ) ) {
			return new WP_Error( 'error_display_name', esc_html__( 'Display name cannot be changed to email address due to privacy concern.', 'learnpress' ) );
		}

		if ( ! is_email( $update_data['user_email'] ) ) {
			return new WP_Error( 'error_email', esc_html__( 'Display name cannot be changed to email address due to privacy concern.', 'learnpress' ) );
		} elseif ( email_exists( $update_data['user_email'] ) && $update_data['user_email'] !== $current_user->user_email ) {
			return new WP_Error( 'error_email', esc_html__( 'This email address is already registered.', 'learnpress' ) );
		}

		$custom_fields = LP()->settings()->get( 'register_profile_fields' );

		if ( $custom_fields && ! empty( $update_meta ) ) {
			foreach ( $custom_fields as $field ) {
				if ( $field['required'] === 'yes' && empty( $update_meta[ $field['id'] ] ) ) {
					return new WP_Error( 'registration-custom-exists', $field['name'] . __( ' is required field.', 'learnpress' ) );
				}
			}
		}

		$update_data = apply_filters( 'learn-press/update-profile-basic-information-data', $update_data );

		$return = wp_update_user( $update_data );

		if ( ! empty( $update_meta ) ) {
			lp_user_custom_register_fields( $user_id, $update_meta );
		}

		if ( is_wp_error( $return ) ) {
			return $return;
		}

		return $return;
	}

	public static function init() {
		self::process_login();
		self::process_register();
	}
}

