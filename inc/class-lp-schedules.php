<?php

/**
 * Class LP_Schedules
 *
 * Manage all schedules
 */
class LP_Schedules {

	/**
	 * @var LP_Background_Schedule_Items
	 */
	protected $background_schedule_items = null;

	/**
	 * LP_Schedules constructor.
	 */
	public function __construct() {
	}

	/**
	 * Add the items need to mark as completed into queue
	 * for running in background.
	 *
	 * @param string $template
	 *
	 * @return mixed
	 */
	public function queue_items() {
		return false;
	}
}

return new LP_Schedules();
