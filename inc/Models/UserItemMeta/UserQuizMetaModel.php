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

class UserQuizMetaModel extends UserItemMetaModel {
	const KEY_QUESTION_CHECKED = '_lp_question_checked';
	const KEY_QUESTION_HINT    = '_lp_question_hint';
	const KEY_RETAKEN_COUNT    = '_lp_retaken_count';
}
