<?php

/**
 * Class LP_Course_Extra_Info_Fast_Query_Model
 *
 * @author tungnnx
 * @version 1.0.2
 * @since 4.1.3
 */
class LP_Course_Extra_Info_Fast_Query_Model {
	/**
	 * @var int First item of Course
	 */
	public $first_item_id = 0;
	/**
	 * @var object Total items of course
	 * {"count_items":"3","lp_lesson":"2","lp_quiz":"1", "{item_type}":"0"}
	 */
	public $total_items;

	/**
	 * Mapper stdclass to model
	 *
	 * @param stdClass $object
	 * @return LP_Course_Extra_Info_Fast_Query_Model
	 */
	public function map_stdclass( stdClass $object ): self {
		$extra_info = new self();

		foreach ( $object as $key => $value ) {
			if ( isset( $extra_info->{$key} ) ) {
				$extra_info->{$key} = $value;
			}
		}

		return $extra_info;
	}
}
