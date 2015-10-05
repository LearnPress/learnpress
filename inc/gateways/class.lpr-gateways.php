<?php
class LPR_Gateways{

    protected static $_instance = null;

    protected $payment_gateways = array();

    function __construct(){
        $this->init();
    }

    function init(){
        $gateways = array(
            'paypal' => 'LPR_Gateway_Paypal'
        );
        // Filter
        $gateways = apply_filters( 'learn_press_payment_method', $gateways );
        if( $gateways ){
            foreach( $gateways as $k => $gateway ){
                if( is_string( $gateway ) && false !== strpos( $gateway, 'LPR_Gateway_') && class_exists( $gateway ) ){
                    $gateway = new $gateway();
                }
                $this->payment_gateways[$k] = apply_filters( 'learn_press_payment_method_init', $gateway );
            }
        }
    }

    function get_gateways(){
        $gateways = array();
        if( count( $this->payment_gateways) ) foreach( $this->payment_gateways as $gateway ){
            $gateways[ $gateway->id ] = $gateway;
        }
        return $gateways;
    }

    function get_available_payment_gateways(){
    	$this->init();
        $_available_gateways = array();

        foreach ( $this->payment_gateways as $slug => $gateway ) {

            // let custom addon can define how is enable/disable
            if( apply_filters( 'learn_press_payment_gateway_available_' . $slug, true, $gateway ) ){
                $_available_gateways[$slug] = $gateway;

            };
            continue;
            if ( $gateway->is_available() ) {
                if ( ! is_add_payment_method_page() ) {
                    $_available_gateways[ $gateway->id ] = $gateway;

                } elseif( $gateway->supports( 'add_payment_method' ) ) {
                    $_available_gateways[ $gateway->id ] = $gateway;

                }
            }
        }

        return apply_filters( 'learn_press_available_payment_gateways', $_available_gateways );
    }
    function get_availabe_gateways(){
        return $this->payment_gateways;
    }

    /**
     * Ensure that only one instance of LPR_Gateways is loadded
     * @return LPR_Gateways|null
     */
    static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}


