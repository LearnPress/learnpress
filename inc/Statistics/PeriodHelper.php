<?php
/**
 * Class PeriodHelper
 *
 * @package LearnPress/Classes/Statistics
 * @since 4.4.2
 */

namespace LearnPress\Statistics;

use DateTimeImmutable;
use Exception;

defined( 'ABSPATH' ) || exit();

/**
 * Maps a controller statistics filter ( filter_type + time ) to the equivalent
 * previous window and builds KPI payloads with previous-period comparison.
 *
 * Fail-soft contract: unmappable or invalid input returns null so a KPI card
 * simply renders without a delta — never a wrong one.
 *
 * @since 4.4.2
 */
class PeriodHelper {
	/**
	 * Map a statistics filter to the window immediately before it, same length.
	 *
	 * date            → the previous day
	 * previous_days N → the N + 1 days before the current [ today - N … today ] window, as custom
	 * month           → the previous calendar month
	 * previous_months N → the N + 1 months before the current window, as custom
	 * year            → the previous calendar year
	 * custom A+B      → the same-length range ending the day before A
	 *
	 * @param array  $filter [ 'filter_type' => string, 'time' => string|int ] as built by get_statistics_filter().
	 * @param string $today  'Y-m-d' anchor for the relative types; defaults to WP current_time (injectable for tests).
	 * @return array|null Same shape as $filter, or null when unmappable.
	 */
	public static function get_previous_filter( array $filter, string $today = '' ): ?array {
		$type = $filter['filter_type'] ?? '';
		$time = $filter['time'] ?? '';

		if ( '' === $time || ( ! is_string( $time ) && ! is_int( $time ) ) ) {
			return null;
		}

		try {
			switch ( $type ) {
				case 'date':
					$date = new DateTimeImmutable( (string) $time );

					return [
						'filter_type' => 'date',
						'time'        => $date->modify( '-1 day' )->format( 'Y-m-d' ),
					];

				case 'previous_days':
					$days = (int) $time;
					if ( $days < 2 ) {
						// Mirrors the previous_days_filter() constraint — fail soft here.
						return null;
					}
					// Current window is [ today - N … today ] inclusive → N + 1 days.
					$anchor = new DateTimeImmutable( self::today( $today ) );
					$end    = $anchor->modify( sprintf( '-%d days', $days + 1 ) );
					$start  = $end->modify( sprintf( '-%d days', $days ) );

					return [
						'filter_type' => 'custom',
						'time'        => $start->format( 'Y-m-d' ) . '+' . $end->format( 'Y-m-d' ),
					];

				case 'month':
					$date = new DateTimeImmutable( (string) $time );
					// Anchor to day 1 first: "Jan 31 - 1 month" would overflow past February.
					$first = $date->modify( 'first day of this month' );

					return [
						'filter_type' => 'month',
						'time'        => $first->modify( '-1 month' )->format( 'Y-m-d' ),
					];

				case 'previous_months':
					$months = (int) $time;
					if ( $months < 2 ) {
						// Mirrors the previous_months_filter() constraint — fail soft here.
						return null;
					}
					// Current window is [ month( today ) - N … month( today ) ] → N + 1 months.
					$anchor = ( new DateTimeImmutable( self::today( $today ) ) )->modify( 'first day of this month' );
					$end    = $anchor->modify( sprintf( '-%d months', $months + 1 ) )->modify( 'last day of this month' );
					$start  = $anchor->modify( sprintf( '-%d months', ( 2 * $months ) + 1 ) );

					return [
						'filter_type' => 'custom',
						'time'        => $start->format( 'Y-m-d' ) . '+' . $end->format( 'Y-m-d' ),
					];

				case 'year':
					$date = new DateTimeImmutable( (string) $time );

					return [
						'filter_type' => 'year',
						'time'        => ( (int) $date->format( 'Y' ) - 1 ) . '-01-01',
					];

				case 'custom':
					$dates = explode( '+', (string) $time );
					if ( 2 !== count( $dates ) ) {
						return null;
					}
					sort( $dates );
					$start      = new DateTimeImmutable( $dates[0] );
					$end        = new DateTimeImmutable( $dates[1] );
					$length     = (int) $start->diff( $end )->format( '%a' ) + 1;
					$prev_end   = $start->modify( '-1 day' );
					$prev_start = $prev_end->modify( sprintf( '-%d days', $length - 1 ) );

					return [
						'filter_type' => 'custom',
						'time'        => $prev_start->format( 'Y-m-d' ) . '+' . $prev_end->format( 'Y-m-d' ),
					];
			}
		} catch ( Exception $e ) {
			return null;
		}

		return null;
	}

	/**
	 * Build the KPI payload comparing a current value against the previous period.
	 *
	 * change_pct is null when there is no previous value or it is zero — the UI
	 * hides the delta instead of showing a division-by-zero artifact.
	 *
	 * @param int|float|string|null $value Current-period value.
	 * @param int|float|string|null $prev  Previous-period value; null when no previous window.
	 * @return array [ 'value' => number, 'prev_value' => number|null, 'change_pct' => float|null ]
	 */
	public static function kpi_payload( $value, $prev ): array {
		$value = is_numeric( $value ) ? $value + 0 : 0;

		$payload = [
			'value'      => $value,
			'prev_value' => null,
			'change_pct' => null,
		];

		if ( ! is_numeric( $prev ) ) {
			return $payload;
		}

		$prev                  = $prev + 0;
		$payload['prev_value'] = $prev;

		if ( 0 == $prev ) {
			return $payload;
		}

		$payload['change_pct'] = round( ( $value - $prev ) / $prev * 100, 1 );

		return $payload;
	}

	/**
	 * @param string $today Injected 'Y-m-d' anchor or '' for WP current time.
	 * @return string
	 */
	private static function today( string $today ): string {
		if ( '' !== $today ) {
			return $today;
		}

		return current_time( 'Y-m-d' );
	}
}
