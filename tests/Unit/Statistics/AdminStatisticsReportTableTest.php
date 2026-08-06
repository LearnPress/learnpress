<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Statistics;

use LearnPress\TemplateHooks\Admin\AdminStatisticsReportTable;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * CSV serialization of the statistics report tables: RFC-4180 quoting plus the
 * spreadsheet formula-injection guard. Pure string logic — exercised via
 * reflection so no AJAX/DB runtime is needed (the constructor is skipped so no
 * hooks are registered).
 *
 * @covers \LearnPress\TemplateHooks\Admin\AdminStatisticsReportTable::build_csv
 * @covers \LearnPress\TemplateHooks\Admin\AdminStatisticsReportTable::csv_escape
 */
class AdminStatisticsReportTableTest extends BrainMonkeyTestCase {

	/**
	 * Instance without running the (hook-registering) constructor.
	 */
	private function instance(): AdminStatisticsReportTable {
		return ( new ReflectionClass( AdminStatisticsReportTable::class ) )->newInstanceWithoutConstructor();
	}

	private function escape( $value ): string {
		$method = new ReflectionMethod( AdminStatisticsReportTable::class, 'csv_escape' );
		$method->setAccessible( true );

		return $method->invoke( $this->instance(), $value );
	}

	private function build( array $columns, array $rows ): string {
		$method = new ReflectionMethod( AdminStatisticsReportTable::class, 'build_csv' );
		$method->setAccessible( true );

		return $method->invoke( $this->instance(), $columns, $rows );
	}

	private function invoke( string $name, ...$args ) {
		$method = new ReflectionMethod( AdminStatisticsReportTable::class, $name );
		$method->setAccessible( true );

		return $method->invoke( $this->instance(), ...$args );
	}

	// --- csv_escape --------------------------------------------------------

	public function test_plain_string_is_unchanged(): void {
		$this->assertSame( 'hello world', $this->escape( 'hello world' ) );
	}

	public function test_comma_is_quoted(): void {
		$this->assertSame( '"a,b"', $this->escape( 'a,b' ) );
	}

	public function test_double_quote_is_doubled_and_wrapped(): void {
		$this->assertSame( '"she said ""hi"""', $this->escape( 'she said "hi"' ) );
	}

	public function test_newline_is_quoted(): void {
		$this->assertSame( "\"line1\nline2\"", $this->escape( "line1\nline2" ) );
	}

	public function test_carriage_return_is_quoted(): void {
		$this->assertSame( "\"a\r\nb\"", $this->escape( "a\r\nb" ) );
	}

	/**
	 * @dataProvider formula_injection_prefixes
	 */
	public function test_formula_injection_is_neutralized( string $prefix ): void {
		$this->assertSame( "'" . $prefix . 'cmd', $this->escape( $prefix . 'cmd' ) );
	}

	public static function formula_injection_prefixes(): array {
		return array(
			'equals' => array( '=' ),
			'plus'   => array( '+' ),
			'minus'  => array( '-' ),
			'at'     => array( '@' ),
		);
	}

	public function test_formula_injection_with_comma_is_prefixed_then_quoted(): void {
		// Guard prepends "'", the comma then forces RFC-4180 quoting.
		$this->assertSame( '"\'=1,2"', $this->escape( '=1,2' ) );
	}

	public function test_null_becomes_empty_string(): void {
		$this->assertSame( '', $this->escape( null ) );
	}

	public function test_integer_is_cast_to_string(): void {
		$this->assertSame( '42', $this->escape( 42 ) );
	}

	// --- build_csv ---------------------------------------------------------

	public function test_build_csv_writes_header_and_rows_crlf_separated(): void {
		$columns = array(
			array(
				'label' => 'Name',
				'key'   => 'name',
			),
			array(
				'label' => 'Revenue',
				'key'   => 'revenue',
			),
		);
		$rows    = array(
			array(
				'name'    => 'Course, A',
				'revenue' => 100,
			),
			array(
				'name'    => 'B',
				'revenue' => 200,
			),
		);

		$this->assertSame(
			"Name,Revenue\r\n\"Course, A\",100\r\nB,200",
			$this->build( $columns, $rows )
		);
	}

	public function test_build_csv_uses_the_csv_callback_when_present(): void {
		$columns = array(
			array(
				'label' => 'Name',
				'csv'   => function ( $row ) {
					return strtoupper( (string) $row['name'] );
				},
			),
		);
		$rows    = array( array( 'name' => 'abc' ) );

		$this->assertSame( "Name\r\nABC", $this->build( $columns, $rows ) );
	}

	public function test_build_csv_missing_key_yields_empty_cell(): void {
		$columns = array(
			array(
				'label' => 'Score',
				'key'   => 'score',
			),
		);
		$rows    = array( array( 'name' => 'no-score-here' ) );

		$this->assertSame( "Score\r\n", $this->build( $columns, $rows ) );
	}

	public function test_build_csv_escapes_header_labels(): void {
		$columns = array(
			array(
				'label' => 'Revenue, USD',
				'key'   => 'revenue',
			),
		);

		$this->assertSame( '"Revenue, USD"', $this->build( $columns, array() ) );
	}

	// --- trend helpers ( Order report ) -----------------------------------

	public function test_trend_direction(): void {
		$this->assertSame( 'up', $this->invoke( 'trend_direction', 100.0, 50.0 ) );
		$this->assertSame( 'up', $this->invoke( 'trend_direction', 10.0, 0.0 ) );
		$this->assertSame( 'down', $this->invoke( 'trend_direction', 50.0, 100.0 ) );
		$this->assertSame( 'flat', $this->invoke( 'trend_direction', 50.0, 50.0 ) );
		$this->assertSame( 'flat', $this->invoke( 'trend_direction', 0.0, 0.0 ) );
	}

	public function test_trend_pct(): void {
		$this->assertSame( 50.0, $this->invoke( 'trend_pct', 150.0, 100.0 ) );
		$this->assertSame( -50.0, $this->invoke( 'trend_pct', 50.0, 100.0 ) );
		$this->assertSame( 0.0, $this->invoke( 'trend_pct', 100.0, 100.0 ) );
		// No prior baseline → null ( rendered as "New" ), never divide-by-zero.
		$this->assertNull( $this->invoke( 'trend_pct', 100.0, 0.0 ) );
		$this->assertNull( $this->invoke( 'trend_pct', 0.0, 0.0 ) );
	}

	public function test_trend_csv(): void {
		$this->assertSame( '50%', $this->invoke( 'trend_csv', array( 'trend_pct' => 50.0 ) ) );
		$this->assertSame( '-25%', $this->invoke( 'trend_csv', array( 'trend_pct' => -25.0 ) ) );
		$this->assertSame( 'New', $this->invoke( 'trend_csv', array( 'trend_pct' => null, 'trend' => 'up' ) ) );
		$this->assertSame( '', $this->invoke( 'trend_csv', array( 'trend_pct' => null, 'trend' => 'flat' ) ) );
	}
}
