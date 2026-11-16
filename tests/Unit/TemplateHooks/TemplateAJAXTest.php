<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks;

use Brain\Monkey\Functions;
use LearnPress\TemplateHooks\TemplateAJAX;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * Regression: args (can contain request params) are printed as JSON in attribute data-send.
 * WP do_shortcode() runs shortcodes inside HTML attributes of post content,
 * so square brackets must be encoded, ex: ?foo=[gallery include=1 link=file].
 */
class TemplateAJAXTest extends BrainMonkeyTestCase {
	protected function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'lp_skeleton_animation_html' ) ) {
			eval( 'function lp_skeleton_animation_html( $count = 3 ) { echo "<ul></ul>"; }' );
		}
		Functions\when( 'htmlentities2' )->alias( static fn( $s ) => (string) $s );
		Functions\when( 'esc_attr' )->alias( static fn( $s ) => htmlspecialchars( (string) $s, ENT_QUOTES ) );
	}

	public function test_load_content_via_ajax_encodes_square_brackets_in_data_send(): void {
		$args     = [
			'foo'      => '[gallery include=1 link=file]',
			'c_search' => '[leak id=8]',
			'ids'      => [ 1, 2 ],
		];
		$callback = [
			'class'  => 'Foo',
			'method' => 'bar',
		];

		$output = TemplateAJAX::load_content_via_ajax( $args, $callback );

		$this->assertSame( 1, preg_match( '/data-send="([^"]*)"/', $output, $matches ) );
		$this->assertStringNotContainsString( '[', $matches[1] );
		$this->assertStringNotContainsString( ']', $matches[1] );

		// Browser decodes entities, JS still reads the same data.
		$data = json_decode( html_entity_decode( $matches[1], ENT_QUOTES | ENT_HTML5 ), true );
		$this->assertSame( $args, $data['args'] );
		$this->assertSame( $callback, $data['callback'] );
	}
}
