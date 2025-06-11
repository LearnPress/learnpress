<?php

/**
 * Class LP_Datetime
 */
class LP_Datetime {
	/**
	 * @var string $format .
	 */
	public static $format = 'Y-m-d H:i:s';
	/**
	 * Format date by config WP.
	 */
	const I18N_FORMAT = 'i18n';
	/**
	 * Format date time by config WP.
	 */
	const I18N_FORMAT_HAS_TIME = 'i18n_has_time';
	/**
	 * Format date time Human.
	 */
	const HUMAN_FORMAT = 'human';
	/**
	 * String date time.
	 *
	 * @var string $raw_date .
	 */
	protected $raw_date = null;

	/**
	 * Constructor.
	 *
	 * @param string|int $date
	 * @param mixed $tz
	 *
	 * @throws
	 */
	public function __construct( $date = '', $tz = null ) {
		if ( $date instanceof LP_Datetime ) {
			$this->raw_date = $date->get_raw_date();
		} else {
			$this->raw_date = is_numeric( $date ) ? gmdate( self::$format, $date ) : $date;
		}

		if ( empty( $this->raw_date ) ) {
			$this->raw_date = current_time( 'mysql', 1 );
		}
	}

	/**
	 * Check date is null
	 *
	 * @return bool
	 */
	public function is_null(): bool {
		return ! $this->raw_date || $this->raw_date === '0000-00-00 00:00:00';
	}

	public function get_raw_date() {
		return $this->raw_date;
	}

	/**
	 * @return  string  The date as a formatted string.
	 */
	public function __toString() {
		return $this->format( self::$format );
	}

	/**
	 * Gets the date as a formatted string.
	 *
	 * @param string $format Set i18n to return date in local time.
	 *
	 * @return string.
	 * @version 4.0.2
	 * @since 3.0.0
	 */
	public function format( string $format = '' ): string {
		$date_str = '';

		if ( '0000-00-00 00:00:00' === $this->get_raw_date() ) {
			return $date_str;
		}

		if ( empty( $format ) ) {
			$format = get_option( 'date_format', 'Y-m-d' );
		}

		switch ( $format ) {
			case 'i18n': // Display format Date by Timezone of WP.
				$time_stamp              = $this->getTimestamp(); // UTC+0 (GMT)
				$time_stamp_by_time_zone = $time_stamp + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
				$date_format             = get_option( 'date_format' );
				$date_str                = date_i18n( $date_format, $time_stamp_by_time_zone );
				break;
			case 'i18n_has_time': // Display format Date Time by Timezone of WP.
				$date_time_format_wp     = apply_filters(
					'learn-press/datetime/format/i18n_has_time',
					get_option( 'date_format' ) . ' ' . get_option( 'time_format' )
				);
				$time_stamp              = $this->getTimestamp(); // UTC+0 (GMT)
				$time_stamp_by_time_zone = $time_stamp + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
				$date_str                = apply_filters(
					'learn-press/datetime/date/i18n_has_time',
					sprintf(
						'%s',
						date_i18n( $date_time_format_wp, $time_stamp_by_time_zone )
					),
					$time_stamp,
					$time_stamp_by_time_zone
				);
				break;
			case 'human':
				$time      = $this->getTimestamp();
				$time_diff = time() - $time;

				if ( $time_diff > 0 ) {
					$date_str = sprintf( __( '%s ago', 'learnpress' ), human_time_diff( $time, time() ) );
				}
				break;
			case 'mysql':
				$date_str = gmdate( 'Y-m-d H:i:s', $this->getTimestamp() );
				break;
			default:
				$date_str = gmdate( $format, $this->getTimestamp() );
		}

		if ( ! is_string( $date_str ) ) {
			$date_str = '';
		}

		return $date_str;
	}

