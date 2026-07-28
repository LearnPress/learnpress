<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\AI\Assistant;

use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class AIAssistantControllerTest extends BrainMonkeyTestCase {

	private function load_controller_with_stubs(): void {
		if ( ! function_exists( '\\LearnPress\\AI\\Assistant\\get_current_user_id' ) ) {
			eval(
				'namespace LearnPress\\AI\\Assistant;
				function get_current_user_id() {
					return \\LearnPress\\Tests\\Unit\\AI\\Assistant\\AIAssistantControllerState::$user_id;
				}
				function learn_press_get_user( $user_id = 0 ) {
					return \\LearnPress\\Tests\\Unit\\AI\\Assistant\\AIAssistantControllerState::$user;
				}
				function __( $text, $domain = null ) {
					return (string) $text;
				}
				function absint( $value ) {
					return abs( (int) $value );
				}
				function sanitize_key( $value ) {
					return preg_replace( "/[^a-z0-9_]/", "", strtolower( (string) $value ) );
				}
				function sanitize_textarea_field( $value ) {
					return is_scalar( $value ) ? trim( (string) $value ) : "";
				}
				function sanitize_text_field( $value ) {
					return is_scalar( $value ) ? trim( (string) $value ) : "";
				}'
			);
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

		// Minimal PostModel: only the status constant and post_status are exercised.
		if ( ! class_exists( '\\LearnPress\\Models\\PostModel', false ) ) {
			eval(
				'namespace LearnPress\\Models;
				class PostModel {
					const STATUS_PUBLISH = "publish";
					public $ID = 0;
					public $post_type = "";
					public $post_status = "publish";
					public function __construct( int $id = 0, string $post_type = "", string $post_status = "publish" ) {
						$this->ID = $id;
						$this->post_type = $post_type;
						$this->post_status = $post_status;
					}
				}'
			);
		}

		/**
		 * Curriculum is keyed [ item_type ][ item_id ], mirroring the composite identity
		 * the real CourseModel::get_item_model() enforces via get_section_of_item().
		 */
		if ( ! class_exists( '\\LearnPress\\Models\\CourseModel', false ) ) {
			eval(
				'namespace LearnPress\\Models;
				class CourseModel {
					public static $found = null;
					public static array $curriculum = array();
					public static function find( int $course_id, bool $check_cache = false ) {
						return self::$found;
					}
					public function get_item_model( int $item_id, string $item_type, bool $check_assign = true ) {
						return self::$curriculum[ $item_type ][ $item_id ] ?? false;
					}
				}'
			);
		}

		if ( ! class_exists( 'LP_User', false ) ) {
			eval(
				'class LP_User {
					public static bool $can_view_course = true;
					public static bool $can_view_item = true;
					public static string $deny_message = "This content is protected.";
					public function can_view_content_course( int $course_id = 0 ) {
						$view = new \\stdClass();
						$view->flag = self::$can_view_course;
						$view->message = self::$can_view_course ? "" : self::$deny_message;
						return $view;
					}
					public function can_view_item( int $item_id = 0, $view = null ) {
						$item_view = new \\stdClass();
						$item_view->flag = self::$can_view_item;
						$item_view->message = self::$can_view_item ? "" : self::$deny_message;
						return $item_view;
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
					public function run( string $message, int $item_id, string $item_type, int $course_id, int $user_id, array $history = array(), array $active_quiz_questions = array(), ?string $action_hint = null ): array {
						self::$captured = array(
							"message" => $message,
							"item_id" => $item_id,
							"item_type" => $item_type,
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

	/**
	 * Put a published lesson 42 and a published quiz 42 in the same course, so every
	 * test starts from the state where the numeric ID alone is ambiguous.
	 */
	private function seed_course_with_colliding_ids(): void {
		AIAssistantControllerState::$user_id = 55;
		AIAssistantControllerState::$user    = new \LP_User();

		\LP_User::$can_view_course = true;
		\LP_User::$can_view_item   = true;

		\LearnPress\Models\CourseModel::$found      = new \LearnPress\Models\CourseModel();
		\LearnPress\Models\CourseModel::$curriculum = array(
			'lp_lesson' => array( 42 => new \LearnPress\Models\PostModel( 42, 'lp_lesson' ) ),
			'lp_quiz'   => array( 42 => new \LearnPress\Models\PostModel( 42, 'lp_quiz' ) ),
		);

		\LearnPress\AI\Assistant\Agent::$captured = array();
	}

	private function chat_payload( array $overrides = array() ): array {
		return array_merge(
			array(
				'message'   => 'hello',
				'course_id' => 20,
				'item_type' => 'lp_lesson',
				'item_id'   => 42,
			),
			$overrides
		);
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
			'ai_assistant_summarize_enabled'  => 'no',
			'ai_assistant_explain_enabled'    => 'yes',
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
	public function test_supported_item_types_are_lesson_and_quiz_only(): void {
		$this->load_controller_with_stubs();

		$types = \LearnPress\AI\Assistant\AIAssistantController::get_supported_item_types();

		$this->assertSame( array( 'lp_lesson', 'lp_quiz' ), $types );
		$this->assertNotContains( 'lp_question', $types );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_handle_chat_throws_when_required_fields_missing(): void {
		$this->load_controller_with_stubs();
		$this->seed_course_with_colliding_ids();

		$controller = new \LearnPress\AI\Assistant\AIAssistantController();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Message is required.' );
		$controller->handle_chat( $this->chat_payload( array( 'message' => '   ' ) ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_handle_chat_requires_item_type(): void {
		$this->load_controller_with_stubs();
		$this->seed_course_with_colliding_ids();

		$controller = new \LearnPress\AI\Assistant\AIAssistantController();
		$payload    = $this->chat_payload();
		unset( $payload['item_type'] );

		try {
			$controller->handle_chat( $payload );
			$this->fail( 'Expected \Exception for a missing item_type.' );
		} catch ( \Exception $e ) {
			$this->assertStringContainsString( 'not available for this type', $e->getMessage() );
		}

		// Denied before the Agent ran, so no OpenAI request was possible.
		$this->assertSame( array(), \LearnPress\AI\Assistant\Agent::$captured );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_handle_chat_rejects_unsupported_item_type(): void {
		$this->load_controller_with_stubs();
		$this->seed_course_with_colliding_ids();

		$controller = new \LearnPress\AI\Assistant\AIAssistantController();

		try {
			$controller->handle_chat( $this->chat_payload( array( 'item_type' => 'lp_question' ) ) );
			$this->fail( 'Expected \Exception for an unsupported item_type.' );
		} catch ( \Exception $e ) {
			$this->assertStringContainsString( 'not available for this type', $e->getMessage() );
		}

		$this->assertSame( array(), \LearnPress\AI\Assistant\Agent::$captured );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_handle_chat_resolves_quiz_and_lesson_sharing_one_id_independently(): void {
		$this->load_controller_with_stubs();
		$this->seed_course_with_colliding_ids();

		$controller = new \LearnPress\AI\Assistant\AIAssistantController();

		$controller->handle_chat( $this->chat_payload( array( 'item_type' => 'lp_lesson' ) ) );
		$this->assertSame( 'lp_lesson', \LearnPress\AI\Assistant\Agent::$captured['item_type'] );
		$this->assertSame( 42, \LearnPress\AI\Assistant\Agent::$captured['item_id'] );

		$controller->handle_chat( $this->chat_payload( array( 'item_type' => 'lp_quiz' ) ) );
		$this->assertSame( 'lp_quiz', \LearnPress\AI\Assistant\Agent::$captured['item_type'] );
		$this->assertSame( 42, \LearnPress\AI\Assistant\Agent::$captured['item_id'] );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_handle_chat_denies_when_declared_type_does_not_match_the_record(): void {
		$this->load_controller_with_stubs();
		$this->seed_course_with_colliding_ids();

		// Only a quiz exists at ID 77; a request declaring it a lesson must not resolve it.
		\LearnPress\Models\CourseModel::$curriculum = array(
			'lp_quiz' => array( 77 => new \LearnPress\Models\PostModel( 77, 'lp_quiz' ) ),
		);

		$controller = new \LearnPress\AI\Assistant\AIAssistantController();

		$this->expectException( \Exception::class );
		$controller->handle_chat( $this->chat_payload( array( 'item_type' => 'lp_lesson', 'item_id' => 77 ) ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_handle_chat_denies_item_belonging_to_another_course(): void {
		$this->load_controller_with_stubs();
		$this->seed_course_with_colliding_ids();

		// The resolved course has no such item: the pair came from two different courses.
		\LearnPress\Models\CourseModel::$curriculum = array();

		$controller = new \LearnPress\AI\Assistant\AIAssistantController();

		$this->expectException( \Exception::class );
		$controller->handle_chat( $this->chat_payload() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_handle_chat_denies_missing_course(): void {
		$this->load_controller_with_stubs();
		$this->seed_course_with_colliding_ids();

		\LearnPress\Models\CourseModel::$found = false;

		$controller = new \LearnPress\AI\Assistant\AIAssistantController();

		$this->expectException( \Exception::class );
		$controller->handle_chat( $this->chat_payload() );
	}

	/**
	 * @return array<string, array{0:string}>
	 */
	public static function unpublished_status_provider(): array {
		return array(
			'draft'   => array( 'draft' ),
			'pending' => array( 'pending' ),
			'private' => array( 'private' ),
			'trash'   => array( 'trash' ),
		);
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_handle_chat_denies_unpublished_items(): void {
		$this->load_controller_with_stubs();

		foreach ( array_keys( self::unpublished_status_provider() ) as $status ) {
			$this->seed_course_with_colliding_ids();
			\LearnPress\Models\CourseModel::$curriculum = array(
				'lp_lesson' => array( 42 => new \LearnPress\Models\PostModel( 42, 'lp_lesson', $status ) ),
			);

			$controller = new \LearnPress\AI\Assistant\AIAssistantController();

			try {
				$controller->handle_chat( $this->chat_payload() );
				$this->fail( "Expected \\Exception for post_status '$status'." );
			} catch ( \Exception $e ) {
				$this->assertNotSame( '', $e->getMessage() );
			}

			$this->assertSame( array(), \LearnPress\AI\Assistant\Agent::$captured, "status '$status' reached the Agent" );
		}
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_handle_chat_denies_when_course_access_is_refused(): void {
		$this->load_controller_with_stubs();
		$this->seed_course_with_colliding_ids();

		// Not enrolled: can_view_item() returns the course-level denial unchanged.
		\LP_User::$can_view_course = false;
		\LP_User::$can_view_item   = false;

		$controller = new \LearnPress\AI\Assistant\AIAssistantController();

		try {
			$controller->handle_chat( $this->chat_payload() );
			$this->fail( 'Expected \Exception when item access is refused.' );
		} catch ( \Exception $e ) {
			$this->assertStringContainsString( 'protected', strtolower( $e->getMessage() ) );
		}

		$this->assertSame( array(), \LearnPress\AI\Assistant\Agent::$captured );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_handle_chat_allows_preview_item_when_item_rule_overrides_course_denial(): void {
		$this->load_controller_with_stubs();
		$this->seed_course_with_colliding_ids();

		// Preview lesson: course access denied, item-level rule grants it.
		\LP_User::$can_view_course = false;
		\LP_User::$can_view_item   = true;

		$controller = new \LearnPress\AI\Assistant\AIAssistantController();
		$result     = $controller->handle_chat( $this->chat_payload() );

		$this->assertSame( 'text', $result['type'] );
		$this->assertSame( 'lp_lesson', \LearnPress\AI\Assistant\Agent::$captured['item_type'] );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_handle_chat_denies_guest(): void {
		$this->load_controller_with_stubs();
		$this->seed_course_with_colliding_ids();

		AIAssistantControllerState::$user_id = 0;

		$controller = new \LearnPress\AI\Assistant\AIAssistantController();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'User must be logged in.' );
		$controller->handle_chat( $this->chat_payload() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_handle_chat_sanitizes_history_and_active_quiz_state_before_agent(): void {
		$this->load_controller_with_stubs();
		$this->seed_course_with_colliding_ids();

		$controller = new \LearnPress\AI\Assistant\AIAssistantController();
		$result     = $controller->handle_chat(
			$this->chat_payload(
				array(
					'message'               => '  hello  ',
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
					'action_hint'           => 'quick-quiz',
				)
			)
		);

		$captured = \LearnPress\AI\Assistant\Agent::$captured;

		$this->assertSame( 'text', $result['type'] );
		$this->assertSame( 'hello', $captured['message'] );
		$this->assertSame( 55, $captured['user_id'] );
		$this->assertSame( 20, $captured['course_id'] );
		$this->assertSame( 42, $captured['item_id'] );
		$this->assertSame( 'lp_lesson', $captured['item_type'] );
		$this->assertSame( 1, count( $captured['history'] ) );
		$this->assertSame( 'ask me', $captured['history'][0]['content'] );
		$this->assertTrue( $captured['active_quiz_questions']['is_active'] );
		$this->assertSame( 2, $captured['active_quiz_questions']['current_index'] );
		$this->assertSame( 'Q1', $captured['active_quiz_questions']['questions'][0]['question'] );
		$this->assertSame( 'quick_quiz', $captured['action_hint'] );
	}
}

class AIAssistantControllerState {
	public static int $user_id = 0;
	public static $user        = null;
}
