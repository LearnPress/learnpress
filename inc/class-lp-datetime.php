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
	 * @var object
	 */
	protected static $gmt;

	/**
	 * @var object
	 */
	protected static $stz;

	/**
	 * @var DateTimeZone
	 */
	protected $tz;

	/**
	 * String date time.
	 *
	 * @var string $raw_date .
	 */
	protected $raw_date = null;

	protected static $def_timezone = null;

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
	 * Check if time is exceeded with current time
	 *
	 * using by Addon Content Drip.
	 *
	 * @since 3.0.1
	 * @version 4.0.1
	 */
	public function is_exceeded(): bool {
		return $this->getTimestamp() >= time();
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
	 * @param boolean $local .
	 *
	 * @return string.
	 * @version 4.0.1
	 * @since 3.0.0
	 */
	public function format( string $format = '', bool $local = true ): string {
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
	 * @version 1.0.1
	 */
	public static function format_human_time_diff( DateTime $date_start, DateTime $date_end ): string {
		$diff = $date_end->diff( $date_start );
		$week = floor( $diff->d / 7 );

		$format_date = '';
		$string      = array(
			'y' => '%y years',
			'm' => '%m months',
			'w' => '', // object don't have week, only add to custom week format
			'd' => '%d days, %h hours',
			'h' => '%h hours, %i minutes',
			'i' => '%i minutes, %s seconds',
			's' => '%s seconds',
		);

		foreach ( $string as $k => $v ) {
			if ( isset( $diff->{$k} ) && $diff->{$k} > 0 ) {
				if ( in_array( $k, array( 'y', 'm' ) ) ) {
					$date        = new LP_Datetime( $date_end->getTimestamp() );
					$format_date = $date->format( LP_Datetime::I18N_FORMAT_HAS_TIME );
				} else {
					$format_date = $diff->format( $v );
				}
				break;
			} elseif ( 'w' === $k && $week > 0 ) {
				$day_remain  = $diff->d - $week * 7;
				$format_date = sprintf(
					'%d %s, %d %s', $week,
					_n( 'week', 'weeks', $week ),
					$day_remain,
					_n( 'day', 'days', $day_remain )
				);
				break;
			}
		}

		return apply_filters(
			'learn-press/datetime/format_human_time_diff',
			$format_date, $diff, $date_start, $date_end
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
	public function getTimestamp( $local = true ): int {
		try {
			if ( ! $local ) {
				_deprecated_argument( __METHOD__, '4.2.6.6' );
			}

			$date = new DateTime( $this->get_raw_date() );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
			$date = new DateTime();
		}

		return $date->getTimestamp();
	}

	/**
	 * Get string plural duration.
	 *
	 * @param float $duration_number
	 * @param string $duration_type
	 *
	 * @return string
	 * @version 1.0.1
	 * @since 4.2.3.5
	 */
	public static function get_string_plural_duration( float $duration_number, string $duration_type = '' ): string {
		if ( $duration_number == 0 ) {
			return esc_html__( 'Lifetime', 'learnpress' );
		}

		switch ( strtolower( $duration_type ) ) {
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
			default:
				$duration_str = $duration_number;
		}

		return $duration_str;
	}
}
