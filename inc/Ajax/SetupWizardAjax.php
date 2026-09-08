<?php
/**
 * Class SetupWizardAjax
 *
 * Handle Setup Wizard requests via the LearnPress AJAX dispatcher.
 *
 * @since 4.4.6
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Helpers\Response;
use LearnPress\Models\UserModel;
use LearnPress\Services\SetupDemoCourseService;
use LP_Emails;
use LP_Helper;
use LP_Request;
use LP_Settings;
use LP_Settings_Cache;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class SetupWizardAjax
 */
class SetupWizardAjax extends AbstractAjax {
	/**
	 * Save the current wizard step.
	 *
	 * @return void
	 */
	public function lp_setup_wizard_save_step() {
		$response = new Response();

		try {
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception( esc_html__( 'You do not have permission to update setup settings.', 'learnpress' ) );
			}

			$form = $this->get_form_data();
			$step = sanitize_key( $form['lp-setup-step'] ?? '' );

			$nonce = sanitize_text_field( $form['lp-setup-nonce'] ?? '' );
			if ( ! wp_verify_nonce( $nonce, 'lp-setup-step-' . $step ) ) {
				throw new Exception( esc_html__( 'The setup request has expired. Please refresh the page and try again.', 'learnpress' ) );
			}

			$allowed_steps = array( 'welcome', 'learning-experience', 'pages', 'payment', 'emails', 'finish' );
			if ( ! in_array( $step, $allowed_steps, true ) ) {
				throw new Exception( esc_html__( 'Invalid setup step.', 'learnpress' ) );
			}

			$settings = is_array( $form['settings'] ?? null ) ? $form['settings'] : array();
			switch ( $step ) {
				case 'learning-experience':
					$this->save_learning_experience( $settings );
					break;
				case 'pages':
					$this->save_pages();
					break;
				case 'payment':
					$this->save_payment( $settings );
					break;
				case 'emails':
					$this->save_emails( $settings );
					break;
			}

			( new LP_Settings_Cache( true ) )->clean_lp_settings();
			do_action( 'learn-press/setup-wizard/update-settings', $settings, $step );

			$response->status  = Response::STATUS_SUCCESS;
			$response->message = esc_html__( 'Setup settings saved.', 'learnpress' );
		} catch ( Throwable $error ) {
			$response->message = $error->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Import one demo course for the Finish step.
	 *
	 * @return void
	 */
	public function lp_setup_import_demo_course() {
		$response = new Response();

		try {
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception( esc_html__( 'You do not have permission to install demo courses.', 'learnpress' ) );
			}

			$params = LP_Helper::json_decode( LP_Request::get_param( 'data' ), true );
			$index  = is_array( $params ) ? ( $params['index'] ?? 0 ) : -1;
			if ( ! is_numeric( $index ) || (int) $index < 0 ) {
				throw new Exception( esc_html__( 'Invalid demo course index.', 'learnpress' ) );
			}

			$response->data    = (object) ( new SetupDemoCourseService() )->import_course( (int) $index );
			$response->status  = Response::STATUS_SUCCESS;
			$response->message = esc_html__( 'The demo course was processed successfully.', 'learnpress' );
		} catch ( Throwable $error ) {
			$response->message = $error->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Convert flat bracketed form keys returned by getDataOfForm into arrays.
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function get_form_data(): array {
		$params = LP_Helper::json_decode( LP_Request::get_param( 'data' ), true );
		if ( ! is_array( $params ) ) {
			throw new Exception( esc_html__( 'Invalid setup data.', 'learnpress' ) );
		}

		parse_str( http_build_query( $params ), $form );

		return is_array( $form ) ? $form : array();
	}

	/**
	 * Save Learning Experience settings.
	 *
	 * @param array $settings Submitted settings.
	 *
	 * @return void
	 */
	protected function save_learning_experience( array $settings ) {
		$course = is_array( $settings['course'] ?? null ) ? $settings['course'] : array();
		if ( ! wp_is_block_theme() ) {
			$layout = sanitize_key( $course['layout_single_course'] ?? '' );
			if ( in_array( $layout, array( 'modern', 'classic' ), true ) ) {
				update_option( 'learn_press_layout_single_course', $layout );
			}

			$listing = sanitize_key( $course['archive_courses_layout'] ?? '' );
			if ( in_array( $listing, array( 'grid', 'list' ), true ) ) {
				update_option( 'learn_press_archive_courses_layout', $listing );
			}
		}

		update_option( 'learn_press_auto_enroll', $this->toggle_value( $course['auto_enroll'] ?? 0 ) );
	}

	/**
	 * Ensure all required LearnPress pages exist.
	 *
	 * @return void
	 */
	protected function save_pages() {
		$page_keys = array(
			'courses_page_id',
			'instructors_page_id',
			'single_instructor_page_id',
			'profile_page_id',
			'checkout_page_id',
			'become_a_teacher_page_id',
			'term_conditions_page_id',
		);

		foreach ( $page_keys as $page_key ) {
			$option_key = 'learn_press_' . $page_key;
			$page_id    = (int) get_option( $option_key );
			if ( ! $page_id || 'publish' !== get_post_status( $page_id ) ) {
				$page_id = (int) \LP_Setup_Wizard::instance()->create_page( $page_key );
				if ( $page_id ) {
					update_option( $option_key, $page_id );
				}
			}
		}
	}

	/**
	 * Save currency and payment gateway settings.
	 *
	 * @param array $settings Submitted settings.
	 *
	 * @return void
	 */
	protected function save_payment( array $settings ) {
		$currency = is_array( $settings['currency'] ?? null ) ? $settings['currency'] : array();
		$code     = strtoupper( sanitize_text_field( $currency['currency'] ?? '' ) );
		if ( array_key_exists( $code, learn_press_currencies() ) ) {
			update_option( 'learn_press_currency', $code );
		}

		$position = sanitize_key( $currency['currency_pos'] ?? '' );
		if ( array_key_exists( $position, learn_press_currency_positions() ) ) {
			update_option( 'learn_press_currency_pos', $position );
		}

		update_option( 'learn_press_thousands_separator', sanitize_text_field( $currency['thousands_separator'] ?? ',' ) );
		update_option( 'learn_press_decimals_separator', sanitize_text_field( $currency['decimals_separator'] ?? '.' ) );
		update_option( 'learn_press_number_of_decimals', min( 8, max( 0, (int) ( $currency['number_of_decimals'] ?? 2 ) ) ) );

		foreach ( array( 'offline-payment', 'paypal' ) as $gateway ) {
			$gateway_settings           = get_option( 'learn_press_' . $gateway, array() );
			$gateway_settings           = is_array( $gateway_settings ) ? $gateway_settings : array();
			$gateway_settings['enable'] = $this->toggle_value( $settings[ $gateway ]['enable'] ?? 0 );
			update_option( 'learn_press_' . $gateway, $gateway_settings );
		}
	}

	/**
	 * Save the supported email notification groups.
	 *
	 * @param array $settings Submitted settings.
	 *
	 * @return void
	 */
	protected function save_emails( array $settings ) {
		$groups = array(
			'new_order'                 => array( 'new-order-admin', 'new-order-instructor' ),
			'order_completed'           => array( 'completed-order-user' ),
			'course_enrolled'           => array( 'enrolled-course-user' ),
			'course_completed'          => array( 'finished-course-user' ),
			'become_instructor_request'  => array( 'become-an-instructor' ),
			'become_instructor_accepted' => array( 'instructor-accepted' ),
		);
		$notifications = is_array( $settings['emails']['notifications'] ?? null )
			? $settings['emails']['notifications']
			: array();

		foreach ( $groups as $key => $email_ids ) {
			$enabled = 'yes' === $this->toggle_value( $notifications[ $key ] ?? 0 );
			foreach ( $email_ids as $email_id ) {
				$email = LP_Emails::get_email( $email_id );
				if ( $email ) {
					$email->enable( $enabled );
				}
			}
		}
	}

	/**
	 * Convert an AdminTemplate toggle value to a LearnPress option value.
	 *
	 * @param mixed $value Submitted toggle value.
	 *
	 * @return string
	 */
	protected function toggle_value( $value ): string {
		return in_array( (string) $value, array( '1', 'yes', 'true' ), true ) ? 'yes' : 'no';
	}
}
