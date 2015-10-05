<?php

/**
 * Class LPR_Cart
 *
 * Simple Cart object for now. Maybe need to expand later
 */
class LPR_Cart{
    private static $instance = false;
    function __construct(){
        if( self::$instance ) return;

        if( !session_id() ) session_start();
        if( empty( $_SESSION['learn_press_cart'] ) ){
            $_SESSION['learn_press_cart'] = array(
                'cart_id'   => $this->generate_cart_id(),
                'products'  => array()
            );
        }
    }

    function get_cart_id(){
        return $_SESSION['learn_press_cart']['cart_id'];
    }

    function get_products(){
        return $_SESSION['learn_press_cart']['products'];
    }

    function get_sub_total(){
        $sub_total = 0;
        $products = $this->get_products();
        if( $products ) foreach( $products as $product ){
            $sub_total += learn_press_is_free_course( $product['id'] ) ? 0 : floatval( learn_press_get_course_price( $product['id'] ) );
        }
        learn_press_format_price( $sub_total );
        return apply_filters( 'learn_press_get_cart_subtotal', $sub_total, $this->get_cart_id() );
    }

    function get_total(){
        $sub_total  = $this->get_sub_total();
        $total      = $sub_total;
        return apply_filters( 'learn_press_get_cart_total', $total, $this->get_cart_id() );
    }

    function generate_cart_id(){
        return md5( time() );
    }

    function add_to_cart( $course_id ){

        $course = get_post( $course_id );
        $price  = learn_press_get_course_price( $course_id );
        $quantity = 1;

        $_SESSION['learn_press_cart']['products'][$course_id] = array(
            'id'        => $course_id,
            'quantity'  => $quantity,
            'price'     => $price
        );
    }

    function empty_cart(){
        unset( $_SESSION['learn_press_cart']['products'] );
        return $this;
    }

    function destroy(){
        unset( $_SESSION['learn_press_cart'] );
    }
    static function instance( $prop = false, $args = false ){
        if( !self::$instance ){
            self::$instance = new self();
        }
        $ins = self::$instance;
        if( $prop ) {
            $prop = 'get_' . $prop;
        }
        return $prop && is_callable( array( $ins, $prop ) ) ? call_user_func_array( array( $ins, $prop ), (array)$args ) : $ins;
    }
}
if( !is_admin() ) {
    $GLOBALS['learn_press_cart'] = LPR_Cart::instance();
}

function learn_press_get_cart( $prop = null ){
    return LPR_Cart::instance( $prop );
}

function learn_press_get_cart_description(){
    $products = learn_press_get_cart( 'products');
    $description = '';
    if( $products ){
        foreach( $products as $prop ){
            $description .= get_the_title( $prop['id'] );
        }
    }
    return apply_filters( 'learn_press_cart_description', $description );
}

function learn_press_get_cart_course_url(){
    $products = learn_press_get_cart( 'products');
    $return = '';
    if( $products ){
        foreach( $products as $prop ){
            $return = get_permalink( $prop['id'] ); break;
        }
    }
    return apply_filters( 'learn_press_cart_course_url', $return );
}

function learn_press_get_cart_total(){
    return learn_press_get_cart( 'total');
}
//learn_press_get_cart_description();