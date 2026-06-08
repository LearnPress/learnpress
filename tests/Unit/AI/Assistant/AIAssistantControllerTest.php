<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\AI\Assistant;

use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class AIAssistantControllerTest extends BrainMonkeyTestCase {

	private function load_controller_with_stubs(): void {
		if ( ! function_exists( 'absint' ) ) {
			function absint( $value ) {
				return abs( (int) $value );
			}
		}

		if ( ! function_exists( 'sanitize_textarea_field' ) ) {
			function sanitize_textarea_field( $value ) {
				return is_scalar( $value ) ? trim( (string) $value ) : '';
			}
		}

		if ( ! function_exists( 'sanitize_text_field' ) ) {
			function sanitize_text_field( $value ) {
				return is_scalar( $value ) ? trim( (string) $value ) : '';
			}
		}

		if ( ! function_exists( '\\LearnPress\\AI\\Assistant\\get_current_user_id' ) ) {
			eval(
				'namespace LearnPress\\AI\\Assistant;
				function get_current_user_id() {
					return \\LearnPress\\Tests\\Unit\\AI\\Assistant\\AIAssistantControllerState::$user_id;
				}
				function __( $text, $domain = null ) {
					return (string) $text;
				}
				function absint( $value ) {
					return abs( (int) $value );
				}
				function sanitize_textarea_field( $value ) {
					return is_scalar( $value ) ? trim( (string) $value ) : "";
				}
				function sanitize_text_field( $value ) {
					return is_scalar( $value ) ? trim( (string) $value ) : "";
				}'
			);
		}

		if ( ! function_exists( 'get_current_user_id' ) ) {
			function get_current_user_id() {
				return \LearnPress\Tests\Unit\AI\Assistant\AIAssistantControllerState::$user_id;
			}
		}

		if ( ! class_exists( 'LP_Settings', false ) ) {
			eval(
				'class LP_Settings {
					public static array $options = array();
					public static function get_option( string $key, $default = "" ) {
						return self::$options[ $key ] ?? $default;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\Services\\OpenAiService', false ) ) {
			eval(
				'namespace LearnPress\\Services;
				class OpenAiService {
					public static bool $enabled = true;
					public static function instance(): self {
						return new self();
					}
					public function is_enable(): bool {
						return self::$enabled;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\AI\\Assistant\\Agent', false ) ) {
			eval(
				'namespace LearnPress\\AI\\Assistant;
				class Agent {
					public static array $captured = array();
					public static array $result = array( "type" => "text", "message" => "ok", "quiz" => null );
					public function run( string $message, int $lesson_id, int $course_id, int $user_id, array $history = array(), array $active_quiz_questions = array(), ?string $action_hint = null ): array {
						self::$captured = array(
							"message" => $message,
							"lesson_id" => $lesson_id,
							"course_id" => $course_id,
							"user_id" => $user_id,
							"history" => $history,
							"active_quiz_questions" => $active_quiz_questions,
							"action_hint" => $action_hint,
						);
						return self::$result;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\AI\\Assistant\\AIAssistantController', false ) ) {
			require_once dirname( __DIR__, 4 ) . '/inc/AI/Assistant/AIAssistantController.php';
		}
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_is_enabled_requires_service_flag_secret_and_option(): void {
		$this->load_controller_with_stubs();

		\LearnPress\Services\OpenAiService::$enabled = true;
		\LP_Settings::$options                       = array(
			'open_ai_secret_key'   => 'sk-123',
			'ai_assistant_enabled' => 'yes',
		);

		$this->assertTrue( \LearnPress\AI\Assistant\AIAssistantController::is_enabled() );

		\LP_Settings::$options['open_ai_secret_key'] = '';
		$this->assertFalse( \LearnPress\AI\Assistant\AIAssistantController::is_enabled() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_get_enabled_actions_reads_action_settings_and_defaults_to_yes(): void {
		$this->load_controller_with_stubs();

		\LP_Settings::$options = array(
			'ai_assistant_summarize_enabled' => 'no',
			'ai_assistant_explain_enabled'   => 'yes',
			'ai_assistant_quick_quiz_enabled' => 'no',
		);

		$actions = \LearnPress\AI\Assistant\AIAssistantController::get_enabled_actions();

		$this->assertFalse( $actions['summarize'] );
		$this->assertTrue( $actions['explain'] );
		$this->assertFalse( $actions['quick_quiz'] );
		$this->assertTrue( $actions['smart_review'] );
		$this->assertFalse( \LearnPress\AI\Assistant\AIAssistantController::is_action_enabled( 'summarize' ) );
		$this->assertTrue( \LearnPress\AI\Assistant\AIAssistantController::is_action_enabled( 'smart_review' ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_handle_chat_throws_when_required_fields_missing(): void {
		$this->load_controller_with_stubs();
		AIAssistantControllerState::$user_id = 99;

		$controller = new \LearnPress\AI\Assistant\AIAssistantController();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Message is required.' );
		$controller->handle_chat(
			array(
				'message'   => '   ',
				'lesson_id' => 10,
				'course_id' => 20,
			)
		);
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_handle_chat_sanitizes_history_and_active_quiz_state_before_agent(): void {
		$this->load_controller_with_stubs();
		AIAssistantControllerState::$user_id = 55;

		$controller = new \LearnPress\AI\Assistant\AIAssistantController();
		$result     = $controller->handle_chat(
			array(
				'message'               => '  hello  ',
				'lesson_id'             => '10',
				'course_id'             => '20',
				'history'               => array(
					array(
						'role'    => 'user',
						'content' => '  ask me  ',
					),
					array(
						'role'    => 'system',
						'content' => 'ignore',
					),
				),
				'active_quiz_questions' => array(
					'is_active'     => '1',
					'completed'     => '',
					'current_index' => '2',
					'score'         => '1',
					'questions'     => array(
						array(
							'question'      => ' Q1 ',
							'options'       => array( ' A ', ' B ' ),
							'correct_index' => '1',
							'explanation'   => '  explain  ',
						),
					),
				),
				'action_hint'          => 'quick-quiz',
			)
		);

		$this->assertSame( 'text', $result['type'] );
		$this->assertSame( 'hello', \LearnPress\AI\Assistant\Agent::$captured['message'] );
		$this->assertSame( 55, \LearnPress\AI\Assistant\Agent::$captured['user_id'] );
		$this->assertSame( 1, count( \LearnPress\AI\Assistant\Agent::$captured['history'] ) );
		$this->assertSame( 'ask me', \LearnPress\AI\Assistant\Agent::$captured['history'][0]['content'] );
		$this->assertTrue( \LearnPress\AI\Assistant\Agent::$captured['active_quiz_questions']['is_active'] );
		$this->assertSame( 2, \LearnPress\AI\Assistant\Agent::$captured['active_quiz_questions']['current_index'] );
		$this->assertSame( 'Q1', \LearnPress\AI\Assistant\Agent::$captured['active_quiz_questions']['questions'][0]['question'] );
		$this->assertSame( 'quick_quiz', \LearnPress\AI\Assistant\Agent::$captured['action_hint'] );
	}
}

class AIAssistantControllerState {
	public static int $user_id = 0;
}
