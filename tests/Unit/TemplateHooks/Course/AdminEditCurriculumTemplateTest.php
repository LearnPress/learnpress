<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Course;

use Brain\Monkey\Functions;
use LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * Unit tests for item status label on admin edit curriculum.
 */
class AdminEditCurriculumTemplateTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'apply_filters' )->returnArg( 2 );
	}

	public function test_get_item_status_label_for_curriculum_statuses(): void {
		$expected = [
			'publish' => 'Published',
			'draft'   => 'Draft',
			'pending' => 'Pending',
			'private' => 'Private',
			'future'  => 'Scheduled',
			'trash'   => 'Trash',
		];

		foreach ( $expected as $status => $label ) {
			$this->assertSame( $label, AdminEditCurriculumTemplate::get_item_status_label( $status ) );
		}
	}

	public function test_get_item_status_label_falls_back_to_registered_status(): void {
		Functions\when( 'get_post_status_object' )->justReturn( (object) [ 'label' => 'Archived' ] );

		$this->assertSame( 'Archived', AdminEditCurriculumTemplate::get_item_status_label( 'archived' ) );
	}

	public function test_get_item_status_label_falls_back_to_status_key(): void {
		Functions\when( 'get_post_status_object' )->justReturn( null );

		$this->assertSame( 'custom', AdminEditCurriculumTemplate::get_item_status_label( 'custom' ) );
	}
}
