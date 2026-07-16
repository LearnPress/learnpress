<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Statistics;

use Brain\Monkey\Functions;
use LearnPress\Statistics\PeriodRange;
use LearnPress\Statistics\PeriodResolver;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * @covers \LearnPress\Statistics\PeriodResolver
 * @covers \LearnPress\Statistics\PeriodRange
 */
class PeriodResolverTest extends BrainMonkeyTestCase {

	/**
	 * Tue Jul 14 2026, mid-morning — every window test anchors here.
	 */
	private const NOW = '2026-07-14 10:30:00';

	protected function setUp(): void {
		parent::setUp();

		// Monday weeks unless a test overrides; date_i18n → plain gmdate for labels.
		Functions\when( 'get_option' )->justReturn( 1 );
		Functions\when( 'date_i18n' )->alias(
			static function ( $format, $timestamp ) {
				return gmdate( $format, (int) $timestamp );
			}
		);
	}

	private function resolve( string $filtertype, string $date = '' ): PeriodRange {
		return PeriodResolver::resolve( $filtertype, $date, self::NOW );
	}

	/*
	|--------------------------------------------------------------------------
	| resolve() — new presets: window + granularity + legacy pair
	|--------------------------------------------------------------------------
	*/

	public function test_today(): void {
		$range = $this->resolve( 'today' );

		$this->assertSame( '2026-07-14 00:00:00', $range->start );
		$this->assertSame( self::NOW, $range->end );
		$this->assertSame( PeriodResolver::GRAN_HOUR, $range->granularity );
		$this->assertSame(
			array(
				'filter_type' => 'date',
				'time'        => '2026-07-14',
			),
			$range->legacy_pair()
		);
	}

	public function test_yesterday(): void {
		$range = $this->resolve( 'yesterday' );

		$this->assertSame( '2026-07-13 00:00:00', $range->start );
		$this->assertSame( '2026-07-13 23:59:59', $range->end );
		$this->assertSame( PeriodResolver::GRAN_HOUR, $range->granularity );
		$this->assertSame(
			array(
				'filter_type' => 'date',
				'time'        => '2026-07-13',
			),
			$range->legacy_pair()
		);
	}

