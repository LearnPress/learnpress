<?php

namespace LearnPress\Helpers;

use DateTime;
use DateTimeZone;
use LP_Debug;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class DateTime
 *
 * @since 4.4.2
 * @version 1.0.0
 */
class LPDateTime {
	const FORMAT_MYSQL = 'Y-m-d H:i:s';
	/**
	 * Format date by config WP.
	 */
	const FORMATE_I18N_DATE = 'i18n_date';
	/**
	 * Format date time by config WP.
	 */
	const FORMAT_I18N_DATE_TIME          = 'i18n_date_time';
	const FORMAT_I18N_DATE_TIME_TIMEZONE = 'i18n_date_time_timezone';
	/**
	 * Format date time Human.
	 */
	const FORMAT_HUMAN     = 'human';
	const FORMAT_HUMAN_TWO = 'human_two';
	/**
	 * String date time.
	 *
	 * @var string $raw_date .
	 */
	protected $raw_date    = null;
	protected $is_local_wp = false;

	/**
	 * Constructor.
	 *
	 * @param string|int $date
	 * @param bool $is_local_wp Is timezone setting on the WP. Default is false is GMT (UTC+0)
	 */
	public function __construct( string $date = '', bool $is_local_wp = false ) {
		$time = strtotime( $date );
		if ( empty( $date ) ) {
			$time = time();
		}

		$this->raw_date    = gmdate( self::FORMAT_MYSQL, $time );
		$this->is_local_wp = $is_local_wp;
	}

	public function get_raw_date() {
		return $this->raw_date;
	}

	/**
	 * Format date time to string
	 *
	 * @param string $format
	 * @param bool $to_local
	 * @return string
	 */
	public function format( string $format = '', bool $to_local = false ): string {
		$option_date_format = get_option( 'date_format' );
		$option_time_format = get_option( 'time_format' );

		if ( $to_local ) {
			// Convert to local timezone
			$timezone = wp_timezone();
		} else {
			// Keep sample date time, only change format
			$timezone = new DateTimeZone( 'UTC' );
		}

		switch ( $format ) {
			case self::FORMAT_MYSQL:
				return wp_date( $this->get_raw_date(), $timezone );
			case self::FORMATE_I18N_DATE:
				return wp_date( $option_date_format, $this->get_timestamp() );
			case self::FORMAT_I18N_DATE_TIME:
				return wp_date(
					$option_date_format . ' ' . $option_time_format,
					$this->get_timestamp(),
					$timezone
				);
			case self::FORMAT_I18N_DATE_TIME_TIMEZONE:
				return sprintf(
					'%s %s',
					wp_date(
						$option_date_format . ' ' . $option_time_format,
						$this->get_timestamp(),
						$timezone
					),
					wp_timezone_string()
				);
			case self::FORMAT_HUMAN:
				return sprintf(
					__( '%s ago', 'learnpress' ),
					human_time_diff( $this->get_timestamp(), time() )
				);
			default:
				return gmdate( $format, $this->get_timestamp() );
		}
	}

	/**
	 * Get timestamp of Date.
	 *
	 * @return int
	 */
	public function get_timestamp(): int {
		return strtotime( $this->raw_date );
	}
}
