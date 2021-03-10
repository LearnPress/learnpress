<?php

/**
 * Handle request(ajax|api) call step by step
 *
 * Class LP_Handle_Steps
 */
class LP_Handle_Upgrade_Steps extends LP_Handle_Steps {
	/**
	 * @var string
	 */
	public $version = '';

	/**
	 * Finish Step.
	 *
	 * @param LP_Step $step .
	 * @param string  $message .
	 *
	 * @return LP_Step
	 */
	public function finish_step( LP_Step $step, string $message ): LP_Step {
		$step->status  = 'finished';
		$step->percent = 100;
		$step->message = $message . '" success - Handles success';

		return $step;
	}
}
