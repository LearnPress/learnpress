<?php

namespace LearnPress\Helpers;

use DateTime;
use DateTimeZone;

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
	const FORMAT_I18N_DATE = 'i18n_date';
	/**
	 * Format date time by config WP.
	 */
	const FORMAT_I18N_DATE_TIME = 'i18n_date_time';
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
	protected $raw_date = '';

	/**
	 * Constructor.
	 *
	 * @param string $date_gmt Date must be in GMT (UTC+0).
	 */
	public function __construct( string $date_gmt = '' ) {
		$time = strtotime( $date_gmt );
		if ( empty( $date_gmt ) ) {
			$time = time();
		}

		$this->raw_date = (string) gmdate( self::FORMAT_MYSQL, $time );
	}

	/**
	 * Get raw date.
	 *
	 * @return string
	 */
	public function get_raw_date(): string {
		return $this->raw_date;
	}

	/**
	 * Get timestamp of Date.
	 *
	 * @return int
	 */
	public function get_timestamp(): int {
		return strtotime( $this->get_raw_date() );
	}

	/**
	 * Format the date/time into a string.
	 *
	 * - $type_convert = 'keep' preserves the GMT/UTC time.
	 * - $type_convert = 'gmt_to_local' converts to the site's local timezone.
	 *
	 * @param string $format Output format (MYSQL, I18N_DATE, I18N_DATE_TIME, HUMAN, or a custom format string).
	 * @param string $type_convert Timezone conversion mode: keep|gmt_to_local.
	 * @param bool $return_has_timezone Whether to append the timezone name to the output.
	 *
	 * @return string The formatted date/time string.
	 */
	public function format( string $format = '', string $type_convert = 'keep', bool $return_has_timezone = false ): string {
		$string             = '';
		$option_date_format = get_option( 'date_format' );
		$option_time_format = get_option( 'time_format' );
		$timestamp          = $this->get_timestamp();
		$timezone           = 'gmt_to_local' === $type_convert ? wp_timezone() : new DateTimeZone( 'UTC' );

		switch ( $format ) {
			case self::FORMAT_MYSQL:
				$string = wp_date( self::FORMAT_MYSQL, $timestamp, $timezone );
				break;
			case self::FORMAT_I18N_DATE:
				$string = wp_date( $option_date_format, $timestamp, $timezone );
				break;
			case self::FORMAT_I18N_DATE_TIME:
				$string = wp_date( $option_date_format . ' ' . $option_time_format, $timestamp, $timezone );
				break;
			case self::FORMAT_HUMAN:
				$string = sprintf( __( '%s ago', 'learnpress' ), human_time_diff( $timestamp, time() ) );
				break;
			default:
				$string = wp_date( $format, $timestamp, $timezone );
				break;
		}

		if ( $return_has_timezone ) {
			$string .= ' ' . $timezone->getName();
		}

		return $string;
	}

	/**
	 * Display date human time diff.
	 * 1. Show number days, hours if >= 1 days
	 * 2. Show number hours, seconds if >= 1 hours
	 * 3. Show number seconds if < 1 hours
	 * Clone from LP_Datetime::format_human_time_diff since 4.0.3
	 *
	 * @param DateTime $date_start
	 * @param DateTime $date_end
	 *
	 * @return string
	 * @since 4.4.2
	 * @version 1.0.0
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
	 * Get string plural duration.
	 * Clone from LP_Datetime::get_string_plural_duration since 4.2.3.5
	 *
	 * @param int $duration_number
	 * @param string $duration_type
	 *
	 * @return string
	 * @version 1.0.0
	 * @since 4.4.2
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
}