	/**
	 * Display date human time diff.
	 * 1. Show number days, hours if >= 1 days
	 * 2. Show number hours, seconds if >= 1 hours
	 * 3. Show number seconds if < 1 hours
	 *
	 * @param DateTime $date_start
	 * @param DateTime $date_end
	 *
	 * @return string
	 * @since 4.0.3
	 * @version 1.0.5
	 */
	public static function format_human_time_diff( DateTime $date_start, DateTime $date_end ): string {
		$diff = $date_end->diff( $date_start );
		$week = floor( $diff->d / 7 );

		$i18n_year   = self::get_string_plural_duration( $diff->y, 'year' );
		$i18n_month  = self::get_string_plural_duration( $diff->m, 'month' );
		$i18n_week   = self::get_string_plural_duration( $week, 'week' );
		$i18n_day    = self::get_string_plural_duration( $diff->d, 'day' );
		$i18n_hour   = self::get_string_plural_duration( $diff->h, 'hour' );
		$i18n_minute = self::get_string_plural_duration( $diff->i, 'minute' );
		$i18n_second = self::get_string_plural_duration( $diff->s, 'second' );

		$format_date = '';
		$string      = array(
			'y' => '%y years',
			'm' => '%m months',
			'w' => '', // object don't have week, only add to custom week format
			'd' => '%d days, %h hours',
			'h' => '%h hours, %i minutes',
			'i' => '%i minutes, %s seconds',
			's' => $i18n_second,
		);

		foreach ( $string as $k => $v ) {
			if ( isset( $diff->{$k} ) && $diff->{$k} > 0 ) {
				switch ( $k ) {
					case 'y':
						$format_date = sprintf(
							'%1$s%2$s',
							$i18n_year,
							$diff->m > 0 ? ', ' . $i18n_month : ''
						);
						break;
					case 'm':
						$format_date = sprintf(
							'%1$s%2$s',
							$i18n_month,
							$diff->d > 0 ? ', ' . $i18n_day : ''
						);
						break;
					case 'd':
						$format_date = sprintf(
							'%1$s%2$s',
							$i18n_day,
							$diff->h > 0 ? ', ' . $i18n_hour : ''
						);
						break;
					case 'h':
						$format_date = sprintf(
							'%1$s%2$s',
							$i18n_hour,
							$diff->i > 0 ? ', ' . $i18n_minute : ''
						);
						break;
					case 'i':
						$format_date = sprintf(
							'%1$s%2$s',
							$i18n_minute,
							$diff->s > 0 ? ', ' . $i18n_second : ''
						);
						break;
					default:
						$format_date = $v;
						break;
				}
				break;
			} elseif ( 'w' === $k && $week > 0 ) {
				$day_remain  = $diff->d - $week * 7;
				$format_date = sprintf(
					'%1$s%2$s',
					$i18n_week,
					$day_remain > 0 ? ', ' . self::get_string_plural_duration( $day_remain, 'day' ) : ''
				);
				break;
			}
		}

		return apply_filters(
			'learn-press/datetime/format_human_time_diff',
			$format_date,
			$diff,
			$date_start,
			$date_end
		);
	}

	/**
	 * Get the date as an SQL datetime string.
	 *
	 * @param boolean $local True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string
	 * @version 4.0.1
	 * @since 3.0.0
	 */
	public function toSql( bool $local = false ): string {
		if ( $local ) {
			return $this->toSqlLocal();
		}

		return $this->format( 'mysql' );
	}

	/**
	 * Convert to format sql local time.
	 *
	 * @return string
	 * @since 4.2.1
	 */
	private function toSqlLocal(): string {
		$time = $this->getTimestamp() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;

		return gmdate( LP_Datetime::$format, $time );
	}

	/**
	 * Get timestamp of Date.
	 *
	 * @return int
	 */
	public function getTimestamp(): int {
		try {
			$date = new DateTime( $this->get_raw_date() );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			$date = new DateTime();
		}

		return $date->getTimestamp();
	}

	/**
	 * Get timestamp of Date in local time.
	 * Note: timestamp before handle must timezone is GMT+0
	 *
	 * @return int
	 * @since 4.2.7.3
	 */
	public function getTimestampLocal(): int {
		return $this->getTimestamp() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
	}

	/**
	 * Get string plural duration.
	 *
	 * @param int $duration_number
	 * @param string $duration_type
	 *
	 * @return string
	 * @version 1.0.5
	 * @since 4.2.3.5
	 */
	public static function get_string_plural_duration( int $duration_number, string $duration_type = '' ): string {
		switch ( strtolower( $duration_type ) ) {
			case 'second':
				$duration_str = sprintf(
					_n( '%s Second', '%s Seconds', $duration_number, 'learnpress' ),
					$duration_number
				);
				break;
			case 'minute':
				$duration_str = sprintf(
					_n( '%s Minute', '%s Minutes', $duration_number, 'learnpress' ),
					$duration_number
				);
				break;
			case 'hour':
				$duration_str = sprintf(
					_n( '%s Hour', '%s Hours', $duration_number, 'learnpress' ),
					$duration_number
				);
				break;
			case 'day':
				$duration_str = sprintf(
					_n( '%s Day', '%s Days', $duration_number, 'learnpress' ),
					$duration_number
				);
				break;
			case 'week':
				$duration_str = sprintf(
					_n( '%s Week', '%s Weeks', $duration_number, 'learnpress' ),
					$duration_number
				);
				break;
			case 'month':
				$duration_str = sprintf(
					_n( '%s Month', '%s Months', $duration_number, 'learnpress' ),
					$duration_number
				);
				break;
			case 'year':
				$duration_str = sprintf(
					_n( '%s Year', '%s Years', $duration_number, 'learnpress' ),
					$duration_number
				);
				break;
			default:
				$duration_str = $duration_number . ' ' . $duration_type;
		}

		return apply_filters( 'learn-press/i18n/plural_duration', $duration_str, $duration_number, $duration_type );
	}

	/**
	 * Get timezone string
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public static function get_timezone_string(): string {
		$wp_timezone = wp_timezone_string();
		$is_utc      = (int) $wp_timezone !== 0;

		if ( $is_utc ) {
			$wp_timezone = sprintf( '%s %s', __( 'Timezone: UTC', 'learnpress' ), $wp_timezone );
		} else {
			$wp_timezone = sprintf( '%s %s', __( 'Timezone:', 'learnpress' ), $wp_timezone );
		}

		return $wp_timezone;
	}
}
