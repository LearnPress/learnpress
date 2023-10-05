<?php

/**
 * Class UserItemModel
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.5
 */

namespace LearnPress\Models\UserItems;

use Exception;
use LP_Datetime;

/**
 * @method update()
 */
class UserItemQuizModel extends UserItemModel {
	/**
	 * Item type Course
	 *
	 * @var string Item type
	 */
	public $item_type = LP_QUIZ_CPT;
	/**
	 * Ref type Order
	 *
	 * @var string
	 */
	public $ref_type = LP_COURSE_CPT;
	/**
	 * Meta data of quiz.
	 *
	 * @var UserItemQuizMetaModel
	 */
	public $meta_data;

	/**
	 * Get status
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Retake quiz.
	 *
	 * @throws Exception
	 */
	public function retake(): UserItemQuizModel {
		$this->check_can_retake();

		//Todo: update quiz meta data.

		$this->status     = LP_ITEM_STARTED;
		$this->start_time = gmdate( LP_Datetime::$format, time() );
		$this->end_time   = null;
		$this->graduation = LP_COURSE_GRADUATION_IN_PROGRESS;
		return $this->save();
	}

	/**
	 * @throws Exception
	 */
	public function check_can_retake() {

	}
}
