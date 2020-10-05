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
		add_filter( 'learn-press/become-teacher-validate-field', array( __CLASS__, 'become_teacher_validate_field' ), 10, 3 );

		$fields      = learn_press_get_become_a_teacher_form_fields();
		$field_names = wp_list_pluck( $fields, 'id' );
		$args        = call_user_func_array( array( 'LP_Request', 'get_list' ), $field_names );

		$result = array(
			'message' => array(),
			'result'  => 'success',
		);

		foreach ( $fields as $field ) {
			$name     = $field['id'];
			$validate = apply_filters( 'learn-press/become-teacher-validate-field', $name, $field, $args[ $name ] );

			if ( is_wp_error( $validate ) ) {
				$result['message'][ $name ] = learn_press_get_message( $validate->get_error_message(), 'error' );
				$result['result']           = 'error';
			} elseif ( ! $validate ) {
				$result['message'][ $name ] = learn_press_get_message( sprintf( __( 'Field "%s" is required.', 'learnpress' ), $field['title'] ), 'error' );
				$result['result']           = 'error';
			}
		}

		remove_filter( 'learn-press/become-teacher-validate-field', array( __CLASS__, 'become_teacher_validate_field' ) );

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
	 * Basic filtering for become-teacher fields if it is required.
	 *
	 * @param string $name
	 * @param array  $field
	 * @param mixed  $value
	 *
	 * @return bool|WP_Error
	 */
	public static function become_teacher_validate_field( $name, $field, $value ) {
		try {
			$validate = ! ( ! empty( $field['required'] ) && $field['required'] && empty( $value ) );

			if ( ( 'bat_email' === $name ) && $validate ) {
				if ( ! $validate = get_user_by( 'email', $value ) ) {
					return new WP_Error( __( 'Your email does not exist!', 'learnpress' ) );
				}
			}
		} catch ( Exception $ex ) {
			$validate = new WP_Error( 'invalid_email', $ex->getMessage() );
		}

		return $validate;
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

		$fields      = LP_Shortcode_Login_Form::get_login_fields();
		$field_names = wp_list_pluck( $fields, 'id' );
		$args        = call_user_func_array( array( 'LP_Request', 'get_list' ), $field_names );

		if ( isset( $args['username'], $args['password'] ) ) {
			try {
				$username = $args['username'];
				$password = $args['password'];
				$remember = LP_Request::get_string( 'rememberme' );

				if ( empty( $username ) ) {
					throw new Exception( '<strong>' . __( 'Error:', 'learnpress' ) . '</strong> ' . __( 'Username is required', 'learnpress' ) );
				}

				// On multisite, ensure user exists on current site, if not add them before allowing login.
				if ( is_multisite() ) {
					$user_data = get_user_by( is_email( $username ) ? 'email' : 'login', $creds['user_login'] );

					if ( $user_data && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
						add_user_to_blog( get_current_blog_id(), $user_data->ID, 'customer' );
					}
				}

				$user = wp_signon(
					apply_filters(
						'learnpress_login_credentials',
						array(
							'user_login'    => $args['username'],
							'user_password' => $args['password'],
							'rememberme'    => LP_Request::get_string( 'rememberme' ),
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
				if ( $e->getMessage() ) {
					learn_press_add_message( $e->getMessage(), 'error' );
				}
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

		$fields      = LP_Shortcode_Register_Form::get_register_fields();
		$field_names = wp_list_pluck( $fields, 'id' );
		$args        = call_user_func_array( array( 'LP_Request', 'get_list' ), $field_names );
		$update_meta = isset( $_POST['_lp_custom_register_form'] ) ? $_POST['_lp_custom_register_form'] : array();

		try {
			$new_customer = self::learnpress_create_new_customer(
				sanitize_email( $args['reg_email'] ),
				$args['reg_username'],
				$args['reg_password'],
				array(
					'first_name'   => isset( $args['reg_first_name'] ) ? $args['reg_first_name'] : '',
					'last_name'    => isset( $args['reg_last_name'] ) ? $args['reg_last_name'] : '',
					'display_name' => isset( $args['reg_display_name'] ) ? $args['reg_display_name'] : '',
				),
				$update_meta
			);

			if ( is_wp_error( $new_customer ) ) {
				throw new Exception( $new_customer->get_error_message() );
			}

			wp_set_current_user( $new_customer );
			wp_set_auth_cookie( $new_customer, true );

			learn_press_add_message( $args['reg_username'] . __( ' was successfully created!', 'learnpress' ), 'success' );

			// Send email when check enable Instructor.
			if ( LP()->settings->get( 'instructor_registration' ) == 'yes' && isset( $_POST['become_teacher'] ) ) {
				update_user_meta( $new_customer, '_requested_become_teacher', 'yes' );
				do_action(
					'learn-press/become-a-teacher-sent',
					array(
						'bat_email'   => $args['reg_email'],
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
	public static function learnpress_create_new_customer( $email, $username = '', $password = '', $args = array(), $update_meta = array() ) {
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

		$custom_fields = LP()->settings()->get( 'register_profile_fields' );

		if ( $custom_fields && ! empty( $update_meta ) ) {
			foreach ( $custom_fields as $field ) {
				if ( $field['required'] === 'yes' && empty( $update_meta[ sanitize_key( $field['name'] ) ] ) ) {
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
				if ( $field['required'] === 'yes' && empty( $update_meta[ sanitize_key( $field['name'] ) ] ) ) {
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

