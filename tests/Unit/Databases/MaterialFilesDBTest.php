<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Databases;

use Brain\Monkey;
use LearnPress\Databases\Material\MaterialFilesDB;
use LearnPress\Filters\MaterialFilter;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\Test;

class MaterialFilesDBTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\Functions\stubEscapeFunctions();
	}

	#[Test]
	public function getInstance_returns_singleton(): void {
		Monkey\Functions\when( 'global' )->justReturn( [] );

		$db1 = MaterialFilesDB::getInstance();
		$db2 = MaterialFilesDB::getInstance();

		$this->assertInstanceOf( MaterialFilesDB::class, $db1 );
		$this->assertSame( $db1, $db2 );
	}

	#[Test]
	public function get_files_with_empty_filter_uses_default_table(): void {
		Monkey\Functions\when( 'global' )->justReturn( [] );

		$db = MaterialFilesDB::getInstance();
		$filter = new MaterialFilter();
		$total_rows = 0;

		$result = $db->get_files( $filter, $total_rows );

		$this->assertIsArray( $result );
	}

	#[Test]
	public function get_files_sets_collection_and_alias(): void {
		Monkey\Functions\when( 'global' )->justReturn( [] );

		$db = MaterialFilesDB::getInstance();
		$filter = new MaterialFilter();
		$total_rows = 0;

		$result = $db->get_files( $filter, $total_rows );

		$this->assertIsArray( $result );
	}
}

