<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Services;

use Brain\Monkey\Functions;
use LearnPress\Services\OpenAiService;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

class OpenAiServiceTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->define_stubs();
		$this->reset_singleton( OpenAiService::class );
	}

	private function define_stubs(): void {
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

		if ( ! class_exists( 'LP_Helper', false ) ) {
			eval(
				'class LP_Helper {
					public static function json_decode( string $json, bool $assoc = false ) {
						return json_decode( $json, $assoc );
					}
				}'
			);
		}
	}

	private function reset_singleton( string $class_name ): void {
		$ref = new \ReflectionProperty( $class_name, 'instance' );
		$ref->setValue( null, null );
	}

	private function make_service(): OpenAiService {
		\LP_Settings::$options = array(
			'open_ai_secret_key'              => 'sk-test',
			'open_ai_text_model_type'         => 'gpt-4.1',
			'open_ai_image_model_type'        => 'gpt-image-1',
			'open_ai_frequency_penalty_level' => 0.2,
			'open_ai_presence_penalty_level'  => 0.1,
			'open_ai_creativity_level'        => 0.7,
			'open_ai_max_token'               => 250,
		);

		$service                     = OpenAiService::instance();
		$service->secret_key         = 'sk-test';
		$service->urlChartCompletion = 'https://example.test/v1/chat/completions';

		return $service;
	}

	public function test_handle_params_for_send_chat_completion_includes_tools_and_tool_choice(): void {
		$service  = $this->make_service();
		$messages = array(
			array(
				'role'    => 'user',
				'content' => 'hello',
			),
		);
		$tools    = array(
			array(
				'type'     => 'function',
				'function' => array( 'name' => 'get_lesson_content' ),
			),
		);

		$params = $service->handle_params_for_send_chat_completion(
			array(
				'messages'    => $messages,
				'tools'       => $tools,
				'tool_choice' => 'auto',
			)
		);

		$this->assertArrayHasKey( 'messages', $params );
		$this->assertSame( $messages, $params['messages'] );
		$this->assertArrayHasKey( 'tools', $params );
		$this->assertSame( $tools, $params['tools'] );
		$this->assertArrayHasKey( 'tool_choice', $params );
		$this->assertSame( 'auto', $params['tool_choice'] );
		$this->assertArrayNotHasKey( 'response_format', $params );
	}

	public function test_handle_params_for_send_chat_completion_adds_response_format_without_tools(): void {
		$service = $this->make_service();

		$params = $service->handle_params_for_send_chat_completion(
			array(
				'messages' => array(
					array(
						'role'    => 'user',
						'content' => 'summarize lesson',
					),
				),
			)
		);

		$this->assertArrayHasKey( 'response_format', $params );
		$this->assertSame( array( 'type' => 'json_object' ), $params['response_format'] );
	}

	public function test_send_chat_request_returns_raw_tool_calls_message(): void {
		$service  = $this->make_service();
		$captured = array();

		Functions\when( 'wp_remote_post' )->alias(
			static function ( $url, $args ) use ( &$captured ) {
				$captured['url']  = $url;
				$captured['args'] = $args;
				return array( 'ok' => true );
			}
		);
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn(
			json_encode(
				array(
					'usage'   => array(
						'total_tokens' => 42,
					),
					'choices' => array(
						array(
							'message' => array(
								'role'       => 'assistant',
								'content'    => null,
								'tool_calls' => array(
									array(
										'id'       => 'call_1',
										'function' => array(
											'name'      => 'get_lesson_content',
											'arguments' => '{"lesson_id":10}',
										),
									),
								),
							),
						),
					),
				)
			)
		);

		$message = $service->send_chat_request(
			array(
				'messages' => array(
					array(
						'role'    => 'user',
						'content' => 'help me',
					),
				),
				'tools'    => array(
					array(
						'type'     => 'function',
						'function' => array( 'name' => 'get_lesson_content' ),
					),
				),
			)
		);

		$this->assertSame( 'https://example.test/v1/chat/completions', $captured['url'] );
		$this->assertArrayHasKey( 'tool_calls', $message );
		$this->assertSame( 'get_lesson_content', $message['tool_calls'][0]['function']['name'] );
		$this->assertSame( 42, $message['usage']['total_tokens'] );
	}
}
