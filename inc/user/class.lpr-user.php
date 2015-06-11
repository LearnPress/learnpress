<?php
class LPR_User extends WP_User{
    private static $instances = array();
    function __construct( $id = false ){
        parent::__construct( $id );
    }

    static function instance( $uid ){
        if( empty( self::$instances[$uid] ) ){
            $user = new self( $uid );

            self::$instances[$user->ID] = $user;

            $uid = $user->ID;
        }
        if( !empty( self::$instances[$uid] ) ) return self::$instances[$uid];
    }

}