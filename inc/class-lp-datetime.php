<?php

/**
 * Class LP_Datetime
 */
class LP_Datetime extends DateTime {
	/**
	 * @var    string
	 */
	public static $format = 'Y-m-d H:i:s';

	/**
	 * @var    object
	 */
	protected static $gmt;

	/**
	 * @var    object
	 */
	protected static $stz;

	/**
	 * @var    DateTimeZone
	 */
	protected $tz;

	protected $raw_date = null;

	protected static $def_timezone = null;

	/**
	 * Constructor.
	 *
	 * @param string $date
	 * @param mixed  $tz
	 *
	 * @throws
	 */
	public function __construct( $date = '', $tz = null ) {
		if ( empty( self::$gmt ) || empty( self::$stz ) ) {
			self::$gmt = new DateTimeZone( 'GMT' );
			self::$stz = new DateTimeZone( @date_default_timezone_get() ); // phpcs:ignore
		}

		if ( $date instanceof LP_Datetime ) {
			$this->raw_date = $date->get_raw_date();
		} else {
			$this->raw_date = is_numeric( $date ) ? gmdate( 'Y-m-d H:i:s', $date ) : $date;
		}

		if ( empty( $date ) ) {
			$date = current_time( 'mysql' );
		}

		if ( ! ( $tz instanceof DateTimeZone ) ) {
			$tz = self::get_default_timezone( $tz );
		}

		if ( ! $tz ) {
			$tz = null;
		}

		date_default_timezone_set( 'UTC' );

		parent::__construct( $this->raw_date, $tz );

		date_default_timezone_set( self::$stz->getName() ); // phpcs:ignore

		$this->tz = $tz;
	}

	/**
	 * Get default timezone from param and wp settings
	 *
	 * @param mixed $tz
	 *
	 * @return DateTimeZone|null|string
	 */
	public static function get_default_timezone( $tz ) {
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
	 */
	public function is_exceeded( $interval = 0 ) {
		return $this->getTimestamp() >= current_time( 'timestamp' ) + $interval; // phpcs:ignore
	}

	public function is_null() {
		return ! $this->raw_date || $this->raw_date === '0000-00-00 00:00:00';
	}

	public function get_raw_date() {
		return $this->raw_date;
	}

	/**
	 * @param string $name The name of the property.
	 *
	 * @return  mixed
	 */
	public function __get( $name ) {
		$value = null;

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

		return $value;
	}

	/**
	 * @return  string  The date as a formatted string.
	 */
	public function __toString() {
		return (string) $this->format( self::$format, true );
	}

	/**
	 * Gets the date as a formatted string.
	 *
	 * @param string  $format The date format specification string (see {@link PHP_MANUAL#date})
	 * @param boolean $local True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return string The date string in the specified format format.
	 */
	public function format( $format, $local = true ) {
		if ( '0000-00-00 00:00:00' === $this->raw_date ) {
			return '';
		}

		if ( empty( $format ) ) {
			$format = 'mysql';
		}

		$return = false;

		switch ( $format ) {
			case 'i18n':
				$return = learn_press_date_i18n( $this->getTimestamp( $local ) );
				break;
			case 'timestamp':
				$return = $this->getTimestamp( $local );
				break;
			case 'human':
				$time      = $this->getTimestamp( true );// mysql2date( 'G', $date->format('Y-m-d H:i:s') );
				$time1     = $this->getTimestamp( false );// mysql2date( 'G', $date->format('Y-m-d H:i:s') );
				$time_diff = ( time() ) - $time1;

				if ( $time_diff > 0 ) {
					$return = sprintf( __( '%s ago', 'learnpress' ), human_time_diff( $time1, time() ) );
				}
				break;
			case 'mysql':
				$return = $this->format( 'Y-m-d H:i:s', $local );
				break;
			default:
				if ( ! $local && ! empty( self::$gmt ) ) {
					parent::setTimezone( self::$gmt );
				}

				$return = parent::format( $format );

				if ( ! $local && ! empty( $this->tz ) ) {
					parent::setTimezone( $this->tz );
				}
		}

		return $return;
	}

	/**
	 * @param boolean $hours True to return the value in hours.
	 *
	 * @return float
	 */
	public function getOffset( $hours = false ) {
		return $this->tz ? (float) $hours ? ( $this->tz->getOffset( $this ) / 3600 ) : $this->tz->getOffset( $this ) : 0;
	}

	/**
	 * @param DateTimeZone $tz The new DateTimeZone object.
	 *
	 * @return void
	 */
	public function setTimezone( $tz ) {
		$this->tz = $tz;

		parent::setTimezone( $tz );
	}

	/**
	 * @param boolean $local True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string
	 */
	public function toISO8601( $local = true ) {
		return $this->format( DateTime::RFC3339, $local );
	}

	/**
	 * Gets the date as an SQL datetime string.
	 *
	 * @param boolean $local True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string
	 */
	public function toSql( $local = true ) {
		return $this->format( 'Y-m-d H:i:s', $local );
	}

	/**
	 * Consider the date is in GMT and convert to local time with
	 * gmt_offset option of WP Core.
	 *
	 * @param string $format
	 *
	 * @return int|string
	 * @since 4.0.0
	 */
	public function toLocal( $format = 'Y-m-d H:i:s' ) {
		$time = $this->getTimestamp() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;

		if ( $format ) {
			return date( $format, $time ); // phpcs:ignore
		}

		return $time;
	}

	/**
	 * Gets the date as an RFC 822 string.  IETF RFC 2822 supercedes RFC 822 and its definition
	 * can be found at the IETF Web site.
	 *
	 * @param boolean $local True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string
	 */
	public function toRFC822( $local = true ) {
		return $this->format( DateTime::RFC2822, $local );
	}

	/**
	 * Gets the date as UNIX time stamp.
	 *
	 * @return  integer  The date as a UNIX timestamp.
	 */
	public function toUnix() {
		return (int) parent::format( 'U' );
	}

	public function getTimestamp( $local = true ) {
		$this->setGMT( $local );
		$timestamp = parent::getTimestamp();
		$this->setGMT( $local, false );

		if ( $local ) {
			$timestamp += $this->getOffset();
		}

		return $timestamp;
	}

	protected function setGMT( $local = false, $gmt = true ) {
		if ( $gmt ) {
			if ( $local == false && ! empty( self::$gmt ) ) {
				parent::setTimezone( self::$gmt );
			}
		} else {
			if ( $local == false && ! empty( $this->tz ) ) {
				parent::setTimezone( $this->tz );
			}
		}
	}

	public static function getSqlNullDate() {
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
	 */
	public function addDuration( $seconds ) {
		$timestamp = $this->getTimestamp();
		parent::__construct( date( 'Y-m-d H:i:s', $timestamp + $seconds ), $this->tz ); // phpcs:ignore
	}

	public function getPeriod( $seconds, $local = true ) {
		$timestamp = $this->getTimestamp( $local );

		if ( ! is_numeric( $seconds ) ) {
			$seconds = strtotime( $seconds ) - time();
		}

		return date( 'Y-m-d H:i:s', $timestamp + $seconds ); // phpcs:ignore
	}
}
