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
	 *
	 * @return string
	 * @since 4.2.3.5
	 * @version 1.0.0
	 */
	public function html_start_date( LP_User_Item $user_item ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<span class="lp-user-item start-date">' => '</span>',
			];

			$start_time_from_db = get_post_meta( $user_item->get_id(), '_lp_start_time', true );
			if ( empty( $start_time_from_db ) ) {
				$start_date = ' - ';
			} else {
				$start_date = $user_item->get_start_time( 'i18n' );
			}

			$content = Template::instance()->nest_elements( $html_wrapper, $start_date );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html end time html of user item.
	 *
	 * @param LP_User_Item $user_item
	 *
	 * @return string
	 * @since 4.2.3.5
	 * @version 1.0.0
	 */
	public function html_end_date( LP_User_Item $user_item ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<span class="lp-user-item end-date">' => '</span>',
			];

			$start_date = $user_item->get_end_time( 'i18n' );
			$content    = Template::instance()->nest_elements( $html_wrapper, $start_date );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html expire time html of user item.
	 *
	 * @param LP_User_Item $user_item
	 *
	 * @return string
	 * @since 4.2.3.5
	 * @version 1.0.0
	 */
	public function html_expire_date( LP_User_Item $user_item ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<span class="lp-user-item expire-date">' => '</span>',
			];

			$duration_from_db = (float) get_post_meta( $user_item->get_item_id(), '_lp_duration', true );
			if ( $duration_from_db <= 0 ) {
				$expire_date_str = __( 'Never', 'learnpress' );
			} else {
				$expire_date     = $user_item->get_expiration_time();
				$expire_date_str = $expire_date->format( 'i18n' );
			}

			$content = Template::instance()->nest_elements( $html_wrapper, $expire_date_str );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}
}
