<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Course;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Regression: args of list courses must be built from an allow-list of URL params,
 * never from the whole $_GET (ex: ?html_loading_after_content_loaded=[gallery ...]).
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
class ListCoursesTemplateParamsUrlTest extends BrainMonkeyTestCase {
	private $is_category = false;
	private $is_tag      = false;

	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WP_Query', false ) ) {
			eval( 'class WP_Query { public $vars = []; public function get( $k ) { return $this->vars[ $k ] ?? ""; } }' );
		}
		if ( ! class_exists( 'WP_Term', false ) ) {
			eval( 'class WP_Term { public $term_id = 0; }' );
		}
		if ( ! class_exists( 'LP_Helper', false ) ) {
			eval(
				'class LP_Helper { public static function sanitize_params_submitted( $value, $type = "text", $unslash = true ) {
					if ( is_array( $value ) ) { return array_map( fn( $v ) => self::sanitize_params_submitted( $v, $type, $unslash ), $value ); }
					return is_string( $value ) ? trim( strip_tags( $unslash ? stripslashes( $value ) : $value ) ) : $value;
				} }'
			);
		}
		if ( ! class_exists( 'LP_Request', false ) ) {
			eval(
				'class LP_Request { public static function get_param( $key, $default = "", $type = "text", $method = "" ) {
					return LP_Helper::sanitize_params_submitted( $_GET[ $key ] ?? $default, $type );
				} }'
			);
		}

		Functions\when( 'learn_press_is_course_category' )->alias( fn() => $this->is_category );
		Functions\when( 'learn_press_is_course_tag' )->alias( fn() => $this->is_tag );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/course-category/php/' );

		$_GET                = [];
		$GLOBALS['wp_query'] = new \WP_Query();
	}

	protected function tearDown(): void {
		$_GET = [];
		unset( $GLOBALS['wp_query'] );
		parent::tearDown();
	}

	public function test_get_params_url_only_returns_keys_on_allow_list(): void {
		$_GET = [
			'c_search'                          => 'php <b>basic</b>',
			'term_id'                           => '1,2',
			'order_by'                          => 'post_title',
			'paged'                             => '3',
			'html_loading_after_content_loaded' => '[gallery include="1" link="file"]',
			'html_loading_before_show_content'  => '[audio src="x.mp3"]',
			'html_no_load_ajax_first'           => '[leak]',
			'url_current'                       => 'https://evil.example/',
			'c_status'                          => 'draft',
			'c_fields'                          => 'post_content',
			'attributes'                        => [ 'courseQuery' => [ 'limit' => 999 ] ],
			'foo'                               => '[leak id=7]',
		];

		$this->assertSame(
			[
				'paged'    => '3',
				'order_by' => 'post_title',
				'c_search' => 'php basic',
				'term_id'  => '1,2',
			],
			ListCoursesTemplate::get_params_url()
		);
	}

	public function test_get_params_url_empty_request_returns_empty_array(): void {
		$this->assertSame( [], ListCoursesTemplate::get_params_url() );
	}

	public function test_get_params_url_sets_values_detected_by_server(): void {
		$_GET                                 = [
			'paged'                => '9',
			'page_term_id_current' => '777',
			'page_term_url'        => 'https://evil.example/',
		];
		$GLOBALS['wp_query']->vars['paged']   = 2;
		$this->is_category                    = true;
		$term                                 = new \WP_Term();
		$term->term_id                        = 15;
		Functions\when( 'get_queried_object' )->justReturn( $term );

		$this->assertSame(
			[
				'paged'                => 2,
				'page_term_id_current' => 15,
				'page_term_url'        => 'https://example.com/course-category/php/',
			],
			ListCoursesTemplate::get_params_url()
		);
	}

	public function test_get_params_url_strips_html_keys_added_by_filter_and_accepts_extra_allowed_keys(): void {
		$_GET = [ 'c_price' => 'free' ];
		Filters\expectApplied( 'learn-press/list-courses/params-url/keys-allow' )
			->once()
			->andReturnUsing( static fn( $keys ) => array_merge( $keys, [ 'c_price' ] ) );
		Filters\expectApplied( 'lp/template/archive-course/skeleton/args' )
			->once()
			->andReturnUsing(
				static fn( $args ) => array_merge(
					$args,
					[
						'pll-current-lang'                  => 'vi',
						'html_loading_after_content_loaded' => '[leak]',
					]
				)
			);

		$this->assertSame(
			[
				'c_price'          => 'free',
				'pll-current-lang' => 'vi',
			],
			ListCoursesTemplate::get_params_url()
		);
	}
}
