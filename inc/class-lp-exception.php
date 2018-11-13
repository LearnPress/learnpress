<?php

class LP_Exception extends Exception {
	public function __construct( $message = "", $code = '', Throwable $previous = null ) {
		parent::__construct( $message, (int) $code, $previous );
	}
}