<?php
/**
 * Template hooks User Item Base.
 *
 * @since 4.2.3.5
 * @version 1.0.1
 */

namespace LearnPress\TemplateHooks\UserItem;

use LearnPress\Models\UserItems\UserItemModel;
use LP_Datetime;
use LP_User_Item;
use Throwable;

class UserItemBaseTemplate {
	/**
	 * Get html start time of user item.
	 *
	 * @param UserItemModel|LP_User_Item $user_item
	 * @param bool $has_time
	 *
	 * @return string
	 * @since 4.2.3.5
	 * @version 1.0.1
	 */
	public function html_start_date_time( $user_item, bool $has_time = true ): string {
		$html = '';

		try {
			if ( $user_item instanceof LP_User_Item ) {
				$userItemModel = new UserItemModel( $user_item->get_data() );
			} elseif ( $user_item instanceof UserItemModel ) {
				$userItemModel = $user_item;
			} else {
				return $html;
			}

			$start_time_from_db = $userItemModel->get_start_time();
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

			$html = sprintf( '<span class="lp-user-item start-date-time">%s</span>', $start_date_str );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html end time of user item.
	 *
	 * @param UserItemModel|LP_User_Item $user_item
	 * @param bool $has_time
	 *
	 * @return string
	 * @since 4.2.3.5
	 * @version 1.0.1
	 */
	public function html_end_date_time( $user_item, bool $has_time = true ): string {
		$html = '';

		try {
			if ( $user_item instanceof LP_User_Item ) {
				$userItemModel = new UserItemModel( $user_item->get_data() );
			} elseif ( $user_item instanceof UserItemModel ) {
				$userItemModel = $user_item;
			} else {
				return $html;
			}

			$end_time_from_db = $userItemModel->get_end_time();
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

			$html = sprintf( '<span class="lp-user-item end-date-time">%s</span>', $end_date_str );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html expire time of user item.
	 *
	 * @param UserItemModel|LP_User_Item $user_item
	 * @param bool $has_time
	 *
	 * @return string
	 * @since 4.2.3.5
	 * @version 1.0.1
	 */
	public function html_expire_date_time( $user_item, bool $has_time = true ): string {
		$html = '';

		try {
			if ( $user_item instanceof LP_User_Item ) {
				$userItemModel = new UserItemModel( $user_item->get_data() );
			} elseif ( $user_item instanceof UserItemModel ) {
				$userItemModel = $user_item;
			} else {
				return $html;
			}

			$expire_date = $userItemModel->get_expiration_time();
			if ( empty( $expire_date ) ) {
				$expire_date_str = __( 'Never', 'learnpress' );
			} else {
				if ( $has_time ) {
					$expire_date_str = $expire_date->format( LP_Datetime::I18N_FORMAT_HAS_TIME );
				} else {
					$expire_date_str = $expire_date->format( LP_Datetime::I18N_FORMAT );
				}
			}

			$html = sprintf( '<span class="lp-user-item expire-date-time">%s</span>', $expire_date_str );
			// Hook old
			if ( has_filter( 'learn-press/user-item/html-expire-date-time' ) ) {
				$html = apply_filters( 'learn-press/user-item/html-expire-date-time', $html, $user_item, $has_time );
			}

			$html = apply_filters( 'learn-press/user-item-model/html-expire-date-time', $html, $userItemModel, $has_time );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html graduation of user item.
	 * @param UserItemModel $userItemModel
	 *
	 * @return void
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function html_graduation( UserItemModel $userItemModel ): string {
		$html = '';

		$graduation = $userItemModel->get_graduation();
		if ( empty( $graduation ) ) {
			return $html;
		}

		$label = '';
		switch ( $graduation ) {
			case UserItemModel::GRADUATION_PASSED:
				$label = __( 'Passed', 'learnpress' );
				break;
			case UserItemModel::GRADUATION_FAILED:
				$label = __( 'Failed', 'learnpress' );
				break;
			default:
				break;
		}

		$html = sprintf(
			'<div class="lp-user-item graduation %s">%s%s</div>',
			$graduation,
			sprintf( '<span class="lp-icon lp-icon-%s"></span>', $graduation ),
			$label
		);

		return apply_filters( 'learn-press/user-item-model/html-graduation', $html, $userItemModel );
	}
}
