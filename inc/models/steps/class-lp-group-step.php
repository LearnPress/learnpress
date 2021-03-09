<?php

/**
 * Class LP_Group_Step
 */
class LP_Group_Step {
	public $name = '';
	public $label = '';

	/**
	 * @var LP_Step[] .
	 */
	public $steps = array();

	/**
	 * LP_Group_Step constructor.
	 *
	 * @param string $name
	 * @param string $label
	 * @param LP_Step[]  $steps
	 */
	public function __construct( string $name, string $label, array $steps ) {
		$this->name  = $name;
		$this->label = $label;
		$this->steps = $steps;
	}
}
