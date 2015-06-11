<?php
/**
 * LPR_Order class
 */
class LPR_Order{
    public $id       = 0;
    public $post     = null;
    function __construct( $id ){
        $this->init( $id );
    }

    protected function init( $order ) {
        if ( is_numeric( $order ) ) {
            $this->get_order( $order );
        } elseif ( $order instanceof LPR_Order ) {
            $this->get_order( $order->id );
        } elseif ( isset( $order->ID ) ) {
            $this->get_order( $order->ID );
        }
    }

    function get_order( $id ){
        $orders = get_posts(
            array(
                'post_type'     => 'lpr_order',
                'include'       => array( $id ),
                'post_status'   => 'any'
            )
        );
        if( $orders ){
            $this->post = array_shift( $orders );
            $this->id = $id;
            return $this->id;
        }
        return false;
    }

    function get_items(){
        if( !$this->post ) return false;
        return learn_press_get_order_items( $this->post->ID );
    }

    function get_user( $field = '' ){

        $user = LPR_User::instance( $this->user_id );
        if( $field ) {
            switch ($field) {
                case 'email':
                    return $user->user_email;
                default:
                    if( isset( $user->{$field} ) ) {
                        return $user->{$field};
                    }
            }
        }
        return $user;
    }

    public function __get( $key ) {
        if( $key == 'user_id'){
            $key = 'customer_id';
        }
        // Get values or default if not set
        $value = get_post_meta( $this->id, '_learn_press_' . $key, true );
        return $value;
    }

    function has_status( $status ){
        return false;
    }

    function get_checkout_payment_url(){

    }

    function get_order_number(){
        return learn_press_transaction_order_number( $this->post->ID );
    }

    function get_formatted_order_total(){
        $order_items = learn_press_get_order_items( $this->post->ID );
        $currency_symbol = learn_press_get_currency_symbol( $order_items->currency );
        return learn_press_format_price( $order_items->sub_total, $currency_symbol );
    }

    function get_payment_method_title(){
        return learn_press_payment_method_from_slug( $this->post->ID );
    }

    function get_status(){
        return $this->transaction_status;
    }
}