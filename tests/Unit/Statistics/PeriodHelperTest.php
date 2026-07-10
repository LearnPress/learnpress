<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Statistics;

use Brain\Monkey\Functions;
use LearnPress\Statistics\PeriodHelper;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * @covers \LearnPress\Statistics\PeriodHelper
 */
class PeriodHelperTest extends BrainMonkeyTestCase {

	/*
	|--------------------------------------------------------------------------
	| get_previous_filter() — date
	|--------------------------------------------------------------------------
	*/

	public function test_date_maps_to_previous_day(): void {
		$prev = PeriodHelper::get_previous_filter( [ 'filter_type' => 'date', 'time' => '2026-07-10' ] );

		$this->assertSame( [ 'filter_type' => 'date', 'time' => '2026-07-09' ], $prev );
	}

	public function test_date_crosses_month_boundary(): void {
		$prev = PeriodHelper::get_previous_filter( [ 'filter_type' => 'date', 'time' => '2026-03-01' ] );

		$this->assertSame( '2026-02-28', $prev['time'] );
	}

	public function test_date_crosses_leap_february(): void {
		$prev = PeriodHelper::get_previous_filter( [ 'filter_type' => 'date', 'time' => '2024-03-01' ] );

		$this->assertSame( '2024-02-29', $prev['time'] );
	}

	public function test_date_crosses_year_boundary(): void {
		$prev = PeriodHelper::get_previous_filter( [ 'filter_type' => 'date', 'time' => '2026-01-01' ] );

		$this->assertSame( [ 'filter_type' => 'date', 'time' => '2025-12-31' ], $prev );
	}

	/*
	|--------------------------------------------------------------------------
	| get_previous_filter() — previous_days
	|--------------------------------------------------------------------------
	*/

	public function test_previous_days_last7days_window(): void {
		// Current window with N = 6 is [ 2026-07-04 … 2026-07-10 ] — 7 days.
		$prev = PeriodHelper::get_previous_filter(
			[ 'filter_type' => 'previous_days', 'time' => 6 ],
			'2026-07-10'
		);

		$this->assertSame( [ 'filter_type' => 'custom', 'time' => '2026-06-27+2026-07-03' ], $prev );
	}

	public function test_previous_days_last30days_window(): void {
		// Current window with N = 30 is [ 2026-06-10 … 2026-07-10 ] — 31 days.
		$prev = PeriodHelper::get_previous_filter(
			[ 'filter_type' => 'previous_days', 'time' => 30 ],
			'2026-07-10'
		);

		$this->assertSame( [ 'filter_type' => 'custom', 'time' => '2026-05-10+2026-06-09' ], $prev );
	}

	public function test_previous_days_below_db_constraint_returns_null(): void {
		$prev = PeriodHelper::get_previous_filter(
			[ 'filter_type' => 'previous_days', 'time' => 1 ],
			'2026-07-10'
		);

		$this->assertNull( $prev );
	}

	public function test_previous_days_uses_current_time_when_no_anchor_given(): void {
		Functions\when( 'current_time' )->justReturn( '2026-07-10' );

		$prev = PeriodHelper::get_previous_filter( [ 'filter_type' => 'previous_days', 'time' => 6 ] );

		$this->assertSame( '2026-06-27+2026-07-03', $prev['time'] );
	}

	/*
	|--------------------------------------------------------------------------
	| get_previous_filter() — month / previous_months
	|--------------------------------------------------------------------------
	*/

	public function test_month_maps_to_previous_month_first_day(): void {
		$prev = PeriodHelper::get_previous_filter( [ 'filter_type' => 'month', 'time' => '2026-07-15' ] );

		$this->assertSame( [ 'filter_type' => 'month', 'time' => '2026-06-01' ], $prev );
	}

	public function test_month_january_maps_to_december_of_previous_year(): void {
		$prev = PeriodHelper::get_previous_filter( [ 'filter_type' => 'month', 'time' => '2026-01-31' ] );

		$this->assertSame( '2025-12-01', $prev['time'] );
	}

	public function test_month_day31_does_not_overflow_past_short_month(): void {
		// Naive "2026-03-31 - 1 month" would overflow into March again.
		$prev = PeriodHelper::get_previous_filter( [ 'filter_type' => 'month', 'time' => '2026-03-31' ] );

		$this->assertSame( '2026-02-01', $prev['time'] );
	}

	public function test_previous_months_last12months_window(): void {
		// Current window with N = 11 is [ Aug 2025 … Jul 2026 ] — 12 months.
		$prev = PeriodHelper::get_previous_filter(
			[ 'filter_type' => 'previous_months', 'time' => 11 ],
			'2026-07-10'
		);

		$this->assertSame( [ 'filter_type' => 'custom', 'time' => '2024-08-01+2025-07-31' ], $prev );
	}

	public function test_previous_months_end_lands_on_last_day_of_month(): void {
		// N = 2 from 2026-07-10: current [ May … Jul 2026 ], previous [ Feb … Apr 2026 ].
		$prev = PeriodHelper::get_previous_filter(
			[ 'filter_type' => 'previous_months', 'time' => 2 ],
			'2026-07-10'
		);

		$this->assertSame( '2026-02-01+2026-04-30', $prev['time'] );
	}

