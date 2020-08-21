<?php

/**
 * Class LP_Forms_Handler
 *
 * Process action for submitting forms
 *
 * @since 3.0
 */
class LP_Forms_Handler {

	/**
	 * Become a teacher form
	 */
	public static function process_become_teacher() {

		add_filter( 'learn-press/become-teacher-validate-field', array(
			__CLASS__,
			'become_teacher_validate_field'
		), 10, 3 );

		$fields      = learn_press_get_become_a_teacher_form_fields();
		$field_names = wp_list_pluck( $fields, 'id' );
		$args        = call_user_func_array( array( 'LP_Request', 'get_list' ), $field_names );

		$result = array(
			'message' => array(),
			'result'  => 'success'
		);

		foreach ( $fields as $field ) {
			$name     = $field['id'];
			$validate = apply_filters( 'learn-press/become-teacher-validate-field', $name, $field, $args[ $name ] );

			if ( is_wp_error( $validate ) ) {
				$result['message'][ $name ] = learn_press_get_message( $validate->get_error_message(), 'error' );
				$result['result']           = 'error';
			} elseif ( ! $validate ) {
				$result['message'][ $name ] = learn_press_get_message( sprintf( '%s "%s" %s', __( 'Field', 'learnpress' ), $field['title'], __( 'is required.', 'learnpress' ) ), 'error' );
				$result['result']           = 'error';
			}
		}

		remove_filter( 'learn-press/become-teacher-validate-field', array(
			__CLASS__,
			'become_teacher_validate_field'
		) );

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
					throw new Exception( __( 'Your email does not exist!', 'learnpress' ) );
				}
			}
		}
		catch ( Exception $ex ) {
			$validate = new WP_Error( 'invalid_email', $ex->getMessage() );
		}

		return $validate;
	}

	public static function process_login() {

		if ( ! LP_Request::verify_nonce( 'learn-press-login' ) ) {
			return;
		}

		add_filter( 'learn-press/login-validate-field', array(
			__CLASS__,
			'login_validate_field'
		), 10, 3 );

		$fields      = LP_Shortcode_Login_Form::get_login_fields();
		$field_names = wp_list_pluck( $fields, 'id' );
		$args        = call_user_func_array( array( 'LP_Request', 'get_list' ), $field_names );

		$result = array(
			'message' => array(),
			'result'  => 'success'
		);

		foreach ( $fields as $field ) {
			$name     = $field['id'];
			$validate = apply_filters( 'learn-press/login-validate-field', $name, $field, $args[ $name ] );

			if ( is_wp_error( $validate ) ) {
				learn_press_add_message( $validate->get_error_message(), 'error' );

				$result['message'][ $name ] = learn_press_get_message( $validate->get_error_message(), 'error' );
				$result['result']           = 'error';
			} elseif ( ! $validate ) {
				$message = sprintf( '%s "%s" %s', __( 'Field', 'learnpress' ), $field['title'], __( 'is required.', 'learnpress' ) );

				learn_press_add_message( $message, 'error' );

				$result['message'][ $name ] = learn_press_get_message( $message, 'error' );
				$result['result']           = 'error';
			}
		}

		remove_filter( 'learn-press/login-validate-field', array(
			__CLASS__,
			'login_validate_field'
		) );

		if ( $result['result'] === 'success' ) {
			$logged = wp_signon( array(
				'user_login'    => $args['username'],
				'user_password' => $args['password'],
				'rememberme'    => LP_Request::get_string( 'rememberme' )
			) );

			if ( is_wp_error( $logged ) ) {
				$result['result'] = 'error';
				foreach ( $logged->get_error_messages() as $code => $message ) {
					$result['message'][ $code ] = learn_press_get_message( $message, 'error' );

					learn_press_add_message( $message, 'error' );
				}
			}
		}

		$result = apply_filters( 'learn-press/login-request-result', $result );

		if ( $result['result'] === 'success' ) {
			$message             = __( 'Login successfully.', 'learnpress' );
			$result['message'][] = learn_press_get_message( $message, 'success' );

			learn_press_add_message( $message, 'success' );
		}
		if ( ! $redirect = LP_Request::get( 'redirect_to' ) ) {
			$redirect = LP_Request::get_redirect( learn_press_get_current_url() );
		}

		learn_press_maybe_send_json( $result, 'learn_press_print_messages' );

		if ( ( $result['result'] === 'success' ) && $redirect ) {
			wp_redirect( $redirect );
			exit();
		}
	}

	public static function login_validate_field( $name, $field, $value ) {
		return ! ! $value;
	}

	public static function process_register() {
		if ( ! LP_Request::verify_nonce( 'learn-press-register' ) ) {
			return;
		}

		add_filter( 'learn-press/register-validate-field', array(
			__CLASS__,
			'register_validate_field'
		), 10, 3 );

		$fields      = LP_Shortcode_Register_Form::get_register_fields();
		$field_names = wp_list_pluck( $fields, 'id' );
		$args        = call_user_func_array( array( 'LP_Request', 'get_list' ), $field_names );

		$result = array(
			'message' => array(),
			'result'  => 'success'
		);

		foreach ( $fields as $field ) {
			$name     = $field['id'];
			$validate = apply_filters( 'learn-press/register-validate-field', $name, $field, $args[ $name ] );

			if ( is_wp_error( $validate ) ) {
				learn_press_add_message( $validate->get_error_message(), 'error' );

				$result['message'][ $name ] = learn_press_get_message( $validate->get_error_message(), 'error' );
				$result['result']           = 'error';
			} elseif ( ! $validate ) {
				$message = sprintf( '%s "%s" %s', __( 'Field', 'learnpress' ), $field['title'], __( 'is required.', 'learnpress' ) );

				learn_press_add_message( $message, 'error' );

				$result['message'][ $name ] = learn_press_get_message( $message, 'error' );
				$result['result']           = 'error';
			}
		}

		remove_filter( 'learn-press/register-validate-field', array(
			__CLASS__,
			'register_validate_field'
		) );

		if ( $result['result'] === 'success' ) {

			$new_user = apply_filters( 'learn-press/new-user-data', array(
				'user_login' => $args['reg_username'],
				'user_pass'  => isset( $args['reg_password'] ) ? $args['reg_password'] : '',
				'user_email' => $args['reg_email']
			) );

			$user_id = wp_insert_user( $new_user );

			if ( is_wp_error( $user_id ) ) {
				$result['result'] = 'error';
				foreach ( $user_id->get_error_messages() as $code => $message ) {
					$result['message'][ $code ] = learn_press_get_message( $message, 'error' );

					learn_press_add_message( $message, 'error' );
				}
			} else {
				wp_new_user_notification( $user_id );
			}
		}

		$result = apply_filters( 'learn-press/register-request-result', $result );

		if ( $result['result'] === 'success' ) {
			$message             = __( 'Register successfully.', 'learnpress' );
			$result['message'][] = learn_press_get_message( $message, 'success' );

			learn_press_add_message( $message, 'success' );

			$logged = wp_signon( array(
				'user_login'    => $args['reg_username'],
				'user_password' => $args['reg_password']
			) );
		}

		learn_press_maybe_send_json( $result, 'learn_press_print_messages' );

		if ( ( $result['result'] === 'success' ) && $redirect = LP_Request::get_redirect( learn_press_get_current_url() ) ) {
			wp_redirect( $redirect );
			exit();
		}
	}

	public static function register_validate_field( $name, $field, $value ) {
		$validate = ! ! $value;

		if ( $validate && $name === 'reg_password' ) {
			try {
				if ( strlen( $value ) < 6 ) {
					throw new Exception( __( 'Password is too short!', 'learnpress' ), 100 );
				}

				if ( preg_match( '#\s+#', $value ) ) {
					throw new Exception( __( 'Password can not have spacing!', 'learnpress' ), 110 );
				}

				if ( ! preg_match( "#[a-zA-Z]+#", $value ) ) {
					throw new Exception( __( 'Password must include at least one letter!', 'learnpress' ), 120 );
				}

				if ( ! preg_match( "#[A-Z]+#", $value ) ) {
					throw new Exception( __( 'Password must include at least one capitalized letter!', 'learnpress' ), 125 );
				}

				if ( ! preg_match( "#[0-9]+#", $value ) ) {
					throw new Exception( __( 'Password must include at least one number!', 'learnpress' ), 125 );
				}

				if ( ! preg_match( '#[~!@\#$%^&*()]#', $value ) ) {
					throw new Exception( __( 'Password must include at least one of these characters ~!@#$%^&*() !', 'learnpress' ), 125 );
				}
			}
			catch ( Exception $ex ) {
				$validate = new WP_Error( $ex->getCode(), $ex->getMessage() );
			}
		}

		return $validate;
	}

	public static function init() {
		self::process_login();
		self::process_register();
	}
}

