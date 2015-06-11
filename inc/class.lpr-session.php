<?php

class LPR_Session{
    private static $instance;
    function __construct(){
        if( self::$instance ) return;
        $this->init();
    }
    function init(){
        if( !session_id() ) session_start();
        if( empty( $_SESSION['learn_press'] ) ) $_SESSION['learn_press'] = array();
    }

    function set( $key, $value ){
        $_SESSION['learn_press'][$key] = $value;
        return $_SESSION['learn_press'][$key];
    }

    function get( $key ){
        return ! empty( $_SESSION['learn_press'][$key] ) ? $_SESSION['learn_press'][$key] : false;
    }

    function remove( $key ){
        if( isset( $_SESSION['learn_press'][$key] ) ) unset( $_SESSION['learn_press'][$key] );
    }
    static function instance(){
        if( !self::$instance ) self::$instance = new self();
        return self::$instance;
    }
}