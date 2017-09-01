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

	/**
	 * Constructor.
	 *
	 * @param   string $date
	 * @param   mixed  $tz
	 */
	public function __construct( $date = '', $tz = null ) {
		if ( empty( self::$gmt ) || empty( self::$stz ) ) {
			self::$gmt = new DateTimeZone( 'GMT' );
			self::$stz = new DateTimeZone( @date_default_timezone_get() );
		}

		if ( empty( $date ) ) {
			$date = current_time( 'mysql' );
		}

		if ( ! ( $tz instanceof DateTimeZone ) ) {
			if ( ( $tz === null ) && $tz = get_option( 'timezone_string' ) ) {
				$tz = new DateTimeZone( $tz );
			} elseif ( is_string( $tz ) ) {
				$tz = new DateTimeZone( $tz );
			}
		}

		date_default_timezone_set( 'UTC' );
		$date = is_numeric( $date ) ? date( 'c', $date ) : $date;

		parent::__construct( $date, $tz );

		date_default_timezone_set( self::$stz->getName() );

		$this->tz = $tz;
	}

	/**
	 * @param   string $name The name of the property.
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
		return (string) parent::format( self::$format );
	}

	/**
	 * Gets the date as a formatted string.
	 *
	 * @param   string  $format The date format specification string (see {@link PHP_MANUAL#date})
	 * @param   boolean $local  True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string   The date string in the specified format format.
	 */
	public function format( $format, $local = true ) {
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
		return (float) $hours ? ( $this->tz->getOffset( $this ) / 3600 ) : $this->tz->getOffset( $this );
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
	 * @param   boolean $local True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string
	 */
	public function toISO8601( $local = true ) {
		return $this->format( DateTime::RFC3339, $local );
	}

	/**
	 * Gets the date as an SQL datetime string.
	 *
	 * @param   boolean $local True to return the date string in the local time zone, false to return it in GMT.
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
	 * @param   boolean $local True to return the date string in the local time zone, false to return it in GMT.
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
}
