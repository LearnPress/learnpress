<?php
/**
 * Class PeriodResolver
 *
 * @package LearnPress/Classes/Statistics
 * @since 4.4.2
 */

namespace LearnPress\Statistics;

use DateTimeImmutable;
use Exception;

defined( 'ABSPATH' ) || exit();

/**
 * Single source of truth for "what window are we looking at, and at what chart
 * resolution". Maps a `preset` (+ optional custom `date`) to a PeriodRange,
 * and a PeriodRange (+ compare mode) to its delta baseline.
 *
 * - Presets resolve to concrete [ start … end ] windows carried as the legacy
 *   `custom` pair, so every existing DB method works unchanged; the only new
 *   signal is the explicit `granularity`.
 * - Weeks honor the WP `start_of_week` option; quarters are calendar quarters.
 * - All date math anchors on current_time() (site timezone); `$now` is
 *   injectable for tests.
 *
 * Fail-soft contract: resolve() never throws — unknown/invalid input falls back
 * to `today`; previous() returns null so a KPI renders without a delta.
 *
 * @since 4.4.2
 */
class PeriodResolver {
	const GRAN_HOUR  = 'hour';
	const GRAN_DAY   = 'day';
	const GRAN_MONTH = 'month';

	const COMPARE_PREVIOUS_PERIOD = 'previous_period';
	const COMPARE_PREVIOUS_YEAR   = 'previous_year';

	/**
	 * Presets offered in the date-range dropdown, in display order
	 * ( 'custom' is the dropdown's second tab, not a list entry ).
	 */
	const UI_PRESETS = array( 'today', 'yesterday', 'week', 'last_week', 'month', 'last_month', 'quarter', 'last_quarter', 'year', 'last_year' );

	/**
	 * Resolve a requested preset into a PeriodRange.
	 *
	 * @param string $preset Preset id; unknown → 'today'.
	 * @param string $date       'Y-m-d+Y-m-d' pair, only read when $preset is 'custom'.
	 * @param string $now        'Y-m-d H:i:s' anchor, '' → current_time( 'mysql' ) (injectable for tests).
	 * @return PeriodRange
	 */
	public static function resolve( string $preset, string $date = '', string $now = '' ): PeriodRange {
		$now_dt = self::now_dt( $now );
		$preset = '' !== $preset ? $preset : 'today';

		try {
			$range = self::build( $preset, $date, $now_dt );
		} catch ( Exception $e ) {
			$range = null;
		}

		if ( ! $range instanceof PeriodRange ) {
			// Poka-yoke: unknown preset or unparsable custom date → today.
			$range = self::build( 'today', '', $now_dt );
		}

		/**
		 * Filter the resolved statistics window.
		 *
		 * @param PeriodRange $range
		 * @param string      $preset Requested preset id.
		 * @param string      $date       Raw custom date pair.
		 * @since 4.4.2
		 */
		return apply_filters( 'learn-press/statistics/period-range', $range, $preset, $date );
	}

