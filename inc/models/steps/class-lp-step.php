<?php

/**
 * Class LP_Group_Step
 */
class LP_Step extends LP_REST_Response {
	/**
	 * Step Name
	 *
	 * @var string
	 */
	public $name = '';
	/**
	 * Step label
	 *
	 * @var string
	 */
	public $label = '';
	/**
	 * @var string
	 */
	public $description = '';
	/**
	 * @var int .
	 */
	public $percent = 0;

	/**
	 * LP_Step constructor.
	 *
	 * @param string $name .
	 * @param string $label .
	 * @param string $description .
	 */
	public function __construct( string $name, string $label, $description = '' ) {
		$this->name        = $name;
		$this->label       = $label;
		$this->description = $description;

		parent::__construct();
	}
}
