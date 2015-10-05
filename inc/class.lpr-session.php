<?php

/**
 * Class LPR_Session
 */
class LPR_Session{
    /**
     * @var object
     */
    private static $instance;

    /**
     * Constructor
     */
    function __construct(){
        if( self::$instance ) return;
        $this->_init();
    }

    /**
     * Start session if it is not started
     * and init global session used by LearnPress
     *
     * @access private
     * @return array
     */
    private function _init(){
        if( ! session_id() ) {
            session_start();
        }
        if( empty( $_SESSION['learn_press'] ) ) {
            $_SESSION['learn_press'] = array();
        }
        return $_SESSION['learn_press'];
    }

    /**
     * Push new value with a key into session array
     *
     * @param $key
     * @param $value
     * @return mixed
     */
    function set( $key, $value ){
        $_SESSION['learn_press'][$key] = $value;
        return $_SESSION['learn_press'][$key];
    }

    /**
     * Get a value from session array by key
     *
     * @param $key
     * @return mixed
     */
    function get( $key ){
        return ! empty( $_SESSION['learn_press'][$key] ) ? $_SESSION['learn_press'][$key] : false;
    }

    /**
     * Clear a value from session by key
     *
     * @param $key
     */
    function remove( $key ){
        if( isset( $_SESSION['learn_press'][$key] ) ){
            unset( $_SESSION['learn_press'][$key] );
        }
    }

    /**
     * Get unique instance object of the class
     *
     * @return LPR_Session|object
     */
    static function instance(){
        if( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}