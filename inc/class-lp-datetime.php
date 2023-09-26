<?php

/**
 * Class LP_Datetime
 */
class LP_Datetime {
	/**
	 * @var string $format.
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
	 * @var string $raw_date.
	 */
	protected $raw_date = null;

	protected static $def_timezone = null;

	/**
	 * Constructor.
	 *
	 * @param string|int $date
	 * @param mixed  $tz
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
	 * Get default timezone from param and wp settings
	 *
	 * @param mixed $tz
	 *
	 * @return DateTimeZone|null|string
	 * @deprecated 4.1.7.3
	 */
	public static function get_default_timezone( $tz ) {
		_deprecated_function( __METHOD__, '4.1.7.3' );
		if ( empty( self::$def_timezone ) ) {
			if ( $tz === null ) {
				$tz = wp_timezone();
			} elseif ( is_string( $tz ) && $tz ) {
				$tz = new DateTimeZone( $tz );
			}
			self::$def_timezone = $tz;
		}

		return self::$def_timezone;
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
	 * @param string  $format Set i18n to return date in local time.
	 * @param boolean $local.
	 *
	 * @since 3.0.0
	 * @version 4.0.1
	 * @return string.
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
				$date_str                = learn_press_date_i18n( $time_stamp_by_time_zone );
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
						gmdate( $date_time_format_wp, $time_stamp_by_time_zone )
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
	 * @version 1.0.0
	 * @since 4.0.3
	 * @return string
	 */
	public static function format_human_time_diff( DateTime $date_start, DateTime $date_end ): string {
		$diff = $date_end->diff( $date_start );

		$format_date = '';
		if ( $diff->d > 0 ) {
			$format_date .= '%d days, ';

			if ( $diff->h > 0 ) {
				$format_date .= '%h hours';
			}
		} elseif ( $diff->h > 0 ) {
			$format_date .= '%h hours, ';

			if ( $diff->i > 0 ) {
				$format_date .= '%i minutes';
			}
		} elseif ( $diff->i > 0 ) {
			$format_date .= '<span class="minute">%i</span> minutes';
		} else {
			$format_date .= '<span class="second">%s</span> seconds';
		}

		return $diff->format( $format_date );
	}

	/**
	 * @param boolean $hours True to return the value in hours.
	 *
	 * @return float
	 * @deprecated 4.1.7.3
	 */
	public function getOffset( $hours = false ) {
		_deprecated_function( __METHOD__, '4.1.7.3' );
		return $this->tz ? (float) $hours ? ( $this->tz->getOffset( $this ) / 3600 ) : $this->tz->getOffset( $this ) : 0;
	}

	/**
	 * @param DateTimeZone $tz The new DateTimeZone object.
	 *
	 * @return void
	 * @deprecated 4.1.7.3
	 */
	/*public function setTimezone( $tz ) {
		_deprecated_function( __METHOD__, '4.1.7.3' );
		$this->tz = $tz;

		parent::setTimezone( $tz );
	}*/

	/**
	 * @param boolean $local True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string
	 * @deprecated 4.1.7.3
	 */
	public function toISO8601( $local = true ) {
		_deprecated_function( __METHOD__, '4.1.7.3' );
		//return $this->format( DateTime::RFC3339, $local );
	}

	/**
	 * Get the date as an SQL datetime string.
	 *
	 * @param boolean $local True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @since 3.0.0
	 * @version 4.0.1
	 * @return  string
	 */
	public function toSql( bool $local = false ): string {
		if ( $local ) {
			return $this->toSqlLocal();
		}

		return $this->format( 'mysql' );
	}

	/**
	 * Consider the date is in GMT and convert to local time with
	 * gmt_offset option of WP Core.
	 *
	 * @param string $format
	 *
	 * @return int|string
	 * @since 4.0.0
	 * @deprecated 4.1.7.3
	 */
	public function toLocal( $format = 'Y-m-d H:i:s' ) {
		_deprecated_function( __METHOD__, '4.1.7.3' );
		$time = $this->getTimestamp() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;

		if ( $format ) {
			return date( $format, $time ); // phpcs:ignore
		}

		return $time;
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
	 * Gets the date as an RFC 822 string.  IETF RFC 2822 supercedes RFC 822 and its definition
	 * can be found at the IETF Web site.
	 *
	 * @param boolean $local True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string
	 * @deprecated 4.1.7.3
	 */
	public function toRFC822( $local = true ) {
		_deprecated_function( __METHOD__, '4.1.7.3' );
		return $this->format( DateTime::RFC2822, $local );
	}

	/**
	 * Gets the date as UNIX time stamp.
	 *
	 * @return  integer  The date as a UNIX timestamp.
	 * @deprecated 4.1.7.3
	 */
	public function toUnix() {
		_deprecated_function( __METHOD__, '4.1.7.3' );
		//return (int) parent::format( 'U' );
	}

	/**
	 * Get timestamp of Date.
	 *
	 * @return int
	 */
	public function getTimestamp( $local = true ): int {
		try {
			$date = new DateTime( $this->get_raw_date() );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
			$date = new DateTime();
		}

		return $date->getTimestamp();
	}

	/**
	 * @deprecated 4.1.7.3
	 */
	protected function setGMT( $local = false, $gmt = true ) {
		_deprecated_function( __METHOD__, '4.1.7.3' );
		/*if ( $gmt ) {
			if ( $local == false && ! empty( self::$gmt ) ) {
				parent::setTimezone( self::$gmt );
			}
		} else {
			if ( $local == false && ! empty( $this->tz ) ) {
				parent::setTimezone( $this->tz );
			}
		}*/
	}

	/**
	 * @deprecated 4.1.7.3
	 */
	public static function getSqlNullDate() {
		_deprecated_function( __METHOD__, '4.1.7.3' );
		return '0000-00-00 00:00:00';
	}

	/**
	 * Add X seconds into datetime of this object.
	 *
	 * @param int $seconds
	 *
	 * @throws
	 *
	 * @since 3.3.0
	 * @deprecated 4.1.7.3
	 */
	public function addDuration( $seconds ) {
		_deprecated_function( __METHOD__, '4.1.7.3' );
		$timestamp = $this->getTimestamp();
		//parent::__construct( date( 'Y-m-d H:i:s', $timestamp + $seconds ), $this->tz ); // phpcs:ignore
	}

	/**
	 * @deprecated 4.1.7.3
	 */
	public function getPeriod( $seconds, $local = true ) {
		_deprecated_function( __METHOD__, '4.1.7.3' );
		/*$timestamp = $this->getTimestamp( $local );

		if ( ! is_numeric( $seconds ) ) {
			$seconds = strtotime( $seconds ) - time();
		}

		return date( 'Y-m-d H:i:s', $timestamp + $seconds );*/
	}

	/**
	 * Get string plural duration.
	 *
	 * @param float $duration_number
	 * @param string $duration_type
	 *
	 * @return string
	 * @version 1.0.0
	 * @since 4.2.3.5
	 */
	public static function get_string_plural_duration( float $duration_number, string $duration_type = '' ): string {
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
