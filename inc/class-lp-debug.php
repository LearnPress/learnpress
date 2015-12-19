<?php
class LP_Debug{
	static function log(){
		if( LP_Settings::instance()->get( 'debug' ) != 'yes' ){
			return;
		}
		if( $args = func_get_args() ){
			foreach( $args as $arg ){
				learn_press_debug( $arg );
			}
		}
	}

	static function exception( $message ){
		if( LP_Settings::instance()->get( 'debug' ) != 'yes' ){
			return;
		}
		throw new Exception( $message );
	}
}