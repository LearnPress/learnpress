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

	public function get_weeks() {
		return $this->_duration ? ( $this->_duration - $this->_duration % ( 3600 * 24 * 7 ) ) / ( 3600 * 24 * 7 ) : 0;
	}

	/**
	 * Get number of days.
	 */
	public function get_days() {
		return $this->_duration ? ( $this->_duration - $this->_duration % ( 3600 * 24 ) ) / ( 3600 * 24 ) : 0;
	}

	/**
	 * Get number of hours.
	 */
	public function get_hours() {
		return $this->_duration ? ( $this->_duration - $this->_duration % 3600 ) / 3600 : 0;
	}

	/**
	 * Get number of minutes.
	 */
	public function get_minutes() {
		return $this->_duration ? ( $this->_duration - $this->_duration % 60 ) / 60 : 0;
	}

	/**
	 * Get number of seconds.
	 */
	public function get_seconds() {
		return $this->_duration;
	}

	public function __toString() {
		return $this->_duration . '';
	}
}