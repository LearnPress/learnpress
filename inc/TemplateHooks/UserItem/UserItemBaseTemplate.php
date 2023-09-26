<?php
/**
 * Template hooks User Item Base.
 *
 * @since 4.2.3.5
 * @version 1.0.0
 */
namespace LearnPress\TemplateHooks\UserItem;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LP_Datetime;
use LP_User_Item;
use Throwable;

class UserItemBaseTemplate {
	use Singleton;

	public function init() {

	}

	/**
	 * Get html start time html of user item.
	 *
	 * @param LP_User_Item $user_item
	 * @param bool $has_time
	 * @return string
	 * @since 4.2.3.5
	 * @version 1.0.0
	 */
	public function html_start_date_time( LP_User_Item $user_item, bool $has_time = true ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<span class="lp-user-item start-date-time">' => '</span>',
			];

			$start_time_from_db = $user_item->get_data( LP_User_Item::KEY_DATA_START_TIME );
			if ( empty( $start_time_from_db ) ) {
				$start_date_str = ' - ';
			} else {
				$start_date = new LP_Datetime( $start_time_from_db );
				if ( $has_time ) {
					$start_date_str = $start_date->format( LP_Datetime::I18N_FORMAT_HAS_TIME );
				} else {
					$start_date_str = $start_date->format( LP_Datetime::I18N_FORMAT );
				}
			}

			$content = Template::instance()->nest_elements( $html_wrapper, $start_date_str );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html end time html of user item.
	 *
	 * @param LP_User_Item $user_item
	 * @param bool $has_time
	 * @return string
	 * @since 4.2.3.5
	 * @version 1.0.0
	 */
	public function html_end_date_time( LP_User_Item $user_item, bool $has_time = true ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<span class="lp-user-item end-date-time">' => '</span>',
			];

			$end_time_from_db = $user_item->get_data( LP_User_Item::KEY_DATA_END_TIME );
			if ( empty( $end_time_from_db ) ) {
				$end_date_str = ' - ';
			} else {
				$end_date = new LP_Datetime( $end_time_from_db );
				if ( $has_time ) {
					$end_date_str = $end_date->format( LP_Datetime::I18N_FORMAT_HAS_TIME );
				} else {
					$end_date_str = $end_date->format( LP_Datetime::I18N_FORMAT );
				}
			}
			$content = Template::instance()->nest_elements( $html_wrapper, $end_date_str );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html expire time html of user item.
	 *
	 * @param LP_User_Item $user_item
	 * @param bool $has_time
	 * @return string
	 * @since 4.2.3.5
	 * @version 1.0.0
	 */
	public function html_expire_date_time( LP_User_Item $user_item, bool $has_time = true ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<span class="lp-user-item expire-date-time">' => '</span>',
			];

			$expire_date = $user_item->get_expiration_time();
			if ( empty( $expire_date ) ) {
				$expire_date_str = __( 'Never', 'learnpress' );
			} else {
				if ( $has_time ) {
					$expire_date_str = $expire_date->format( LP_Datetime::I18N_FORMAT_HAS_TIME );
				} else {
					$expire_date_str = $expire_date->format( LP_Datetime::I18N_FORMAT );
				}
			}

			$content = Template::instance()->nest_elements( $html_wrapper, $expire_date_str );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}
}
