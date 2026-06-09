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
				'class LP_REST_Response {
					public string $status = "";
					public string $message = "";
					public $data = null;
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\AI\\Assistant\\AIAssistantController', false ) ) {
			eval(
				'namespace LearnPress\\AI\\Assistant;
				class AIAssistantController {
					public static bool $enabled = true;
					public static array $result = array( "type" => "text", "message" => "ok", "quiz" => null );
					public static function is_enabled(): bool {
						return self::$enabled;
					}
					public function handle_chat( array $data ): array {
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
				'message' => ' hi ',
				'lesson_id' => '10',
				'course_id' => '20',
			)
		);

		$ajax = new \LearnPress\Ajax\AI\AIAssistantAjax();
		$ajax->openai_assistant_chat();

		$this->assertSame( 'success', AIAssistantAjaxState::$sent->status );
		$this->assertSame( 'text', AIAssistantAjaxState::$sent->data['type'] );
		$this->assertSame( 'Hello learner', AIAssistantAjaxState::$sent->data['message'] );
		$this->assertNull( AIAssistantAjaxState::$sent->data['quiz'] );
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
	public static $sent = null;
}
