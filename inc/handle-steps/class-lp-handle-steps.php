<?php

/**
 * Handle request(ajax|api) call step by step
 *
 * Class LP_Handle_Steps
 * @author tungnx
 * @version 1.0.0
 */
class LP_Handle_Steps {
	/**
	 * @var LP_Group_Step[]
	 */
	public $group_steps = array();

	/**
	 * @param array $params | keys: steps, step, data.
	 */
	public function handle( array $params ) {
		$response = new LP_REST_Response();

		try {
			$steps = $params['steps'] ?? array();

			if ( empty( $steps ) ) {
				throw new Exception( __( 'Steps invalid', 'learnpress' ) );
			}

			$step = $params['step'] ?? '';
			if ( empty( $step ) ) {
				throw new Exception( __( 'Step invalid', 'learnpress' ) );
			}

			$data = $params['data'] ?? array();

			/**
			 * @var $response LP_Step
			 */
			$response = $this->call_step( $step, $data );

			// Next step or Finish.
			if ( 'finished' === $response->status ) {
				// Set param to clone table next.
				$index = array_search( $step, $steps, true );
				++ $index;

				if ( ! empty( $steps[ $index ] ) ) {
					$response->status = 'success';
					$response->name   = $steps[ $index ];
					$response->data   = new stdClass();
				} else {
					$response->status = 'finished';
				}
			}
		} catch ( Exception $exception ) {
			$response->message = $exception->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Call Step
	 *
	 * @param string $step .
	 * @param array $data .
	 *
	 * @return false|mixed
	 * @throws Exception .
	 */
	public function call_step( string $step, $data = array() ) {
		$step_function = apply_filters( "lp-handle-steps/$step", array( $this, $step ) );

		if ( is_callable( $step_function ) ) {
			return call_user_func( $step_function, $data );
		}

		throw new Exception( __( 'Not found function', 'learnpress' ) . $step_function );
	}
}
