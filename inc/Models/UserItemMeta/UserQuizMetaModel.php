<?php

/**
 * Class UserItemModel
 * To replace class LP_User_Item
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.5
 */

namespace LearnPress\Models\UserItemMeta;

use Exception;
use LearnPress\Models\UserItemMeta\UserItemMetaModel;
use LP_User_Item_Meta_DB;
use LP_User_Item_Meta_Filter;

class UserQuizMetaModel extends UserItemMetaModel {
	const KEY_QUESTION_CHECKED = '_lp_question_checked';
	const KEY_QUESTION_HINT    = '_lp_question_hint';
	const KEY_RETAKEN_COUNT    = '_lp_retaken_count';

	/**
	 * @throws Exception
	 */
	public function get_retaken_count() {
		$filer                          = new LP_User_Item_Meta_Filter();
		$lp_user_item_meta_db           = LP_User_Item_Meta_DB::getInstance();
		$filer->only_fields = ['meta_value' ];
		$filer->learnpress_user_item_id = $this->learnpress_user_item_id;
		$filer->meta_key                = self::KEY_RETAKEN_COUNT;
		$filer->run_query_count         = false;
		$filer->return_string_query = true;
		$query = $lp_user_item_meta_db->get_user_item_metas( $filer );

		$lp_user_item_meta_db->wpdb->get_var( $query );
	}
}
