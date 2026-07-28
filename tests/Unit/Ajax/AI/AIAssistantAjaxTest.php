<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Ajax\AI;

use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class AIAssistantAjaxTest extends BrainMonkeyTestCase {

	private function load_ajax_with_stubs(): void {
		if ( ! class_exists( '\\LearnPress\\Ajax\\AbstractAjax', false ) ) {
			eval(
				'namespace LearnPress\\Ajax;
				abstract class AbstractAjax {}'
			);
		}

		if ( ! class_exists( 'LP_Request', false ) ) {
			eval(
				'class LP_Request {
					public static array $params = array();
					public static function get_param( string $key ) {
						return self::$params[ $key ] ?? null;
					}
				}'
			);
		}

		if ( ! class_exists( 'LP_Helper', false ) ) {
			eval(
				'class LP_Helper {
					public static function json_decode( string $json, bool $assoc = false ) {
						return json_decode( $json, $assoc );
					}
				}'
			);
		}

		if ( ! class_exists( 'LP_REST_Response', false ) ) {
			eval(
				// Mirrors inc/Models/class-lp-rest-response.php, where status defaults to "error"
				// and handlers opt in to success. The previous "" default made the error-path
				// assertions vacuous.
				'class LP_REST_Response {
					public string $status = "error";
					public string $message = "";
					public $data = null;
				}'
			);
		}

		if ( ! class_exists( 'LP_Debug', false ) ) {
			eval(
				'class LP_Debug {
					public static array $logged = array();
					public static function error_log( $error ) {
						self::$logged[] = $error instanceof \\Throwable ? $error->getMessage() : (string) $error;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\AI\\Assistant\\AIAssistantController', false ) ) {
			eval(
				'namespace LearnPress\\AI\\Assistant;
				class AIAssistantController {
					public static bool $enabled = true;
					public static array $result = array( "type" => "text", "message" => "ok", "quiz" => null );
					public static $throw = null;
					public static function is_enabled(): bool {
						return self::$enabled;
					}
					public function handle_chat( array $data ): array {
						\\LearnPress\\Tests\\Unit\\Ajax\\AI\\AIAssistantAjaxState::$received = $data;
						if ( self::$throw instanceof \\Throwable ) {
							throw self::$throw;
						}
						return self::$result;
					}
				}'
			);
		}

		if ( ! function_exists( '\\LearnPress\\Ajax\\AI\\is_user_logged_in' ) ) {
			eval(
				'namespace LearnPress\\Ajax\\AI;
				function is_user_logged_in() {
					return \LearnPress\\Tests\\Unit\\Ajax\\AI\\AIAssistantAjaxState::$logged_in;
				}
				function __( $text, $domain = null ) {
					return (string) $text;
				}
				function absint( $value ) {
					return abs( (int) $value );
				}
				function wp_send_json( $response ) {
					\LearnPress\\Tests\\Unit\\Ajax\\AI\\AIAssistantAjaxState::$sent = $response;
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\Ajax\\AI\\AIAssistantAjax', false ) ) {
			require_once dirname( __DIR__, 4 ) . '/inc/Ajax/AI/AIAssistantAjax.php';
		}
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_openai_assistant_chat_returns_success_with_normalized_data(): void {
		$this->load_ajax_with_stubs();

		AIAssistantAjaxState::$logged_in = true;
		\LearnPress\AI\Assistant\AIAssistantController::$enabled = true;
		\LearnPress\AI\Assistant\AIAssistantController::$result = array(
			'type' => 'unexpected',
			'message' => 'Hello learner',
			'quiz' => 'invalid-quiz',
		);
		\LP_Request::$params['data'] = json_encode(
			array(
				'message'   => ' hi ',
				'course_id' => '20',
				'item_type' => 'lp_lesson',
				'item_id'   => '10',
			)
		);

		$ajax = new \LearnPress\Ajax\AI\AIAssistantAjax();
		$ajax->openai_assistant_chat();

		$this->assertSame( 'success', AIAssistantAjaxState::$sent->status );
		$this->assertSame( 'text', AIAssistantAjaxState::$sent->data['type'] );
		$this->assertSame( 'Hello learner', AIAssistantAjaxState::$sent->data['message'] );
		$this->assertNull( AIAssistantAjaxState::$sent->data['quiz'] );

		// The full composite identity reaches the controller.
		$this->assertSame( '20', AIAssistantAjaxState::$received['course_id'] );
		$this->assertSame( 'lp_lesson', AIAssistantAjaxState::$received['item_type'] );
		$this->assertSame( '10', AIAssistantAjaxState::$received['item_id'] );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_openai_assistant_chat_returns_public_exception_message_verbatim(): void {
		$this->load_ajax_with_stubs();

		AIAssistantAjaxState::$logged_in                         = true;
		\LearnPress\AI\Assistant\AIAssistantController::$enabled  = true;
		\LearnPress\AI\Assistant\AIAssistantController::$throw    = new \LearnPress\AI\Assistant\PublicException( 'This content is protected.' );
		\LP_Request::$params['data']                              = json_encode( array( 'message' => 'hi' ) );

		$ajax = new \LearnPress\Ajax\AI\AIAssistantAjax();
		$ajax->openai_assistant_chat();

		$this->assertSame( 'error', AIAssistantAjaxState::$sent->status );
		$this->assertSame( 'This content is protected.', AIAssistantAjaxState::$sent->message );
		$this->assertSame( array(), \LP_Debug::$logged );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_openai_assistant_chat_masks_provider_errors_and_logs_them(): void {
		$this->load_ajax_with_stubs();

		$provider_detail = 'OpenAI 401: Incorrect API key sk-live-abc123';

		AIAssistantAjaxState::$logged_in                        = true;
		\LearnPress\AI\Assistant\AIAssistantController::$enabled = true;
		\LearnPress\AI\Assistant\AIAssistantController::$throw   = new \RuntimeException( $provider_detail );
		\LP_Request::$params['data']                             = json_encode( array( 'message' => 'hi' ) );

		$ajax = new \LearnPress\Ajax\AI\AIAssistantAjax();
		$ajax->openai_assistant_chat();

		$this->assertSame( 'error', AIAssistantAjaxState::$sent->status );
		$this->assertStringNotContainsString( 'sk-live-abc123', AIAssistantAjaxState::$sent->message );
		$this->assertStringNotContainsString( 'OpenAI', AIAssistantAjaxState::$sent->message );
		$this->assertStringContainsString( 'unavailable right now', AIAssistantAjaxState::$sent->message );

		// The detail is not lost — it goes to the log instead of the browser.
		$this->assertContains( $provider_detail, \LP_Debug::$logged );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_openai_assistant_chat_returns_error_when_user_not_logged_in(): void {
		$this->load_ajax_with_stubs();

		AIAssistantAjaxState::$logged_in = false;
		\LP_Request::$params['data'] = json_encode( array() );

		$ajax = new \LearnPress\Ajax\AI\AIAssistantAjax();
		$ajax->openai_assistant_chat();

		$this->assertSame( 'error', AIAssistantAjaxState::$sent->status );
		$this->assertSame( 'text', AIAssistantAjaxState::$sent->data['type'] );
		$this->assertSame( '', AIAssistantAjaxState::$sent->data['message'] );
		$this->assertNull( AIAssistantAjaxState::$sent->data['quiz'] );
		$this->assertStringContainsString( 'logged in', strtolower( AIAssistantAjaxState::$sent->message ) );
	}
}

class AIAssistantAjaxState {
	public static bool $logged_in = true;
	public static $sent           = null;
	public static array $received = array();
}
