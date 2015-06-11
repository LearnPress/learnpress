<?php
class LPR_Gateway_Abstract{
    public $title        = null;
    public $description  = null;
    public $id           = null;

    function __construct(){

        if( !$this->title ){
            $this->title = preg_replace('!LPR_Gateway_!', '', get_class( $this ) );
        }
        if( !$this->id ){
            $this->id = sanitize_title( $this->title );
        }
    }

    function process_payment( $order ){
        return array();
    }

    public function get_title() {
        return apply_filters( 'learn_press_gateway_title', $this->title, $this->id );
    }

    public function get_description() {
        return apply_filters( 'learn_press_gateway_description', $this->description, $this->id );
    }

    public function get_icon() {

        $icon = $this->icon ? '<img src="' . WC_HTTPS::force_https_url( $this->icon ) . '" alt="' . esc_attr( $this->get_title() ) . '" />' : '';

        return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
    }
}