	public function test_week_to_date_honors_monday_start(): void {
		$range = $this->resolve( 'week' );

		// Jul 14 is a Tuesday → Monday week starts Jul 13.
		$this->assertSame( '2026-07-13 00:00:00', $range->start );
		$this->assertSame( self::NOW, $range->end );
		$this->assertSame( PeriodResolver::GRAN_DAY, $range->granularity );
		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-07-13+2026-07-14',
			),
			$range->legacy_pair()
		);
	}

	public function test_week_to_date_honors_sunday_start(): void {
		Functions\when( 'get_option' )->justReturn( 0 );

		$range = $this->resolve( 'week' );

		$this->assertSame( '2026-07-12 00:00:00', $range->start );
		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-07-12+2026-07-14',
			),
			$range->legacy_pair()
		);
	}

	public function test_last_week_is_previous_full_week(): void {
		$range = $this->resolve( 'last_week' );

		$this->assertSame( '2026-07-06 00:00:00', $range->start );
		$this->assertSame( '2026-07-12 23:59:59', $range->end );
		$this->assertSame( PeriodResolver::GRAN_DAY, $range->granularity );
		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-07-06+2026-07-12',
			),
			$range->legacy_pair()
		);
	}

	public function test_month_to_date(): void {
		$range = $this->resolve( 'month' );

		$this->assertSame( '2026-07-01 00:00:00', $range->start );
		$this->assertSame( self::NOW, $range->end );
		$this->assertSame( PeriodResolver::GRAN_DAY, $range->granularity );
		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-07-01+2026-07-14',
			),
			$range->legacy_pair()
		);
	}

	public function test_last_month_is_previous_full_month(): void {
		$range = $this->resolve( 'last_month' );

		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-06-01+2026-06-30',
			),
			$range->legacy_pair()
		);
		$this->assertSame( PeriodResolver::GRAN_DAY, $range->granularity );
	}

	public function test_quarter_to_date_is_calendar_quarter(): void {
		$range = $this->resolve( 'quarter' );

		// Jul 14 is in Q3 ( Jul–Sep ).
		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-07-01+2026-07-14',
			),
			$range->legacy_pair()
		);
		$this->assertSame( PeriodResolver::GRAN_DAY, $range->granularity );
	}

	public function test_last_quarter_is_previous_full_quarter(): void {
		$range = $this->resolve( 'last_quarter' );

		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-04-01+2026-06-30',
			),
			$range->legacy_pair()
		);
	}

	public function test_year_to_date(): void {
		$range = $this->resolve( 'year' );

		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-01-01+2026-07-14',
			),
			$range->legacy_pair()
		);
		$this->assertSame( PeriodResolver::GRAN_MONTH, $range->granularity );
	}

	public function test_last_year_is_previous_full_year(): void {
		$range = $this->resolve( 'last_year' );

		$this->assertSame( '2025-01-01 00:00:00', $range->start );
		$this->assertSame( '2025-12-31 23:59:59', $range->end );
		$this->assertSame( PeriodResolver::GRAN_MONTH, $range->granularity );
		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2025-01-01+2025-12-31',
			),
			$range->legacy_pair()
		);
	}

	/*
	|--------------------------------------------------------------------------
	| resolve() — custom ranges
	|--------------------------------------------------------------------------
	*/

	public function test_custom_short_span_is_day_granularity(): void {
		// Apr 10 … Jul 10 inclusive = 92 days.
		$range = $this->resolve( 'custom', '2026-04-10+2026-07-10' );

		$this->assertSame( PeriodResolver::GRAN_DAY, $range->granularity );
		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-04-10+2026-07-10',
			),
			$range->legacy_pair()
		);
	}

	public function test_custom_long_span_is_month_granularity(): void {
		// Apr 9 … Jul 10 inclusive = 93 days.
		$range = $this->resolve( 'custom', '2026-04-09+2026-07-10' );

		$this->assertSame( PeriodResolver::GRAN_MONTH, $range->granularity );
	}

	public function test_custom_reversed_dates_are_sorted(): void {
		$range = $this->resolve( 'custom', '2026-07-10+2026-04-10' );

		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-04-10+2026-07-10',
			),
			$range->legacy_pair()
		);
	}

	public function test_custom_invalid_date_falls_back_to_today(): void {
		$range = $this->resolve( 'custom', 'not-a-date' );

		$this->assertSame( 'today', $range->preset );
		$this->assertSame(
			array(
				'filter_type' => 'date',
				'time'        => '2026-07-14',
			),
			$range->legacy_pair()
		);
	}

	public function test_unknown_filtertype_falls_back_to_today(): void {
		$range = $this->resolve( 'bogus' );

		$this->assertSame( 'today', $range->preset );
	}

	/*
	|--------------------------------------------------------------------------
	| previous() — previous_period, calendar-aligned
	|--------------------------------------------------------------------------
	*/

	public function test_previous_period_today_is_yesterday(): void {
		$prev = PeriodResolver::previous( $this->resolve( 'today' ) );

		$this->assertSame(
			array(
				'filter_type' => 'date',
				'time'        => '2026-07-13',
			),
			$prev->legacy_pair()
		);
	}

	public function test_previous_period_week_shifts_seven_days(): void {
		$prev = PeriodResolver::previous( $this->resolve( 'week' ) );

		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-07-06+2026-07-07',
			),
			$prev->legacy_pair()
		);
	}

	public function test_previous_period_last_week_is_week_before(): void {
		$prev = PeriodResolver::previous( $this->resolve( 'last_week' ) );

		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-06-29+2026-07-05',
			),
			$prev->legacy_pair()
		);
	}

	public function test_previous_period_month_to_date_is_same_offset_in_previous_month(): void {
		$prev = PeriodResolver::previous( $this->resolve( 'month' ) );

		// Plan §4.2 fixture: Jul 1–14 → Jun 1–14.
		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-06-01+2026-06-14',
			),
			$prev->legacy_pair()
		);
	}

	public function test_previous_period_month_clamps_to_short_months(): void {
		$range = PeriodResolver::resolve( 'month', '', '2026-07-31 12:00:00' );
		$prev  = PeriodResolver::previous( $range );

		// Jul 1–31 → Jun 1–30 ( June has no 31st ).
		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-06-01+2026-06-30',
			),
			$prev->legacy_pair()
		);
	}

	public function test_previous_period_last_month_is_month_before(): void {
		$prev = PeriodResolver::previous( $this->resolve( 'last_month' ) );

		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-05-01+2026-05-31',
			),
			$prev->legacy_pair()
		);
	}

	public function test_previous_period_quarter_to_date_is_same_offset_in_previous_quarter(): void {
		$prev = PeriodResolver::previous( $this->resolve( 'quarter' ) );

		// 14 days into Q3 → first 14 days of Q2.
		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-04-01+2026-04-14',
			),
			$prev->legacy_pair()
		);
	}

	public function test_previous_period_last_quarter_is_quarter_before(): void {
		$prev = PeriodResolver::previous( $this->resolve( 'last_quarter' ) );

		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-01-01+2026-03-31',
			),
			$prev->legacy_pair()
		);
	}

	public function test_previous_period_year_to_date_is_previous_year_same_window(): void {
		$prev = PeriodResolver::previous( $this->resolve( 'year' ) );

		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2025-01-01+2025-07-14',
			),
			$prev->legacy_pair()
		);
	}

	public function test_previous_period_last_year_is_year_before(): void {
		$prev = PeriodResolver::previous( $this->resolve( 'last_year' ) );

		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2024-01-01+2024-12-31',
			),
			$prev->legacy_pair()
		);
	}

	public function test_previous_period_custom_is_equal_length_window_before(): void {
		$prev = PeriodResolver::previous( $this->resolve( 'custom', '2026-07-01+2026-07-14' ) );

		// 14 days → the 14 days ending the day before Jul 1.
		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-06-17+2026-06-30',
			),
			$prev->legacy_pair()
		);
	}

	/*
	|--------------------------------------------------------------------------
	| previous() — previous_year
	|--------------------------------------------------------------------------
	*/

	public function test_previous_year_shifts_window_one_calendar_year(): void {
		$prev = PeriodResolver::previous( $this->resolve( 'month' ), PeriodResolver::COMPARE_PREVIOUS_YEAR );

		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2025-07-01+2025-07-14',
			),
			$prev->legacy_pair()
		);
	}

	public function test_previous_year_clamps_leap_february(): void {
		$range = PeriodResolver::resolve( 'custom', '2024-02-29+2024-02-29', '2024-03-15 09:00:00' );
		$prev  = PeriodResolver::previous( $range, PeriodResolver::COMPARE_PREVIOUS_YEAR );

		$this->assertSame(
			array(
				'filter_type' => 'date',
				'time'        => '2023-02-28',
			),
			$prev->legacy_pair()
		);
	}

	/*
	|--------------------------------------------------------------------------
	| sanitize_compare() / granularity / labels
	|--------------------------------------------------------------------------
	*/

	public function test_sanitize_compare_whitelists(): void {
		$this->assertSame( 'previous_year', PeriodResolver::sanitize_compare( 'previous_year' ) );
		$this->assertSame( 'previous_period', PeriodResolver::sanitize_compare( 'previous_period' ) );
		$this->assertSame( 'previous_period', PeriodResolver::sanitize_compare( 'DROP TABLE' ) );
		$this->assertSame( 'previous_period', PeriodResolver::sanitize_compare( '' ) );
	}

	public function test_preset_granularity(): void {
		$this->assertSame( 'hour', $this->resolve( 'today' )->granularity );
		$this->assertSame( 'day', $this->resolve( 'week' )->granularity );
		$this->assertSame( 'day', $this->resolve( 'month' )->granularity );
		$this->assertSame( 'month', $this->resolve( 'year' )->granularity );
	}

	public function test_labels(): void {
		$this->assertSame( 'Today (Jul 14)', $this->resolve( 'today' )->label );
		$this->assertSame( 'Month to date (Jul 1 – 14)', $this->resolve( 'month' )->label );
		$this->assertSame( 'Last quarter (Apr 1 – Jun 30)', $this->resolve( 'last_quarter' )->label );
		$this->assertSame( 'Year to date (Jan – Jul)', $this->resolve( 'year' )->label );
		$this->assertSame( 'Last year (2025)', $this->resolve( 'last_year' )->label );
	}

	public function test_default_anchor_uses_wp_current_time(): void {
		Functions\when( 'current_time' )->justReturn( self::NOW );

		$range = PeriodResolver::resolve( 'month' );

		$this->assertSame(
			array(
				'filter_type' => 'custom',
				'time'        => '2026-07-01+2026-07-14',
			),
			$range->legacy_pair()
		);
	}
}
