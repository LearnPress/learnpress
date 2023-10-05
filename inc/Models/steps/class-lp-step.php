<?php

/**
 * Class LP_Group_Step
 *
 * @author tungnnx
 * @version 1.0.0
 * @since 4.0.0
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
	 * @param string $status .
	 */
	public function __construct( string $name, string $label, string $description = '', string $status = '' ) {
		$this->name        = $name;
		$this->label       = $label;
		$this->description = $description;
		$this->status      = $status;

		parent::__construct();
	}
}
