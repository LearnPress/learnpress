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
				$result['message'][ $name ] = learn_press_get_message( sprintf( __( 'Field "%s" is required.', 'learnpress' ), $field['title'] ), 'error' );
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
			do_action( 'learn-press/become-a-teacher-sent', $args['bat_email'] );
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
					throw new Exception( __( 'Your email does not exists!', 'learnpress' ) );
				}
			}
		}
		catch ( Exception $ex ) {
			$validate = new WP_Error( 'invalid_email', $ex->getMessage() );
		}

		return $validate;
	}
}