	public function test_previous_months_below_db_constraint_returns_null(): void {
		$prev = PeriodHelper::get_previous_filter(
			[ 'filter_type' => 'previous_months', 'time' => 1 ],
			'2026-07-10'
		);

		$this->assertNull( $prev );
	}

	/*
	|--------------------------------------------------------------------------
	| get_previous_filter() — year / custom
	|--------------------------------------------------------------------------
	*/

	public function test_year_maps_to_previous_year(): void {
		$prev = PeriodHelper::get_previous_filter( [ 'filter_type' => 'year', 'time' => '2026-05-10' ] );

		$this->assertSame( [ 'filter_type' => 'year', 'time' => '2025-01-01' ], $prev );
	}

	public function test_custom_maps_to_same_length_range_immediately_before(): void {
		// 10-day range → previous 10 days ending the day before the range starts.
		$prev = PeriodHelper::get_previous_filter(
			[ 'filter_type' => 'custom', 'time' => '2026-07-01+2026-07-10' ]
		);

		$this->assertSame( [ 'filter_type' => 'custom', 'time' => '2026-06-21+2026-06-30' ], $prev );
	}

	public function test_custom_single_day_range(): void {
		$prev = PeriodHelper::get_previous_filter(
			[ 'filter_type' => 'custom', 'time' => '2026-07-10+2026-07-10' ]
		);

		$this->assertSame( '2026-07-09+2026-07-09', $prev['time'] );
	}

	public function test_custom_reversed_dates_are_sorted_first(): void {
		$prev = PeriodHelper::get_previous_filter(
			[ 'filter_type' => 'custom', 'time' => '2026-07-10+2026-07-01' ]
		);

		$this->assertSame( '2026-06-21+2026-06-30', $prev['time'] );
	}

	public function test_custom_crossing_year_boundary(): void {
		$prev = PeriodHelper::get_previous_filter(
			[ 'filter_type' => 'custom', 'time' => '2026-01-01+2026-01-07' ]
		);

		$this->assertSame( '2025-12-25+2025-12-31', $prev['time'] );
	}

	/*
	|--------------------------------------------------------------------------
	| get_previous_filter() — fail-soft paths
	|--------------------------------------------------------------------------
	*/

	public function test_unknown_filter_type_returns_null(): void {
		$this->assertNull( PeriodHelper::get_previous_filter( [ 'filter_type' => 'quarter', 'time' => '2026-07-10' ] ) );
	}

	public function test_missing_keys_return_null(): void {
		$this->assertNull( PeriodHelper::get_previous_filter( [] ) );
	}

	public function test_custom_without_separator_returns_null(): void {
		$this->assertNull(
			PeriodHelper::get_previous_filter( [ 'filter_type' => 'custom', 'time' => '2026-07-10' ] )
		);
	}

	public function test_garbage_date_returns_null(): void {
		$this->assertNull(
			PeriodHelper::get_previous_filter( [ 'filter_type' => 'date', 'time' => 'not-a-date' ] )
		);
	}

	/*
	|--------------------------------------------------------------------------
	| kpi_payload()
	|--------------------------------------------------------------------------
	*/

	public function test_kpi_payload_positive_change(): void {
		$payload = PeriodHelper::kpi_payload( 150, 100 );

		$this->assertSame( [ 'value' => 150, 'prev_value' => 100, 'change_pct' => 50.0 ], $payload );
	}

	public function test_kpi_payload_negative_change_rounded(): void {
		$payload = PeriodHelper::kpi_payload( 100, 150 );

		$this->assertSame( -33.3, $payload['change_pct'] );
	}

	public function test_kpi_payload_zero_prev_hides_delta(): void {
		$payload = PeriodHelper::kpi_payload( 10, 0 );

		$this->assertSame( 10, $payload['value'] );
		$this->assertSame( 0, $payload['prev_value'] );
		$this->assertNull( $payload['change_pct'] );
	}

	public function test_kpi_payload_null_prev_hides_delta(): void {
		$payload = PeriodHelper::kpi_payload( 10, null );

		$this->assertNull( $payload['prev_value'] );
		$this->assertNull( $payload['change_pct'] );
	}

	public function test_kpi_payload_accepts_numeric_strings(): void {
		$payload = PeriodHelper::kpi_payload( '150.5', '100' );

		$this->assertSame( 150.5, $payload['value'] );
		$this->assertSame( 100, $payload['prev_value'] );
		$this->assertSame( 50.5, $payload['change_pct'] );
	}

	public function test_kpi_payload_drop_to_zero_is_minus_100(): void {
		$payload = PeriodHelper::kpi_payload( 0, 10 );

		$this->assertSame( -100.0, $payload['change_pct'] );
	}

	public function test_kpi_payload_non_numeric_value_collapses_to_zero(): void {
		$payload = PeriodHelper::kpi_payload( 'garbage', null );

		$this->assertSame( 0, $payload['value'] );
	}
}
