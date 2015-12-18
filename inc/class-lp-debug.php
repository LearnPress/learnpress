<?php
class LP_Debug{
	static function log(){

	}

	static function exception( $message ){
		if( LP_Settings::instance()->get( 'debug' ) != 'yes' ){
			return;
		}
		throw new Exception( $message );
	}
}