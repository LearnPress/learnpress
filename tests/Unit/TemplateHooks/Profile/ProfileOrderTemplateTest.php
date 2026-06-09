<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Profile;

use Brain\Monkey\Functions;
use LearnPress\TemplateHooks\Profile\ProfileOrderTemplate;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\Test;

class ProfileOrderTemplateTest extends BrainMonkeyTestCase {

	private function load_template(): void {
		if ( ! class_exists( ProfileOrderTemplate::class, false ) ) {
			require_once dirname( __DIR__, 4 ) . '/inc/TemplateHooks/Profile/ProfileOrderTemplate.php';
		}
	}

	#[Test]
	public function refund_request_status_maps_internal_statuses_to_customer_labels(): void {
		$this->load_template();

		$status = 'pending';
		$order  = new class() {
			public function get_id(): int {
				return 100;
			}
		};

		Functions\when( 'get_post_meta' )->alias(
			static function() use ( &$status ) {
				return $status;
			}
		);
		Functions\when( 'sanitize_key' )->alias(
			static fn( $value ) => strtolower( (string) $value )
		);

		$expected_labels = array(
			'pending'       => 'Pending',
			'denied'        => 'Rejected',
			'approved'      => 'Accepted',
			'auto-approved' => 'Accepted',
		);

		foreach ( $expected_labels as $status => $expected_label ) {
			$html = ProfileOrderTemplate::refund_request_status_html( $order );

			$this->assertStringContainsString( 'Refund request:', $html );
			$this->assertStringContainsString( 'label-' . $status, $html );
			$this->assertStringContainsString( '>' . $expected_label . '</span>', $html );
		}
	}

	#[Test]
	public function refund_request_status_is_hidden_when_order_has_no_request(): void {
		$this->load_template();

		$order = new class() {
			public function get_id(): int {
				return 101;
			}
		};

		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'sanitize_key' )->alias(
			static fn( $value ) => strtolower( (string) $value )
		);

		$this->assertSame( '', ProfileOrderTemplate::refund_request_status_html( $order ) );
	}

	#[Test]
	public function refund_note_is_displayed_with_safe_line_breaks(): void {
		$this->load_template();

		$order = new class() {
			public function get_id(): int {
				return 102;
			}
		};

		Functions\when( 'get_post_meta' )->justReturn( "Approved by support.\nPlease allow 5 business days." );

		$html = ProfileOrderTemplate::refund_note_html( $order );

		$this->assertStringContainsString( 'Refund note:', $html );
		$this->assertStringContainsString( 'Approved by support.<br', $html );
		$this->assertStringContainsString( 'Please allow 5 business days.', $html );
	}

	#[Test]
	public function refund_note_is_hidden_when_order_has_no_note(): void {
		$this->load_template();

		$order = new class() {
			public function get_id(): int {
				return 103;
			}
		};

		Functions\when( 'get_post_meta' )->justReturn( '' );

		$this->assertSame( '', ProfileOrderTemplate::refund_note_html( $order ) );
	}
}
