<?php

/**
 * Handle upgrade lp request(ajax|api) call step by step
 *
 * Class LP_Handle_Upgrade_Steps
 * @author tungnx
 * @version 1.0.0
 */
class LP_Handle_Upgrade_Steps extends LP_Handle_Steps {
	/**
	 * Version.
	 *
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
		$lp_db = LP_Database::getInstance();
		$lp_db->set_step_complete( $step->name, 'completed' );

		$step->status  = 'finished';
		$step->percent = 100;
		$step->message = $message . '" success - Handles success';

		return $step;
	}

	/**
	 * Return error message of a step
	 *
	 * @param string $step
	 * @param string $message
	 *
	 * @return string
	 */
	public function error_step( string $step, string $message ): string {
		return sprintf( 'Step %s: %s', $step, $message );
	}
}
