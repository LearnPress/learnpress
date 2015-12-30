<?php
//add_action( 'wp_head', 'learn_press_head_head' );
function learn_press_head_head(){
	learn_press_debug($_REQUEST, false);
}