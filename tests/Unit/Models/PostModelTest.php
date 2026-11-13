<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models;

use Brain\Monkey\Functions;
use LearnPress\Models\PostModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * Unit tests for PostModel metadata snapshot helpers.
 */
class PostModelTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'maybe_serialize' )->alias(
			static function ( $data ) {
				return ( is_array( $data ) || is_object( $data ) ) ? serialize( $data ) : $data; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			}
		);
	}

	private function make_post( array $meta ): PostModel {
		$post            = new PostModel();
		$post->meta_data = (object) $meta;

		return $post;
	}

	public function test_get_metadata_snapshot_serializes_values(): void {
		$post = $this->make_post(
			[
				'_lp_level' => 'beginner',
				'_el_data'  => [ 'a' => 1 ],
			]
		);

		$snapshot = $post->get_metadata_snapshot();

		$this->assertSame( 'beginner', $snapshot['_lp_level'] );
		$this->assertSame( serialize( [ 'a' => 1 ] ), $snapshot['_el_data'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
	}

	public function test_keep_only_changed_metadata_removes_unchanged_meta(): void {
		$post     = $this->make_post(
			[
				'_lp_level'   => 'beginner',
				'_el_data'    => [ 'a' => 1 ],
				'_lp_retake'  => '1',
			]
		);
		$snapshot = $post->get_metadata_snapshot();

		$post->meta_data->_lp_level = 'expert';
		$post->meta_data->_lp_new   = 'yes';

		$post->keep_only_changed_metadata( $snapshot );

		$this->assertSame(
			[
				'_lp_level' => 'expert',
				'_lp_new'   => 'yes',
			],
			(array) $post->meta_data
		);
	}

	public function test_keep_only_changed_metadata_detects_type_change(): void {
		$post     = $this->make_post( [ '_el_data' => [ 'a' => 1 ] ] );
		$snapshot = $post->get_metadata_snapshot();

		$post->meta_data->_el_data = (object) [ 'a' => 1 ];
		$post->keep_only_changed_metadata( $snapshot );

		$this->assertObjectHasProperty( '_el_data', $post->meta_data );
	}

	public function test_keep_only_changed_metadata_with_nothing_changed_returns_empty(): void {
		$post     = $this->make_post( [ '_lp_level' => 'beginner' ] );
		$snapshot = $post->get_metadata_snapshot();

		$post->keep_only_changed_metadata( $snapshot );

		$this->assertSame( [], (array) $post->meta_data );
	}
}
