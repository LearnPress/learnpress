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
			'bat_name'    => LP_Helper::sanitize_params_submitted( $_POST['bat_name'] ?? '' ),
			'bat_email'   => LP_Helper::sanitize_params_submitted( $_POST['bat_email'] ?? '' ),
			'bat_phone'   => LP_Helper::sanitize_params_submitted( $_POST['bat_phone'] ?? '' ),
			'bat_message' => LP_Helper::sanitize_params_submitted( $_POST['bat_message'] ?? '' ),
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
				$username = trim( LP_Helper::sanitize_params_submitted( $_POST['username'] ) );
				$password = LP_Helper::sanitize_params_submitted( $_POST['password'] );
				$remember = LP_Request::get_string( 'rememberme' );

				if ( empty( $username ) ) {
					throw new Exception( '<strong>' . __( 'Error:', 'learnpress' ) . '</strong> ' . __( 'A username is required', 'learnpress' ) );
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
						$url_redirect = LP_Helper::sanitize_params_submitted( $_POST['redirect'] );
					} elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
						$url_redirect = LP_Request::get_param( '_wp_http_referer' );
					} else {
						$url_redirect = LP_Request::get_redirect( learn_press_get_page_link( 'profile' ) );
					}
					$url_redirect = apply_filters( 'learn-press/login-redirect', $url_redirect, $user );

					$message_data = [
						'status'  => 'success',
						'content' => __( 'Login successfully!', 'learnpress' ),
					];
					learn_press_set_message( $message_data );
					wp_redirect( wp_validate_redirect( $url_redirect, LP_Helper::getUrlCurrent() ) );
					exit();
				}
			} catch ( Exception $e ) {
				$message_data = [
					'status'  => 'error',
					'content' => $e->getMessage(),
				];
				learn_press_set_message( $message_data );
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

		$username         = LP_Helper::sanitize_params_submitted( $_POST['reg_username'] ?? '' );
		$email            = LP_Helper::sanitize_params_submitted( $_POST['reg_email'] ?? '' );
		$password         = LP_Helper::sanitize_params_submitted( $_POST['reg_password'] ?? '' );
		$confirm_password = LP_Helper::sanitize_params_submitted( $_POST['reg_password2'] ?? '' );
		$first_name       = LP_Helper::sanitize_params_submitted( $_POST['reg_first_name'] ?? '' );
		$last_name        = LP_Helper::sanitize_params_submitted( $_POST['reg_last_name'] ?? '' );
		$display_name     = LP_Helper::sanitize_params_submitted( $_POST['reg_display_name'] ?? '' );
		$update_meta      = LP_Helper::sanitize_params_submitted( $_POST['_lp_custom_register_form'] ?? '' );

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

			// Send email become a teacher.
			$is_become_a_teacher = false;
			if ( LP_Settings::get_option( 'instructor_registration', 'no' ) == 'yes' && isset( $_POST['become_teacher'] ) ) {
				update_user_meta( $new_customer, '_requested_become_teacher', 'yes' );
				do_action(
					'learn-press/become-a-teacher-sent',
					array(
						'bat_email'   => $email,
						'bat_phone'   => '',
						'bat_message' => apply_filters( 'learnpress_become_instructor_message', esc_html__( 'I need to become an instructor', 'learnpress' ) ),
					)
				);

				$is_become_a_teacher = true;
			}

			/**
			 * Auto login user
			 * Must set code below after Send email become a teacher
			 * because 'none' check by "check_ajax_referer" will not valid for send mail background on WP_Async_Request
			 */
			wp_set_current_user( $new_customer );
			wp_set_auth_cookie( $new_customer, true );

			$message_success = $username . __( ' was successfully created!', 'learnpress' );

			if ( $is_become_a_teacher ) {
				$message_success .= '<br/>' . __( 'Your request to become an instructor has been sent. We will get back to you soon!', 'learnpress' );
			}

			$message_data = [
				'status'  => 'success',
				'content' => $message_success,
			];
			learn_press_set_message( $message_data );

			if ( ! empty( $_POST['redirect'] ) ) {
				$url_redirect = wp_sanitize_redirect( wp_unslash( $_POST['redirect'] ) );
			} elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
				$url_redirect = wp_unslash( $_REQUEST['_wp_http_referer'] );
			} else {
				$url_redirect = LP_Request::get_redirect( learn_press_get_page_link( 'profile' ) );
			}
			$url_redirect = apply_filters( 'learn-press/register-redirect', $url_redirect, $new_customer );
			wp_redirect( wp_validate_redirect( $url_redirect, LP_Helper::getUrlCurrent() ) );
			exit();

		} catch ( Throwable $e ) {
			$message_data = [
				'status'  => 'error',
				'content' => $e->getMessage(),
			];
			learn_press_set_message( $message_data );
		}
	}

	/**
	 * New create customer.
	 *
	 * @author ThimPress <nhamdv>
	 * @version 1.0.1
	 * @since 4.0.0
	 */
	public static function learnpress_create_new_customer( $email = '', $username = '', $password = '', $confirm_password = '', $args = array(), $update_meta = array() ) {
		try {
			$user_can_register = get_option( 'users_can_register' );
			if ( ! $user_can_register ) {
				throw new Exception( __( 'System WordPress does not allow register.', 'learnpress' ), 110 );
			}

			if ( empty( $email ) || ! is_email( $email ) ) {
				throw new Exception( __( 'Please provide a valid email address.', 'learnpress' ), 101 );
			}

			if ( email_exists( $email ) ) {
				throw new Exception( __( 'An account is already registered with your email address.', 'learnpress' ), 102 );
			}

			$username = sanitize_user( $username );

			if ( empty( $username ) || ! validate_username( $username ) ) {
				throw new Exception( __( 'Please enter a valid account username.', 'learnpress' ), 103 );
			}

			if ( username_exists( $username ) ) {
				throw new Exception(
					__( 'An account is already registered with that username. Please choose another one.', 'learnpress' ),
					104
				);
			}

			if ( apply_filters( 'learnpress_registration_generate_password', false ) ) {
				$password = wp_generate_password();
			}

			if ( empty( $password ) ) {
				throw new Exception( __( 'Please enter an account password.', 'learnpress' ), 105 );
			}

			if ( strlen( $password ) <= 5 ) {
				throw new Exception( __( 'Password must contain at least six characters.', 'learnpress' ), 106 );
			}

			if ( preg_match( '#\s+#', $password ) ) {
				throw new Exception( __( 'Password can not contain spaces!', 'learnpress' ), 107 );
			}

			if ( empty( $confirm_password ) ) {
				throw new Exception( __( 'Please enter confirm password.', 'learnpress' ), 108 );
			}

			if ( $password !== $confirm_password ) {
				throw new Exception( __( 'Password and Confirm Password does not match!', 'learnpress' ), 108 );
			}

			$custom_fields = LP_Settings::get_option( 'register_profile_fields', [] );
			if ( ! empty( $custom_fields ) ) {
				foreach ( $custom_fields as $field ) {
					if ( $field['required'] === 'yes' && empty( $update_meta[ $field['id'] ] ) ) {
						throw new Exception( $field['name'] . __( ' is required field.', 'learnpress' ), 109 );
					}
				}
			}

			do_action( 'lp/before_create_new_customer', $email, $username, $password, $confirm_password, $args, $update_meta );

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

			// Add hook registration_errors of WordPress
			$errors = null;
			$errors = apply_filters( 'registration_errors', $errors, $username, $email );
			if ( is_wp_error( $errors ) ) {
				throw new Exception( $errors->get_error_message() );
			}

			$customer_id = wp_insert_user( $new_customer_data );

			do_action( 'lp/after_create_new_customer', $email, $username, $password, $confirm_password, $args, $update_meta );

			if ( is_wp_error( $customer_id ) ) {
				throw new Exception( $customer_id->get_error_message() );
			} else {
				if ( ! empty( $update_meta ) ) {
					lp_user_custom_register_fields( $customer_id, $update_meta );
				}

				// Send mail.
				wp_new_user_notification( $customer_id, null, 'both' );
			}
		} catch ( Throwable $e ) {
			$code_str = '';
			switch ( $e->getCode() ) {
				case 101:
					$code_str = 'registration-error-invalid-email';
					break;
				case 102:
					$code_str = 'registration-error-email-exists';
					break;
				case 103:
					$code_str = 'registration-error-invalid-username';
					break;
				case 104:
					$code_str = 'registration-error-username-exists';
					break;
				case 105:
					$code_str = 'registration-error-missing-password';
					break;
				case 106:
					$code_str = 'registration-error-short-password';
					break;
				case 107:
					$code_str = 'registration-error-spacing-password';
					break;
				case 108:
					$code_str = 'registration-error-confirm-password';
					break;
				case 109:
					$code_str = 'registration-custom-required-field';
					break;
				case 110:
					$code_str = 'registration-not-allow';
					break;
				default:
					$code_str = $e->getMessage();
					break;
			}

			return new WP_Error( $code_str, $e->getMessage() );
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
			return new WP_Error( 'error_display_name', esc_html__( 'Due to privacy concerns, the display name cannot be changed to an email address.', 'learnpress' ) );
		}

		if ( ! is_email( $update_data['user_email'] ) ) {
			return new WP_Error( 'error_email', esc_html__( 'Due to privacy concerns, the display name cannot be changed to an email address.', 'learnpress' ) );
		} elseif ( email_exists( $update_data['user_email'] ) && $update_data['user_email'] !== $current_user->user_email ) {
			return new WP_Error( 'error_email', esc_html__( 'This email address is already registered.', 'learnpress' ) );
		}

		$custom_fields = LP_Profile::get_register_fields_custom();
		if ( $custom_fields && ! empty( $update_meta ) ) {
			foreach ( $custom_fields as $field ) {
				if ( $field['required'] !== 'yes' ) {
					continue;
				}

				$is_empty = empty( $update_meta[ $field['id'] ] );
				$is_empty = apply_filters( 'learn-press/profile/update-register-custom-field/is-require', $is_empty, $field );
				if ( $is_empty ) {
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

	public static function retrieve_password( $user_login ) {
		$login = isset( $user_login ) ? sanitize_user( wp_unslash( $user_login ) ) : '';

		if ( empty( $login ) ) {
			return new WP_Error( 'error_santize_login', esc_html__( 'Enter a username or email address.', 'learnpress' ) );
		} else {
			// Check on username first, as customers can use emails as usernames.
			$user_data = get_user_by( 'login', $login );
		}

		// If no user found, check if it login is email and lookup user based on email.
		if ( ! $user_data && is_email( $login ) && apply_filters( 'learnpress_get_username_from_email', true ) ) {
			$user_data = get_user_by( 'email', $login );
		}

		$errors = new WP_Error();

		do_action( 'lostpassword_post', $errors, $user_data );

		if ( $errors->get_error_code() ) {
			return $errors;
		}

		if ( ! $user_data ) {
			return new WP_Error( 'error_not_user', esc_html__( 'Invalid username or email.', 'learnpress' ) );
		}

		if ( is_multisite() && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
			return new WP_Error( 'error_not_user', esc_html__( 'Invalid username or email.', 'learnpress' ) );
		}

		// Redefining user_login ensures we return the right case in the email.
		$user_login = $user_data->user_login;

		do_action( 'retrieve_password', $user_login );

		$allow = apply_filters( 'allow_password_reset', true, $user_data->ID );

		if ( ! $allow ) {
			return new WP_Error( 'error_not_allow', esc_html__( 'Password reset is not allowed for this user.', 'learnpress' ) );
		} elseif ( is_wp_error( $allow ) ) {
			return $allow;
		}

		$key = get_password_reset_key( $user_data );

		if ( class_exists( 'LP_Email_Reset_Password' ) ) {
			$email = new LP_Email_Reset_Password();

			$email->handle(
				array(
					'reset_key'  => $key,
					'user_login' => $user_login,
				)
			);
		}

		return true;
	}

	public static function init() {
		self::process_login();
		self::process_register();
	}
}

