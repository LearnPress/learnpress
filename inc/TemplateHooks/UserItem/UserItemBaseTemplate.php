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
	public function html_start_time( LP_User_Item $user_item ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<span class="lp-user-item start-time">' => '</span>',
			];

			$start_date = $user_item->get_start_time( 'i18n' );
			$content    = Template::instance()->nest_elements( $html_wrapper, $start_date );
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
	public function html_end_time( LP_User_Item $user_item ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<span class="lp-user-item end-time">' => '</span>',
			];

			$start_date = $user_item->get_end_time( 'i18n' );
			$content    = Template::instance()->nest_elements( $html_wrapper, $start_date );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}
}
