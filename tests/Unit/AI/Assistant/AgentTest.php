<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\AI\Assistant;

use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class AgentTest extends BrainMonkeyTestCase {

	private function load_agent_with_stubs(): void {
		if ( ! function_exists( '\\LearnPress\\AI\\Assistant\\sanitize_text_field' ) ) {
			eval(
				'namespace LearnPress\\AI\\Assistant;
				function sanitize_text_field( $value ) {
					return is_scalar( $value ) ? trim( (string) $value ) : "";
				}
				function sanitize_textarea_field( $value ) {
					return is_scalar( $value ) ? trim( (string) $value ) : "";
				}
				function absint( $value ) {
					return abs( (int) $value );
				}
				function wp_json_encode( $data ) {
					return json_encode( $data );
				}
				function __( $text, $domain = null ) {
					return (string) $text;
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

		if ( ! class_exists( '\\LearnPress\\Services\\OpenAiService', false ) ) {
			eval(
				'namespace LearnPress\\Services;
				class OpenAiService {
					public static array $queue = array();
					public static function instance(): self {
						static $instance = null;
						if ( null === $instance ) {
							$instance = new self();
						}
						return $instance;
					}
					public function send_chat_request( array $payload ): array {
						if ( empty( self::$queue ) ) {
							return array( "content" => "" );
						}
						return array_shift( self::$queue );
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\AI\\Assistant\\DataLoaders', false ) ) {
			eval(
				'namespace LearnPress\\AI\\Assistant;
				class DataLoaders {
					public static array $lesson = array( "title" => "Lesson", "content" => "Content" );
					public static array $outline = array( "title" => "Course", "sections" => array() );
					public static array $quizResults = array( "quizzes" => array() );
					public function get_lesson_content( int $lesson_id, int $user_id ): array {
						return self::$lesson;
					}
					public function get_course_outline( int $course_id ): array {
						return self::$outline;
					}
					public function get_quiz_results( int $user_id, int $course_id ): array {
						return self::$quizResults;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\AI\\Assistant\\Agent', false ) ) {
			require_once dirname( __DIR__, 4 ) . '/inc/AI/Assistant/Agent.php';
		}
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_run_returns_quiz_contract_when_starting_mini_quiz(): void {
		$this->load_agent_with_stubs();

		\LearnPress\Services\OpenAiService::$queue = array(
			array(
				'content' => json_encode(
					array(
						'intro' => 'Mini quiz ready',
						'questions' => array(
							array(
								'question' => 'Q1',
								'options' => array( 'A1', 'B1', 'C1', 'D1' ),
								'correct_index' => 1,
								'explanation' => 'Because B1',
							),
						),
					)
				),
			),
		);

		$agent = new \LearnPress\AI\Assistant\Agent();
		$result = $agent->run( '/mini-quiz', 10, 20, 30 );

		$this->assertSame( 'quiz', $result['type'] );
		$this->assertSame( 'Mini quiz ready', $result['message'] );
		$this->assertTrue( $result['quiz']['is_active'] );
		$this->assertSame( 1, count( $result['quiz']['questions'] ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_run_stops_after_max_iterations_and_returns_fallback_text(): void {
		$this->load_agent_with_stubs();

		\LearnPress\Services\OpenAiService::$queue = array(
			array( 'content' => '' ),
			array( 'content' => '' ),
			array( 'content' => '' ),
			array( 'content' => '' ),
		);

		$agent = new \LearnPress\AI\Assistant\Agent();
		$result = $agent->run( '/summarize', 10, 20, 30 );

		$this->assertSame( 'text', $result['type'] );
		$this->assertStringContainsString( 'unable to complete', strtolower( $result['message'] ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_run_extracts_message_from_json_content_for_text_intent(): void {
		$this->load_agent_with_stubs();

		\LearnPress\Services\OpenAiService::$queue = array(
			array(
				'content' => json_encode(
					array(
						'message' => 'Key points: one, two, three.',
					)
				),
			),
		);

		$agent  = new \LearnPress\AI\Assistant\Agent();
		$result = $agent->run( '/summarize', 10, 20, 30 );

		$this->assertSame( 'text', $result['type'] );
		$this->assertSame( 'Key points: one, two, three.', $result['message'] );
		$this->assertStringNotContainsString( '{', $result['message'] );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_run_keeps_plain_text_content_for_text_intent(): void {
		$this->load_agent_with_stubs();

		\LearnPress\Services\OpenAiService::$queue = array(
			array(
				'content' => 'This is a plain text summary.',
			),
		);

		$agent  = new \LearnPress\AI\Assistant\Agent();
		$result = $agent->run( '/summarize', 10, 20, 30 );

		$this->assertSame( 'text', $result['type'] );
		$this->assertSame( 'This is a plain text summary.', $result['message'] );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_continue_interactive_quiz_returns_feedback_and_progress(): void {
		$this->load_agent_with_stubs();

		$agent = new \LearnPress\AI\Assistant\Agent();
		$active_quiz = array(
			'is_active' => true,
			'completed' => false,
			'current_index' => 0,
			'score' => 0,
			'total' => 2,
			'questions' => array(
				array(
					'question' => 'Q1',
					'options' => array( 'A1', 'B1', 'C1', 'D1' ),
					'correct_index' => 1,
					'explanation' => 'Because B1',
				),
				array(
					'question' => 'Q2',
					'options' => array( 'A2', 'B2', 'C2', 'D2' ),
					'correct_index' => 2,
					'explanation' => 'Because C2',
				),
			),
		);

		$result = $agent->run( '2', 10, 20, 30, array(), $active_quiz );

		$this->assertSame( 'quiz', $result['type'] );
		$this->assertTrue( $result['quiz']['is_active'] );
		$this->assertSame( 1, $result['quiz']['score'] );
		$this->assertSame( 1, $result['quiz']['current_index'] );
		$this->assertTrue( $result['quiz']['feedback']['is_correct'] );
		$this->assertSame( 'Because B1', $result['quiz']['feedback']['explanation'] );
	}

	public function test_agent_contains_i18n_translator_comments_for_placeholders(): void {
		$file = file_get_contents( dirname( __DIR__, 4 ) . '/inc/AI/Assistant/Agent.php' );

		$this->assertIsString( $file );
		$this->assertStringContainsString( 'translators:', $file );
		$this->assertStringContainsString( "'learnpress'", $file );
	}
}
