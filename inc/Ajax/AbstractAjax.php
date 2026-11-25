<?php
/**
 * class AjaxBase
 *
 * @since 4.2.7.6
 * @version 1.0.7
 */

namespace LearnPress\Ajax;

use LearnPress\Helpers\Response;
use LP_Request;

/**
 * @use LoadContentViaAjax::load_content_via_ajax
 *
 * $action must unique name on all Ajax classes.
 * Because not specify a specific class.
 */
abstract class AbstractAjax {
	/**
	 * Methods allowed to call via param 'lp-load-ajax'.
	 * Empty: any public method is allowed (backward compatible for addons not declared yet).
	 *
	 * @var string[]
	 * @since 4.5.1
	 */
	protected static $actions = [];

	/**
	 * Nonce action of class (besides the general 'wp_rest'), required for all actions of class.
	 * Empty: not required.
	 * Nonce must be printed only in HTML of page use it (not in LP_Assets, not in HTML return via AJAX),
	 * JS sends it via field 'lp_action_nonce' of 'data'.
	 * So a JS on another page (ex: post content) can't call these actions, even if user has permission.
	 *
	 * @var string
	 * @since 4.5.1
	 */
	protected static $action_nonce = '';

	/**
	 * Field name of 'data' contains nonce of action.
	 */
	const FIELD_ACTION_NONCE = 'lp_action_nonce';

	public static function catch_lp_ajax() {
		if ( ! empty( $_REQUEST['lp-load-ajax'] ) ) {
			$action = LP_Request::get_param( 'lp-load-ajax' );
			$nonce  = LP_Request::get_param( 'nonce' );

			if ( ! static::is_action_allowed( $action ) ) {
				return;
			}

			$class = new static();

			/**
			 * For case cache HTML, nonce of guest is cached (expired), so is not required.
			 * Only apply for guest: nonce of guest is public, not protect anything.
			 * User logged in must always send a valid nonce.
			 * Callbacks must check permission and validate args itself.
			 */
			$class_no_nonce = [
				LoadContentViaAjax::class,
			];

			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				if ( is_user_logged_in()
					|| ! in_array( get_class( $class ), $class_no_nonce, true ) ) {
					// Signal for JS (loadAJAX.js) to refresh nonce and retry once, for case HTML is cached.
					header( 'X-LP-Nonce-Invalid: 1' );
					wp_die( 'Invalid request!', 400 );
				}
			}

			if ( ! static::verify_action_nonce() ) {
				$response          = new Response();
				$response->message = __(
					'Your session has expired, please reload the page and try again.',
					'learnpress'
				);
				wp_send_json( $response );
				return;
			}

			if ( is_callable( [ $class, $action ] ) ) {
				call_user_func( [ $class, $action ] );
			}
		}
	}

	/**
	 * Create nonce of class to print in HTML of page use it.
	 *
	 * @return string empty if class not declare $action_nonce.
	 * @since 4.5.1
	 */
	public static function create_action_nonce(): string {
		if ( empty( static::$action_nonce ) ) {
			return '';
		}

		return wp_create_nonce( static::$action_nonce );
	}

	/**
	 * Verify nonce of class sent via field 'lp_action_nonce' of 'data', if class declares $action_nonce.
	 *
	 * @return bool
	 * @since 4.5.1
	 */
	public static function verify_action_nonce(): bool {
		if ( empty( static::$action_nonce ) ) {
			return true;
		}

		$data   = LP_Request::get_param( 'data', '', 'trim' );
		$params = is_string( $data ) ? json_decode( $data, true ) : null;
		$nonce  = is_array( $params ) ? ( $params[ self::FIELD_ACTION_NONCE ] ?? '' ) : '';
		if ( ! is_string( $nonce ) || '' === $nonce ) {
			return false;
		}

		return false !== wp_verify_nonce( sanitize_text_field( $nonce ), static::$action_nonce );
	}

	/**
	 * Check action can call via 'lp-load-ajax'.
	 *
	 * @param mixed $action
	 *
	 * @return bool
	 * @since 4.5.1
	 */
	public static function is_action_allowed( $action ): bool {
		if ( ! is_string( $action ) || '' === $action ) {
			return false;
		}

		if ( ! empty( static::$actions ) ) {
			return in_array( $action, static::$actions, true );
		}

		// Methods of dispatcher must never call via request (ex: catch_lp_ajax call itself is infinite recursion).
		$methods_dispatcher = [
			'catch_lp_ajax',
			'is_action_allowed',
			'create_action_nonce',
			'verify_action_nonce',
			'__construct',
		];
		if ( in_array( strtolower( $action ), $methods_dispatcher, true ) ) {
			return false;
		}

		return method_exists( static::class, $action );
	}
}
