<?php
abstract class LP_Absatract_Post_Type{
	function __construct(){
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
	}

	abstract function admin_params();
	abstract function admin_scripts();
	abstract function admin_styles();
}