	/**
	 * Baseline window for KPI deltas.
	 *
	 * previous_period shifts back by one natural unit of the preset so "to date"
	 * stays calendar-aligned ( Jul 1–14 → Jun 1–14 ); custom/rolling windows get
	 * the equal-length window immediately before. previous_year is the same
	 * month/day window one calendar year earlier ( Feb 29 clamps to Feb 28 ).
	 *
	 * Baselines are day-granular: the legacy pair queries whole days, exactly
	 * like the existing PeriodHelper mapping.
	 *
	 * @param PeriodRange $range   Current window from resolve().
	 * @param string      $compare COMPARE_PREVIOUS_PERIOD | COMPARE_PREVIOUS_YEAR.
	 * @return PeriodRange|null Null when no baseline can be built.
	 */
	public static function previous( PeriodRange $range, string $compare = self::COMPARE_PREVIOUS_PERIOD ): ?PeriodRange {
		try {
			$start = new DateTimeImmutable( $range->start );
			$end   = new DateTimeImmutable( $range->end );

			if ( self::COMPARE_PREVIOUS_YEAR === self::sanitize_compare( $compare ) ) {
				return self::baseline( $range, self::shift_years( $start, -1 ), self::shift_years( $end, -1 ) );
			}

			switch ( $range->preset ) {
				case 'today':
				case 'yesterday':
					return self::baseline( $range, $start->modify( '-1 day' ), $end->modify( '-1 day' ) );

				case 'week':
					return self::baseline( $range, $start->modify( '-7 days' ), $end->modify( '-7 days' ) );

				case 'last_week':
					return self::baseline( $range, $start->modify( '-7 days' ), $start->modify( '-1 day' ) );

				case 'month':
					$prev_first = $start->modify( 'first day of previous month' );
					$day        = min( (int) $end->format( 'j' ), (int) $prev_first->format( 't' ) );

					return self::baseline( $range, $prev_first, $prev_first->modify( sprintf( '+%d days', $day - 1 ) ) );

				case 'last_month':
					$prev_first = $start->modify( 'first day of previous month' );

					return self::baseline( $range, $prev_first, $prev_first->modify( 'last day of this month' ) );

				case 'quarter':
					$prev_first = $start->modify( '-3 months' );
					$prev_last  = $start->modify( '-1 day' );
					$elapsed    = (int) $start->diff( $end )->format( '%a' );
					$prev_end   = $prev_first->modify( sprintf( '+%d days', $elapsed ) );

					return self::baseline( $range, $prev_first, min( $prev_end, $prev_last ) );

				case 'last_quarter':
					return self::baseline( $range, $start->modify( '-3 months' ), $start->modify( '-1 day' ) );

				case 'year':
					return self::baseline( $range, $start->modify( '-1 year' ), self::shift_years( $end, -1 ) );

				case 'last_year':
					$prev_first = $start->modify( '-1 year' );

					return self::baseline( $range, $prev_first, $prev_first->modify( '+11 months' )->modify( 'last day of this month' ) );

				case 'custom':
				default:
					$length     = (int) $start->diff( $end )->format( '%a' ) + 1;
					$prev_end   = $start->modify( '-1 day' );
					$prev_start = $prev_end->modify( sprintf( '-%d days', $length - 1 ) );

					return self::baseline( $range, $prev_start, $prev_end );
			}
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Whitelist the compare mode; anything else → previous_period.
	 *
	 * @param string $compare Raw request value.
	 * @return string
	 */
	public static function sanitize_compare( string $compare ): string {
		return self::COMPARE_PREVIOUS_YEAR === $compare ? self::COMPARE_PREVIOUS_YEAR : self::COMPARE_PREVIOUS_PERIOD;
	}

	/**
	 * Granularity rule for arbitrary spans ( custom ranges ): day up to 92 days,
	 * month beyond. Presets carry their own pinned granularity instead.
	 *
	 * @param DateTimeImmutable $start
	 * @param DateTimeImmutable $end
	 * @return string
	 */
	public static function granularity_for_span( DateTimeImmutable $start, DateTimeImmutable $end ): string {
		$days = (int) $start->diff( $end )->format( '%a' ) + 1;

		return $days <= 92 ? self::GRAN_DAY : self::GRAN_MONTH;
	}

	/**
	 * Build the range for one preset, or null for an unknown one.
	 *
	 * @param string            $preset
	 * @param string            $date
	 * @param DateTimeImmutable $now
	 * @return PeriodRange|null
	 * @throws Exception When a custom date pair does not parse.
	 */
	private static function build( string $preset, string $date, DateTimeImmutable $now ): ?PeriodRange {
		$today = $now->setTime( 0, 0, 0 );

		switch ( $preset ) {
			case 'today':
				return self::make( $preset, $today, $now, self::GRAN_HOUR, 'date', $now->format( 'Y-m-d' ) );

			case 'yesterday':
				$day = $today->modify( '-1 day' );

				return self::make( $preset, $day, self::day_end( $day ), self::GRAN_HOUR, 'date', $day->format( 'Y-m-d' ) );

			case 'week':
				return self::make_custom( $preset, self::week_start( $today ), $now, self::GRAN_DAY );

			case 'last_week':
				$week_start = self::week_start( $today );

				return self::make_custom( $preset, $week_start->modify( '-7 days' ), self::day_end( $week_start->modify( '-1 day' ) ), self::GRAN_DAY );

			case 'month':
				return self::make_custom( $preset, $today->modify( 'first day of this month' ), $now, self::GRAN_DAY );

			case 'last_month':
				$first = $today->modify( 'first day of previous month' );

				return self::make_custom( $preset, $first, self::day_end( $first->modify( 'last day of this month' ) ), self::GRAN_DAY );

			case 'quarter':
				return self::make_custom( $preset, self::quarter_start( $today ), $now, self::GRAN_DAY );

			case 'last_quarter':
				$quarter_start = self::quarter_start( $today );

				return self::make_custom( $preset, $quarter_start->modify( '-3 months' ), self::day_end( $quarter_start->modify( '-1 day' ) ), self::GRAN_DAY );

			case 'year':
				return self::make_custom( $preset, $today->modify( 'first day of january' ), $now, self::GRAN_MONTH );

			case 'last_year':
				$jan1 = $today->modify( 'first day of january' )->modify( '-1 year' );

				return self::make_custom( $preset, $jan1, self::day_end( $jan1->modify( '+11 months' )->modify( 'last day of this month' ) ), self::GRAN_MONTH );

			case 'custom':
				$dates = explode( '+', $date );
				if ( 2 !== count( $dates ) ) {
					return null;
				}
				sort( $dates );
				$start = ( new DateTimeImmutable( $dates[0] ) )->setTime( 0, 0, 0 );
				$end   = self::day_end( new DateTimeImmutable( $dates[1] ) );

				return self::make_custom( $preset, $start, $end, self::granularity_for_span( $start, $end ) );
		}

		return null;
	}

	/**
	 * @param string            $preset
	 * @param DateTimeImmutable $start
	 * @param DateTimeImmutable $end
	 * @param string            $granularity
	 * @param string            $filter_type Legacy DB type.
	 * @param string|int        $time        Legacy DB value.
	 * @return PeriodRange
	 */
	private static function make( string $preset, DateTimeImmutable $start, DateTimeImmutable $end, string $granularity, string $filter_type, $time ): PeriodRange {
		return new PeriodRange(
			$preset,
			$start->format( 'Y-m-d H:i:s' ),
			$end->format( 'Y-m-d H:i:s' ),
			$granularity,
			self::label_for( $preset, $start, $end, $granularity ),
			$filter_type,
			$time
		);
	}

	/**
	 * Range carried as the legacy `custom` pair ( whole days, BETWEEN in SQL ).
	 *
	 * @param string            $preset
	 * @param DateTimeImmutable $start
	 * @param DateTimeImmutable $end
	 * @param string            $granularity
	 * @return PeriodRange
	 */
	private static function make_custom( string $preset, DateTimeImmutable $start, DateTimeImmutable $end, string $granularity ): PeriodRange {
		return self::make( $preset, $start, $end, $granularity, 'custom', $start->format( 'Y-m-d' ) . '+' . $end->format( 'Y-m-d' ) );
	}

	/**
	 * Baseline PeriodRange for previous(): keeps the preset id and granularity,
	 * carries the shifted window as date|custom legacy pair, no label.
	 *
	 * @param PeriodRange       $range Current window.
	 * @param DateTimeImmutable $start
	 * @param DateTimeImmutable $end
	 * @return PeriodRange
	 */
	private static function baseline( PeriodRange $range, DateTimeImmutable $start, DateTimeImmutable $end ): PeriodRange {
		$start_day = $start->format( 'Y-m-d' );
		$end_day   = $end->format( 'Y-m-d' );

		if ( $start_day === $end_day ) {
			$pair = array( 'date', $start_day );
		} else {
			$pair = array( 'custom', $start_day . '+' . $end_day );
		}

		return new PeriodRange(
			$range->preset,
			$start->setTime( 0, 0, 0 )->format( 'Y-m-d H:i:s' ),
			self::day_end( $end )->format( 'Y-m-d H:i:s' ),
			$range->granularity,
			'',
			$pair[0],
			$pair[1]
		);
	}

	/**
	 * Translated display name of a preset ( "month" → "Month to date" ).
	 *
	 * @param string $preset Preset id.
	 * @return string Falls back to the raw id for unknown presets.
	 */
	public static function preset_name( string $preset ): string {
		$names = array(
			'today'        => __( 'Today', 'learnpress' ),
			'yesterday'    => __( 'Yesterday', 'learnpress' ),
			'week'         => __( 'Week to date', 'learnpress' ),
			'last_week'    => __( 'Last week', 'learnpress' ),
			'month'        => __( 'Month to date', 'learnpress' ),
			'last_month'   => __( 'Last month', 'learnpress' ),
			'quarter'      => __( 'Quarter to date', 'learnpress' ),
			'last_quarter' => __( 'Last quarter', 'learnpress' ),
			'year'         => __( 'Year to date', 'learnpress' ),
			'last_year'    => __( 'Last year', 'learnpress' ),
			'custom'       => __( 'Custom', 'learnpress' ),
		);

		return $names[ $preset ] ?? $preset;
	}

	/**
	 * Human range part of a resolved window ( "Jul 1 – 14" ) — what the
	 * dropdown toggle shows next to the preset name.
	 *
	 * @param PeriodRange $range
	 * @return string
	 */
	public static function range_label_for( PeriodRange $range ): string {
		try {
			return self::range_label(
				new DateTimeImmutable( $range->start ),
				new DateTimeImmutable( $range->end ),
				$range->granularity
			);
		} catch ( Exception $e ) {
			return '';
		}
	}

	/**
	 * Preset name + resolved range, e.g. "Month to date (Jul 1 – 14)".
	 *
	 * @param string            $preset
	 * @param DateTimeImmutable $start
	 * @param DateTimeImmutable $end
	 * @param string            $granularity
	 * @return string
	 */
	private static function label_for( string $preset, DateTimeImmutable $start, DateTimeImmutable $end, string $granularity ): string {
		return sprintf( '%s (%s)', self::preset_name( $preset ), self::range_label( $start, $end, $granularity ) );
	}

	/**
	 * Human range part of the label, densest unambiguous form.
	 *
	 * @param DateTimeImmutable $start
	 * @param DateTimeImmutable $end
	 * @param string            $granularity
	 * @return string
	 */
	private static function range_label( DateTimeImmutable $start, DateTimeImmutable $end, string $granularity ): string {
		$same_year  = $start->format( 'Y' ) === $end->format( 'Y' );
		$same_month = $same_year && $start->format( 'm' ) === $end->format( 'm' );

		if ( self::GRAN_MONTH === $granularity ) {
			// Exact calendar year → just the year ( "2025" ).
			if ( $same_year && '01-01' === $start->format( 'm-d' ) && '12-31' === $end->format( 'm-d' ) ) {
				return date_i18n( 'Y', $start->getTimestamp() );
			}
			if ( $same_month ) {
				return date_i18n( 'M Y', $start->getTimestamp() );
			}
			if ( $same_year ) {
				return date_i18n( 'M', $start->getTimestamp() ) . ' – ' . date_i18n( 'M', $end->getTimestamp() );
			}

			return date_i18n( 'M Y', $start->getTimestamp() ) . ' – ' . date_i18n( 'M Y', $end->getTimestamp() );
		}

		if ( $start->format( 'Y-m-d' ) === $end->format( 'Y-m-d' ) ) {
			return date_i18n( 'M j', $start->getTimestamp() );
		}
		if ( $same_month ) {
			return date_i18n( 'M j', $start->getTimestamp() ) . ' – ' . date_i18n( 'j', $end->getTimestamp() );
		}
		if ( $same_year ) {
			return date_i18n( 'M j', $start->getTimestamp() ) . ' – ' . date_i18n( 'M j', $end->getTimestamp() );
		}

		return date_i18n( 'M j, Y', $start->getTimestamp() ) . ' – ' . date_i18n( 'M j, Y', $end->getTimestamp() );
	}

	/**
	 * First day of the week containing $day, honoring WP start_of_week
	 * ( 0 = Sunday … 6 = Saturday ).
	 *
	 * @param DateTimeImmutable $day
	 * @return DateTimeImmutable
	 */
	private static function week_start( DateTimeImmutable $day ): DateTimeImmutable {
		$start_of_week = (int) get_option( 'start_of_week', 1 );
		$offset        = ( (int) $day->format( 'w' ) - $start_of_week + 7 ) % 7;

		return $day->modify( sprintf( '-%d days', $offset ) );
	}

	/**
	 * First day of the calendar quarter containing $day.
	 *
	 * @param DateTimeImmutable $day
	 * @return DateTimeImmutable
	 */
	private static function quarter_start( DateTimeImmutable $day ): DateTimeImmutable {
		$quarter_month = (int) floor( ( (int) $day->format( 'n' ) - 1 ) / 3 ) * 3 + 1;

		return $day->setDate( (int) $day->format( 'Y' ), $quarter_month, 1 );
	}

	/**
	 * Shift a date by whole years, clamping Feb 29 → Feb 28 instead of letting
	 * PHP overflow into March.
	 *
	 * @param DateTimeImmutable $date
	 * @param int               $years Signed.
	 * @return DateTimeImmutable
	 */
	private static function shift_years( DateTimeImmutable $date, int $years ): DateTimeImmutable {
		$year  = (int) $date->format( 'Y' ) + $years;
		$month = (int) $date->format( 'n' );
		$day   = (int) $date->format( 'j' );

		if ( ! checkdate( $month, $day, $year ) ) {
			$day = (int) $date->setDate( $year, $month, 1 )->format( 't' );
		}

		return $date->setDate( $year, $month, $day );
	}

	/**
	 * @param DateTimeImmutable $day
	 * @return DateTimeImmutable
	 */
	private static function day_end( DateTimeImmutable $day ): DateTimeImmutable {
		return $day->setTime( 23, 59, 59 );
	}

	/**
	 * @param string $now Injected 'Y-m-d H:i:s' or '' for WP current time.
	 * @return DateTimeImmutable
	 * @throws Exception Never in practice — falls back to server now.
	 */
	private static function now_dt( string $now ): DateTimeImmutable {
		if ( '' === $now ) {
			$now = (string) current_time( 'mysql' );
		}

		return new DateTimeImmutable( $now );
	}
}
