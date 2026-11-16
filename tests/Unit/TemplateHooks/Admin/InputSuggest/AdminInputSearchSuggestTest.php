<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Admin\InputSuggest;

use Brain\Monkey\Functions;
use LearnPress\TemplateHooks\Admin\InputSuggest\AdminInputSearchSuggest;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * Unit tests for the HTML structure and escaping of AdminInputSearchSuggest::layout().
 */
class AdminInputSearchSuggestTest extends BrainMonkeyTestCase {
	/**
	 * @var string[] Script handles enqueued by layout().
	 */
	private $enqueued = [];

	protected function setUp(): void {
		parent::setUp();

		$this->enqueued = [];
		Functions\when( 'wp_enqueue_script' )->alias(
			function ( $handle ) {
				$this->enqueued[] = $handle;
			}
		);
	}

	/**
	 * Collapse the whitespace between attributes, the markup is written on several lines.
	 */
	private function layout( array $data = [] ): string {
		return preg_replace( '/\s+/', ' ', $this->component()->layout( $data ) );
	}

	private function component(): AdminInputSearchSuggest {
		return new class() extends AdminInputSearchSuggest {
		};
	}

	public function test_layout_empty_data_prints_structure(): void {
		$html = $this->layout();

		$this->assertSame(
			'<div class=" lp-wrap-input-search-suggest ">'
			. '<input type="text" class="lp-input-search-suggest" autocomplete="off" value="" placeholder="">'
			. '<input type="hidden" class="lp-input-search-suggest-value" name="" value="">'
			. '<div class="lp-search-suggest-result"></div>'
			. '</div>',
			$html
		);
	}

	public function test_layout_enqueues_script(): void {
		$this->component()->layout();

		$this->assertSame( [ 'lp-input-search-suggest' ], $this->enqueued );
	}

	public function test_layout_prints_data(): void {
		$html = $this->layout(
			[
				'id'          => 'my-id',
				'name'        => 'course_id',
				'value'       => 15,
				'text'        => 'Course A',
				'placeholder' => 'Search course',
				'class-wrap'  => 'my-class',
				'html_result' => '<div class="lp-target"></div>',
			]
		);

		$this->assertStringContainsString( '<div class=" lp-wrap-input-search-suggest my-class">', $html );
		$this->assertStringContainsString( '<input type="text" id="my-id" class="lp-input-search-suggest" autocomplete="off" value="Course A" placeholder="Search course">', $html );
		$this->assertStringContainsString( 'class="lp-input-search-suggest-value" name="course_id" value="15">', $html );
		$this->assertStringContainsString( '<div class="lp-search-suggest-result"><div class="lp-target"></div></div>', $html );
	}

	/**
	 * @dataProvider provider_escaped_keys
	 */
	public function test_layout_escapes_attribute( string $key ): void {
		$html = $this->component()->layout( [ $key => '"><script>alert(1)</script>' ] );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;', $html );
	}

	public static function provider_escaped_keys(): array {
		return [
			'id'          => [ 'id' ],
			'name'        => [ 'name' ],
			'value'       => [ 'value' ],
			'text'        => [ 'text' ],
			'placeholder' => [ 'placeholder' ],
			'class-wrap'  => [ 'class-wrap' ],
		];
	}

	public function test_html_list_result_single_has_no_checkbox(): void {
		$html = AdminInputSearchSuggest::html_list_result(
			[
				[
					'id'      => 1,
					'text'    => 'A',
					'checked' => true,
				],
			]
		);

		$this->assertStringNotContainsString( 'checkbox', $html );
		$this->assertStringNotContainsString( 'lp-search-suggest-clear-all', $html );
		$this->assertStringNotContainsString( 'is-selected', $html );
		$this->assertStringNotContainsString( 'data-type-select', $html );
	}

	public function test_html_list_result_multiple_prints_checkbox(): void {
		$html = preg_replace(
			'/\s+/',
			' ',
			AdminInputSearchSuggest::html_list_result(
				[
					[
						'id'   => 1,
						'text' => 'A',
					],
					[
						'id'      => 2,
						'text'    => 'B',
						'label'   => 'B label',
						'checked' => true,
					],
				],
				[ 'type_select' => 'multiple' ]
			)
		);

		$this->assertStringContainsString( '<li class="lp-search-suggest-item lp-search-suggest-item--multiple" data-id="1" data-text="A" data-type-select="multiple">', $html );
		$this->assertStringContainsString( '<input type="checkbox" class="lp-search-suggest-checkbox" tabindex="-1" value="1">A (#1)', $html );
		$this->assertStringContainsString( '<li class="lp-search-suggest-item lp-search-suggest-item--multiple is-selected" data-id="2" data-text="B" data-type-select="multiple">', $html );
		$this->assertStringContainsString( 'value="2" checked>B label (#2)', $html );
		// The button "Clear all" is the first row and does not submit a form.
		$this->assertStringContainsString( '<ul class="lp-search-suggest-list"><li class="lp-search-suggest-actions"> <button type="button" class="lp-search-suggest-clear-all">Clear all</button> </li>', $html );
		$this->assertSame( 1, substr_count( $html, 'lp-search-suggest-clear-all' ) );
	}

	public function test_html_list_result_multiple_escapes_item(): void {
		$html = AdminInputSearchSuggest::html_list_result(
			[
				[
					'id'   => '"><script>alert(1)</script>',
					'text' => '"><script>alert(2)</script>',
				],
			],
			[ 'type_select' => 'multiple' ]
		);

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( 'value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $html );
	}

	public function test_html_list_result_unknown_type_select_is_single(): void {
		$html = AdminInputSearchSuggest::html_list_result(
			[
				[
					'id'   => 1,
					'text' => 'A',
				],
			],
			[ 'type_select' => 'other' ]
		);

		$this->assertStringNotContainsString( 'checkbox', $html );
	}

	public function test_html_list_result_multiple_empty_prints_no_results(): void {
		$html = preg_replace(
			[ '/\s+/', '/>\s+</' ],
			[ ' ', '><' ],
			AdminInputSearchSuggest::html_list_result( [], [ 'type_select' => 'multiple' ] )
		);

		$this->assertSame(
			'<ul class="lp-search-suggest-list">'
			. '<li class="lp-search-suggest-actions"><button type="button" class="lp-search-suggest-clear-all">Clear all</button></li>'
			. '<li class="lp-search-suggest-empty">No results</li></ul>',
			$html
		);
	}

	public function test_layout_multiple_prints_wrapper_attribute_and_default_value(): void {
		$html = $this->layout(
			[
				'name'           => 'ids',
				'type_select'    => 'multiple',
				'items_selected' => [
					[
						'id'   => 1,
						'text' => 'A',
					],
					[
						'id'   => 2,
						'text' => 'B',
					],
				],
			]
		);

		$this->assertStringContainsString( '<div class=" lp-wrap-input-search-suggest " data-type-select="multiple">', $html );
		$this->assertStringContainsString( 'name="ids" value="1,2">', $html );
		$this->assertStringContainsString( '<div class="lp-search-suggest-result lp-hidden">', $html );
	}

	public function test_layout_prints_html_result_as_is(): void {
		$html = $this->component()->layout( [ 'html_result' => '<div class="lp-target" data-send="{}"></div>' ] );

		$this->assertStringContainsString( '<div class="lp-target" data-send="{}"></div>', $html );
	}
}
