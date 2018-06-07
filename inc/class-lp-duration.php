<?php

/**
 * Class LP_Duration
 */
class LP_Duration {

	/**
	 * @var int
	 */
	protected $_duration = 0;

	/**
	 * LP_Duration constructor.
	 *
	 * @param mixed $duration
	 */
	public function __construct( $duration ) {
		if ( is_numeric( $duration ) ) {
			if ( $duration < 0 ) {
				$duration = 0;
			}
			$this->_duration = absint( $duration );
		} else {
			if ( preg_match( '~([0-9]+) (second|minute|hour|day|week)~', $duration, $m ) ) {
				$s               = array(
					'second' => 1,
					'minute' => 60,
					'hour'   => 3600,
					'day'    => 3600 * 24,
					'week'   => 3600 * 24 * 7,
					'month'  => 3600 * 30 // ???
				);
				$this->_duration = $m[1] * $s[ $m[2] ];
			}
		}
	}

	/**
	 * @param bool $leading_zero
	 *
	 * @return int
	 */
	public function get_weeks( $leading_zero = false ) {
		$weeks = $this->_duration ? ( $this->_duration - $this->_duration % ( 3600 * 24 * 7 ) ) / ( 3600 * 24 * 7 ) : 0;

		return $weeks < 10 && $leading_zero ? "0{$weeks}" : $weeks;
	}

	/**
	 * Get number of days.
	 *
	 * @param bool $leading_zero
	 *
	 * @return int
	 */
	public function get_days( $leading_zero = false ) {
		$days = $this->_duration ? ( $this->_duration - $this->_duration % ( 3600 * 24 ) ) / ( 3600 * 24 ) : 0;

		return $days < 10 && $leading_zero ? "0{$days}" : $days;
	}

	/**
	 * Get number of hours.
	 *
	 * @param bool $leading_zero
	 *
	 * @return int
	 */
	public function get_hours( $leading_zero = false ) {
		$hours = $this->_duration ? ( $this->_duration - $this->_duration % 3600 ) / 3600 : 0;

		return $hours < 10 && $leading_zero ? "0{$hours}" : $hours;
	}

	/**
	 * Get number of minutes.
	 *
	 * @param bool $leading_zero
	 *
	 * @return int
	 */
	public function get_minutes( $leading_zero = false ) {
		$minutes = $this->_duration ? ( $this->_duration - $this->_duration % 60 ) / 60 : 0;

		return $minutes < 10 && $leading_zero ? "0{$minutes}" : $minutes;
	}

	/**
	 * Get number of seconds.
	 *
	 * @param bool $leading_zero
	 *
	 * @return int|string
	 */
	public function get_seconds( $leading_zero = false ) {
		return $this->_duration < 10 && $leading_zero ? "0" . $this->_duration : $this->_duration;
	}

	/**
	 * @param array|string $format
	 * @param bool         $remove_empty
	 *
	 * @return string
	 */
	public function to_timer( $format = '', $remove_empty = false ) {
		$day    = $this->get_days();
		$mod    = $this->_duration - $day * 24 * 3600;
		$hour   = ( $mod - ( $mod % 3600 ) ) / 3600;
		$mod    = $mod - $hour * 3600;
		$minute = ( $mod - $mod % 60 ) / 60;
		$second = $mod - $minute * 60;

		$parts = array();

		if ( $day ) {
			$parts['day'] = $day;
		}

		if ( $hour ) {
			$parts['hour'] = $hour;
		}

		$parts['minute'] = $minute;
		$parts['second'] = $second;

		foreach ( $parts as $k => $v ) {
			if ( $v < 10 ) {
				$parts[ $k ] = "0{$v}";
			}
		}

		if ( $format ) {

			foreach ( array( 'day', 'hour', 'minute', 'second' ) as $p ) {
				if ( $remove_empty && array_key_exists( $p, $parts ) && intval( $parts[ $p ] ) == 0 ) {
					unset( $parts[ $p ] );
				}
				if ( ! empty( $format[ $p ] ) && ! empty( $parts[ $p ] ) ) {
					$parts[ $p ] = sprintf( $format[ $p ], $parts[ $p ] );
				}
			}

			return join( ' ', $parts );
		}

		return join( ':', $parts );
	}

	public function __toString() {
		return $this->_duration . '';
	}

	/**
	 * @param LP_Duration|int $duration
	 *
	 * @return LP_Duration
	 */
	public function diff( $duration ) {
		$diff = $duration instanceof LP_Duration ? $this->_duration - $duration->get_seconds() : $this->_duration - $duration;

		return new LP_Duration( $diff );
	}

	public function get() {
		return $this->_duration;
	}
}