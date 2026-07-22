<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Webhook;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use LearnPress\Webhook\WebhookResourceSerializer;

/**
 * Tests for outbound webhook payload enrichment.
 */
class WebhookResourceSerializerTest extends BrainMonkeyTestCase {
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'absint' )->alias(
			static function ( $value ): int {
				return abs( (int) $value );
			}
		);

		Functions\when( 'sanitize_text_field' )->alias(
			static function ( $value ): string {
				return trim( strip_tags( (string) $value ) );
			}
		);

		Functions\when( 'sanitize_key' )->alias(
			static function ( $value ): string {
				return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $value ) );
			}
		);

		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		Functions\when( 'home_url' )->alias(
			static function ( string $path = '' ): string {
				return 'https://learnpress.test' . $path;
			}
		);

		Functions\when( 'get_userdata' )->alias(
			static function ( int $user_id ) {
				if ( 7 !== $user_id ) {
					return false;
				}

				return (object) array(
					'ID'           => 7,
					'user_login'   => 'jane',
					'display_name' => 'Jane Doe',
				);
			}
		);

		Functions\when( 'get_post' )->alias(
			static function ( int $post_id ) {
				$posts = array(
					55  => array( 'lp_course', 'PHP Basics', 'php-basics' ),
					99  => array( 'lp_quiz', 'Final Quiz', 'final-quiz' ),
					101 => array( 'lp_lesson', 'Getting Started', 'getting-started' ),
					123 => array( 'lp_order', 'Order on June 4, 2026', 'order-123' ),
				);

				if ( ! isset( $posts[ $post_id ] ) ) {
					return null;
				}

				return (object) array(
					'ID'         => $post_id,
					'post_type'  => $posts[ $post_id ][0],
					'post_title' => $posts[ $post_id ][1],
					'post_name'  => $posts[ $post_id ][2],
				);
			}
		);

		Functions\when( 'get_post_type' )->alias(
			static function ( int $post_id ): string {
				return 55 === $post_id ? 'lp_course' : '';
			}
		);

		Functions\when( 'get_permalink' )->alias(
			static function ( int $post_id ): string {
				return 'https://learnpress.test/?p=' . $post_id;
			}
		);

		Functions\when( 'learn_press_get_order' )->alias(
			static function ( int $order_id ) {
				if ( 123 !== $order_id ) {
					return null;
				}

				return new class() {
					public function get_order_number(): string {
						return 'LP-123';
					}

					public function get_title(): string {
						return 'Order on June 4, 2026';
					}

					public function get_status(): string {
						return 'completed';
					}
				};
			}
		);
	}

	public function test_build_payload_adds_api_version_names_and_order_item_snapshots(): void {
		$serializer = new WebhookResourceSerializer();

		$payload = $serializer->build_payload(
			'order.completed',
			'lpwh_test',
			array(
				'order_id' => 123,
				'user_id'  => 7,
				'items'    => array(
					array(
						'order_item_id'   => 456,
						'order_item_name' => '',
						'item_id'         => 55,
						'item_type'       => 'lp_course',
						'quantity'        => 1,
					),
				),
			)
		);

		$this->assertSame( 'v1', $payload['api_version'] );
		$this->assertSame( 'LP-123', $payload['data']['order_number'] );
		$this->assertSame( 'Order on June 4, 2026', $payload['data']['order_title'] );
		$this->assertSame( 'Jane Doe', $payload['data']['user_name'] );
		$this->assertSame( array( 'PHP Basics' ), $payload['data']['order_item_names'] );
		$this->assertSame( 'PHP Basics', $payload['data']['items'][0]['item_name'] );
		$this->assertSame( 'lp_course', $payload['data']['items'][0]['item']['type'] );
	}

	public function test_user_item_enrichment_adds_course_names_duration_and_strips_sensitive_result_fields(): void {
		$serializer = new WebhookResourceSerializer();

		$data = $serializer->enrich_data(
			'lesson.completed',
			array(
				'user_id'   => 7,
				'item_id'   => 101,
				'item_type' => 'lp_lesson',
				'ref_id'    => 55,
				'ref_type'  => 'lp_course',
				'user_item' => array(
					'user_item_id' => 789,
					'user_id'      => 7,
					'item_id'      => 101,
					'item_type'    => 'lp_lesson',
					'status'       => 'completed',
					'graduation'   => 'passed',
					'ref_id'       => 55,
					'ref_type'     => 'lp_course',
					'start_time'   => '2026-06-04 09:00:00',
					'end_time'     => '2026-06-04 09:20:00',
					'result'       => array(
						'user_mark' => 8,
						'questions' => array( 'hidden' ),
						'files'     => array( 'hidden.pdf' ),
					),
				),
			)
		);

		$this->assertSame( 55, $data['course_id'] );
		$this->assertSame( 'Jane Doe', $data['user_item']['user_name'] );
		$this->assertSame( 'Getting Started', $data['user_item']['item_name'] );
		$this->assertSame( 'PHP Basics', $data['user_item']['course_name'] );
		$this->assertSame( 1200, $data['user_item']['duration_seconds'] );
		$this->assertSame( 8, $data['user_item']['result']['user_mark'] );
		$this->assertArrayNotHasKey( 'questions', $data['user_item']['result'] );
		$this->assertArrayNotHasKey( 'files', $data['user_item']['result'] );
	}

	public function test_missing_resources_keep_ids_and_empty_names(): void {
		$serializer = new WebhookResourceSerializer();

		$data = $serializer->enrich_data(
			'assignment.submitted',
			array(
				'user_id'       => 404,
				'assignment_id' => 999,
				'course_id'     => 888,
			)
		);

		$this->assertSame( 404, $data['user_id'] );
		$this->assertSame( '', $data['user_name'] );
		$this->assertSame( 999, $data['assignment']['id'] );
		$this->assertSame( '', $data['assignment_name'] );
		$this->assertSame( 888, $data['course']['id'] );
		$this->assertSame( '', $data['course_name'] );
	}
}
