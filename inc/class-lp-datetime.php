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

		//date_default_timezone_set( 'UTC' );
		//parent::__construct( $this->raw_date );
		//$m = $this->format( 'm' );
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
	 * @param string $name The name of the property.
	 *
	 * @return  mixed
	 * @deprecated 4.1.7.3
	 */
	public function __get( $name ) {
		_deprecated_function( __METHOD__, '4.1.7.3' );
		/*$value = null;

		switch ( $name ) {
			case 'daysinmonth':
				$value = $this->format( 't', true );
				break;

			case 'dayofweek':
				$value = $this->format( 'N', true );
				break;

			case 'dayofyear':
				$value = $this->format( 'z', true );
				break;

			case 'isleapyear':
				$value = (bool) $this->format( 'L', true );
				break;

			case 'day':
				$value = $this->format( 'd', true );
				break;

			case 'hour':
				$value = $this->format( 'H', true );
				break;

			case 'minute':
				$value = $this->format( 'i', true );
				break;

			case 'second':
				$value = $this->format( 's', true );
				break;

			case 'month':
				$value = $this->format( 'm', true );
				break;

			case 'ordinal':
				$value = $this->format( 'S', true );
				break;

			case 'week':
				$value = $this->format( 'W', true );
				break;

			case 'year':
				$value = $this->format( 'Y', true );
				break;

			default:
		}

		return $value;*/
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
			case 'i18n':
				$date_str = learn_press_date_i18n( $this->getTimestamp() );
				break;
			/*case 'timestamp':
				$date_str = $this->getTimestamp();
				break;*/
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
}
