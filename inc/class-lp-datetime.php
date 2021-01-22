<?php

class LP_Datetime extends DateTime {
	const DAY_ABBR = "\x021\x03";
	const DAY_NAME = "\x022\x03";
	const MONTH_ABBR = "\x023\x03";
	const MONTH_NAME = "\x024\x03";

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
	 * @param mixed $tz
	 */
	public function __construct( $date = '', $tz = null ) {
		if ( empty( self::$gmt ) || empty( self::$stz ) ) {
			self::$gmt = new DateTimeZone( 'GMT' );
			self::$stz = new DateTimeZone( @date_default_timezone_get() );
		}

		if ( $date instanceof LP_Datetime ) {
			$this->raw_date = $date->get_raw_date();
		} else {
			$this->raw_date = $date;
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

		if ( $this->raw_date === '0000-00-00 00:00:00' ) {
			//$date = '1969-01-01 00:00:00';
		}

		/**
		 * addBy tungnx
		 * reason: fix for end_time error (certificate)
		 */
		if ( $date === '0000-00-00 00:00:00' ) {
			$date = date( 'Y-m-d H:i:s' );
		}

		//date_default_timezone_set( 'UTC' );
		$date = is_numeric( $date ) ? date( 'Y-m-d H:i:s', $date ) : $date;

		parent::__construct( $date, $tz );

		//date_default_timezone_set( self::$stz->getName() );

		$this->tz = $tz;
	}

	/**
	 * Get default timezone from param and wp settings
	 *
	 * @param mixed $tz
	 *
	 * @return DateTimeZone|null|string
	 * @since 3.1.0
	 *
	 */
	public static function get_default_timezone( $tz ) {
		if ( empty( self::$def_timezone ) ) {
			if ( ( $tz === null ) ) {
				$tz = new DateTimeZone( self::timezone_string() );
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
		return $this->getTimestamp() >= current_time( 'timestamp' ) + $interval;
	}

	public static function timezone_string() {

		if ( $timezone = get_option( 'timezone_string' ) ) {
			return $timezone;
		}

		if ( 0 === ( $utc_offset = intval( get_option( 'gmt_offset', 0 ) ) ) ) {
			return 'UTC';
		}

		$utc_offset *= 3600;


		if ( $timezone = timezone_name_from_abbr( '', $utc_offset ) ) {
			return $timezone;
		}

		foreach ( timezone_abbreviations_list() as $abbr ) {
			foreach ( $abbr as $city ) {
				if ( ( (bool) date( 'I' ) === (bool) $city['dst'] ) && $city['timezone_id'] && ( intval( $city['offset'] ) === $utc_offset ) ) {
					return $city['timezone_id'];
				}
			}
		}

		return 'UTC';
	}

	public function is_null() {
		return $this->raw_date === '0000-00-00 00:00:00';
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
				$value = (boolean) $this->format( 'L', true );
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
	 * @param string $format The date format specification string (see {@link PHP_MANUAL#date})
	 * @param boolean $local True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string   The date string in the specified format format.
	 */
	public function format( $format = '', $local = true ) {
		if ( '0000-00-00 00:00:00' === $this->raw_date ) {
			return '';
		}

		if ( $local == false && ! empty( self::$gmt ) ) {
			parent::setTimezone( self::$gmt );
		}

		$return = parent::format( $format );

		if ( $local == false && ! empty( $this->tz ) ) {
			parent::setTimezone( $this->tz );
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
}
