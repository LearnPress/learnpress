<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Course;

use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class CourseAIAssistantTemplateTest extends BrainMonkeyTestCase {

	private function load_template_with_stubs(): void {
		if ( ! function_exists( '\\LearnPress\\TemplateHooks\\Course\\is_user_logged_in' ) ) {
			eval(
				'namespace LearnPress\\TemplateHooks\\Course;
				function __( $text, $domain = null ) { return (string) $text; }
				function esc_html__( $text, $domain = null ) { return (string) $text; }
				function esc_attr__( $text, $domain = null ) { return (string) $text; }
				function esc_html( $text ) { return (string) $text; }
				function apply_filters( $hook, $value ) { return $value; }
				function wp_json_encode( $data ) { return json_encode( $data ); }
				function wp_create_nonce( $action ) { return "nonce-test"; }
				function wp_enqueue_script( $handle ) { \\LearnPress\\Tests\\Unit\\TemplateHooks\\Course\\CourseAIAssistantTemplateState::$scripts[] = $handle; }
				function wp_enqueue_style( $handle ) { \\LearnPress\\Tests\\Unit\\TemplateHooks\\Course\\CourseAIAssistantTemplateState::$styles[] = $handle; }
				function wp_add_inline_script( $handle, $data, $position = "after" ) { \\LearnPress\\Tests\\Unit\\TemplateHooks\\Course\\CourseAIAssistantTemplateState::$inline_data = $data; }
				function is_user_logged_in() { return \\LearnPress\\Tests\\Unit\\TemplateHooks\\Course\\CourseAIAssistantTemplateState::$logged_in; }
				function get_current_user_id() { return \\LearnPress\\Tests\\Unit\\TemplateHooks\\Course\\CourseAIAssistantTemplateState::$user_id; }
				function get_post_type( $item_id ) { return \\LearnPress\\Tests\\Unit\\TemplateHooks\\Course\\CourseAIAssistantTemplateState::$post_types[ (int) $item_id ] ?? ""; }
				function absint( $value ) { return abs( (int) $value ); }'
			);
		}

		if ( ! defined( 'LP_PAGE_SINGLE_COURSE_CURRICULUM' ) ) {
			define( 'LP_PAGE_SINGLE_COURSE_CURRICULUM', 'single-course-curriculum' );
		}

		if ( ! function_exists( 'add_action' ) ) {
			function add_action( $hook, $callback ) {
				// No-op for unit tests.
			}
		}

		if ( ! function_exists( '__' ) ) {
			function __( $text, $domain = null ) {
				return (string) $text;
			}
		}

		if ( ! function_exists( 'esc_html__' ) ) {
			function esc_html__( $text, $domain = null ) {
				return (string) $text;
			}
		}

		if ( ! function_exists( 'esc_attr__' ) ) {
			function esc_attr__( $text, $domain = null ) {
				return (string) $text;
			}
		}

		if ( ! function_exists( 'esc_html' ) ) {
			function esc_html( $text ) {
				return (string) $text;
			}
		}

		if ( ! function_exists( 'apply_filters' ) ) {
			function apply_filters( $hook, $value ) {
				return $value;
			}
		}

		if ( ! function_exists( 'wp_json_encode' ) ) {
			function wp_json_encode( $data ) {
				return json_encode( $data );
			}
		}

		if ( ! function_exists( 'wp_create_nonce' ) ) {
			function wp_create_nonce( $action ) {
				return 'nonce-test';
			}
		}

		if ( ! function_exists( 'wp_enqueue_script' ) ) {
			function wp_enqueue_script( $handle ) {
				\LearnPress\Tests\Unit\TemplateHooks\Course\CourseAIAssistantTemplateState::$scripts[] = $handle;
			}
		}

		if ( ! function_exists( 'wp_enqueue_style' ) ) {
			function wp_enqueue_style( $handle ) {
				\LearnPress\Tests\Unit\TemplateHooks\Course\CourseAIAssistantTemplateState::$styles[] = $handle;
			}
		}

		if ( ! function_exists( 'wp_add_inline_script' ) ) {
			function wp_add_inline_script( $handle, $data, $position = 'after' ) {
				\LearnPress\Tests\Unit\TemplateHooks\Course\CourseAIAssistantTemplateState::$inline_data = $data;
			}
		}

		if ( ! function_exists( 'is_user_logged_in' ) ) {
			function is_user_logged_in() {
				return \LearnPress\Tests\Unit\TemplateHooks\Course\CourseAIAssistantTemplateState::$logged_in;
			}
		}

		if ( ! function_exists( 'get_current_user_id' ) ) {
			function get_current_user_id() {
				return \LearnPress\Tests\Unit\TemplateHooks\Course\CourseAIAssistantTemplateState::$user_id;
			}
		}

		if ( ! function_exists( 'get_post_type' ) ) {
			function get_post_type( $item_id ) {
				return \LearnPress\Tests\Unit\TemplateHooks\Course\CourseAIAssistantTemplateState::$post_types[ (int) $item_id ] ?? '';
			}
		}

		if ( ! function_exists( 'absint' ) ) {
			function absint( $value ) {
				return abs( (int) $value );
			}
		}

		if ( ! class_exists( 'LP_Global', false ) ) {
			eval(
				'class LP_Global {
					public static $item = null;
					public static bool $is_quiz = false;
					public static function course_item() {
						return self::$item;
					}
					public static function course_item_quiz() {
						return self::$is_quiz;
					}
				}'
			);
		}

		if ( ! class_exists( 'LP_Page_Controller', false ) ) {
			eval(
				'class LP_Page_Controller {
					public static string $page = "";
					public static function page_current() {
						return self::$page;
					}
				}'
			);
		}

		if ( ! class_exists( 'LP_Settings', false ) ) {
			eval(
				'class LP_Settings {
					public static array $options = array();
					public static function url_handle_lp_ajax(): string {
						return "https://example.test/lp-ajax";
					}
					public static function get_option( string $id, $default = "" ) {
						return self::$options[ $id ] ?? $default;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\AI\\Assistant\\AIAssistantController', false ) ) {
			eval(
				'namespace LearnPress\\AI\\Assistant;
				class AIAssistantController {
					public static bool $enabled = true;
					public static array $enabled_actions = array(
						"summarize" => true,
						"explain" => true,
						"mini_quiz" => true,
						"smart_review" => true,
					);
					public static function is_enabled(): bool {
						return self::$enabled;
					}
					public static function get_enabled_actions(): array {
						return self::$enabled_actions;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\AI\\Assistant\\DataLoaders', false ) ) {
			eval(
				'namespace LearnPress\\AI\\Assistant;
				class DataLoaders {
					public static bool $has_quiz_attempt = false;
					public function has_quiz_attempt( int $user_id, int $course_id ): bool {
						return self::$has_quiz_attempt;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\Helpers\\Template', false ) ) {
			eval(
				'namespace LearnPress\\Helpers;
				class Template {
					public static function combine_components( array $section ): string {
						return implode( "", array_values( $section ) );
					}
					public static function instance(): self {
						return new self();
					}
					public function nest_elements( array $wrap, string $content ): string {
						$open = (string) array_key_first( $wrap );
						$close = (string) current( $wrap );
						return $open . $content . $close;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\Models\\CourseModel', false ) ) {
			eval(
				'namespace LearnPress\\Models;
				class CourseModel {
					public static array $items = array();
					public function __construct( public array $sections = array() ) {}
					public static function find( int $id, bool $cache = true ) {
						return self::$items[ $id ] ?? false;
					}
					public function get_section_items(): array {
						return $this->sections;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\Models\\UserItems\\UserQuizModel', false ) ) {
			eval(
				'namespace LearnPress\\Models\\UserItems;
				class UserQuizModel {
					public static array $items = array();
					public static function find_user_item( int $user_id, int $item_id, string $item_type, int $ref_id, string $ref_type, bool $cache = true ) {
						return self::$items[ $item_id ] ?? false;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\Tests\\Unit\\TemplateHooks\\Course\\UserQuizAttemptsStub', false ) ) {
			eval(
				'namespace LearnPress\\Tests\\Unit\\TemplateHooks\\Course;
				class UserQuizAttemptsStub {
					public function __construct( private array $attempts ) {}
					public function get_attempts( int $limit = 1 ): array {
						return $this->attempts;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\TemplateHooks\\Course\\CourseAIAssistantTemplate', false ) ) {
			require_once dirname( __DIR__, 4 ) . '/inc/TemplateHooks/Course/CourseAIAssistantTemplate.php';
		}
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_render_widget_is_skipped_when_not_curriculum_page(): void {
		$this->load_template_with_stubs();

		\LP_Page_Controller::$page                               = 'other-page';
		\LP_Global::$is_quiz                                     = false;
		CourseAIAssistantTemplateState::$logged_in               = true;
		\LearnPress\AI\Assistant\AIAssistantController::$enabled = true;
		\LP_Global::$item                                        = new CourseAIAssistantItemStub( 10, 20 );

		$template = \LearnPress\TemplateHooks\Course\CourseAIAssistantTemplate::instance();

		ob_start();
		$template->render_widget();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
		$this->assertSame( array(), CourseAIAssistantTemplateState::$scripts );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_free_chat_off_hides_input_area(): void {
		$this->load_template_with_stubs();

		$template = \LearnPress\TemplateHooks\Course\CourseAIAssistantTemplate::instance();

		// free_chat_enabled = false â†’ input area must be absent, quick-only modifier must appear.
		$html = $template->html_panel( false, \LearnPress\AI\Assistant\AIAssistantController::get_enabled_actions() );

		$this->assertStringNotContainsString( 'lp-ai-assistant__input', $html );
		$this->assertStringNotContainsString( 'lp-ai-assistant__send-btn', $html );
		$this->assertStringContainsString( 'lp-ai-assistant__quick-actions', $html );
		$this->assertStringContainsString( 'lp-ai-assistant-panel--quick-only', $html );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_free_chat_on_shows_input_area(): void {
		$this->load_template_with_stubs();

		$template = \LearnPress\TemplateHooks\Course\CourseAIAssistantTemplate::instance();

		// free_chat_enabled = true â†’ textarea + send button must be present, modifier must be absent.
		$html = $template->html_panel( true, \LearnPress\AI\Assistant\AIAssistantController::get_enabled_actions() );

		$this->assertStringContainsString( 'lp-ai-assistant__input-area', $html );
		$this->assertStringContainsString( 'lp-ai-assistant__send-btn', $html );
		$this->assertStringNotContainsString( 'lp-ai-assistant-panel--quick-only', $html );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_disabled_quick_actions_are_not_rendered(): void {
		$this->load_template_with_stubs();
		\LearnPress\AI\Assistant\AIAssistantController::$enabled_actions = array(
			'summarize'    => false,
			'explain'      => true,
			'mini_quiz'    => false,
			'smart_review' => true,
		);

		$template = \LearnPress\TemplateHooks\Course\CourseAIAssistantTemplate::instance();
		$html     = $template->html_quick_actions( \LearnPress\AI\Assistant\AIAssistantController::get_enabled_actions() );

		$this->assertStringNotContainsString( 'Summarize Lesson', $html );
		$this->assertStringContainsString( 'Explain Concept', $html );
		$this->assertStringNotContainsString( 'Mini Quiz', $html );
		$this->assertStringContainsString( 'Smart Review', $html );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_render_widget_outputs_markup_and_has_quiz_attempt_flag(): void {
		$this->load_template_with_stubs();

		\LP_Page_Controller::$page                               = LP_PAGE_SINGLE_COURSE_CURRICULUM;
		\LP_Global::$is_quiz                                     = false;
		CourseAIAssistantTemplateState::$logged_in               = true;
		CourseAIAssistantTemplateState::$user_id                 = 77;
		\LearnPress\AI\Assistant\AIAssistantController::$enabled = true;
		\LP_Global::$item                                        = new CourseAIAssistantItemStub( 15, 30 );
		\LearnPress\AI\Assistant\DataLoaders::$has_quiz_attempt  = true;

		$section                                    = (object) array(
			'items' => array(
				(object) array( 'id' => 900 ),
			),
		);
		\LearnPress\Models\CourseModel::$items      = array(
			30 => new \LearnPress\Models\CourseModel( array( $section ) ),
		);
		CourseAIAssistantTemplateState::$post_types = array( 900 => LP_QUIZ_CPT );
		\LearnPress\Models\UserItems\UserQuizModel::$items = array(
			900 => new UserQuizAttemptsStub(
				array(
					array(
						'result'     => array( 'mark' => '100' ),
						'graduation' => 'passed',
						'end_time'   => '2026-01-01 00:10:00',
					),
				)
			),
		);

		$template = \LearnPress\TemplateHooks\Course\CourseAIAssistantTemplate::instance();

		ob_start();
		$template->render_widget();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="lp-ai-assistant"', $output );
		$this->assertContains( 'lp-ai-assistant', CourseAIAssistantTemplateState::$scripts );
		$this->assertContains( 'lp-ai-assistant', CourseAIAssistantTemplateState::$styles );
		$this->assertStringContainsString( '"hasQuizAttempt":true', (string) CourseAIAssistantTemplateState::$inline_data );
	}
}

class CourseAIAssistantItemStub {
	public function __construct( private int $id, private int $course_id ) {}

	public function get_id(): int {
		return $this->id;
	}

	public function get_course_id(): int {
		return $this->course_id;
	}
}

class CourseAIAssistantTemplateState {
	public static bool $logged_in     = true;
	public static int $user_id        = 1;
	public static array $scripts      = array();
	public static array $styles       = array();
	public static string $inline_data = '';
	public static array $post_types   = array();
}
