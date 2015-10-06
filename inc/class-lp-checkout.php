<?php

/**
 * Class LP_Checkout
 */
class LP_Checkout{

    /**
     * @var LP_Checkout object instance
     * @access protected
     */
    static protected $_instance = null;

    /**
     * Payment method
     *
     * @var string
     */
    public $payment_method = '';

    /**
     * Constructor
     */
    function __construct(){
        //
    }

    /**
     * Creates temp new order if needed
     *
     * @return mixed|WP_Error
     * @throws Exception
     */
    function create_order(){

        $transaction_object = hb_generate_transaction_object( );
        if( ! $transaction_object ){
            throw new Exception( sprintf( __( 'Sorry, your session has expired. <a href="%s">Return to homepage</a>', 'tp-hotel-booking' ), home_url() ) );
        }
        // Insert or update the post data
        $booking_id = absint( get_transient( 'booking_awaiting_payment' ) );
        // Resume the unpaid order if its pending
        if ( $booking_id > 0 && ( $booking = HB_Booking::instance( $booking_id ) ) && $booking->post->ID && $booking->has_status( array( 'pending', 'failed' ) ) ) {
            $booking_data['ID'] = $booking_id;
            $booking_data['post_content'] = hb_get_request( 'addition_information' );
            $booking->set_booking_info( $booking_data );
        } else {
            $booking_id = hb_create_booking( );
            $booking = HB_Booking::instance( $booking_id );
        }
        $check_in               = $transaction_object->check_in_date;
        $check_out              = $transaction_object->check_out_date;
        $tax                    = $transaction_object->tax;
        $price_including_tax    = $transaction_object->price_including_tax;
        $rooms                  = $transaction_object->rooms;

        // booking meta data
        $booking_info = array(
            '_hb_check_in_date'         => strtotime( $check_in ),
            '_hb_check_out_date'        => strtotime( $check_out ),
            '_hb_total_nights'          => $transaction_object->total_nights,
            '_hb_tax'                   => $tax,
            '_hb_price_including_tax'   => $price_including_tax ? 1 : 0,
            '_hb_sub_total'             => $transaction_object->sub_total,
            '_hb_total'                 => $transaction_object->total,
            '_hb_advance_payment'       => $transaction_object->advance_payment,
            '_hb_currency'              => $transaction_object->currency,
            '_hb_customer_id'           => $customer_id,
            '_hb_method'                => $this->payment_method->slug,
            '_hb_method_title'          => $this->payment_method->title,
            '_hb_method_id'             => $this->payment_method->method_id
        );
        if( ! empty( $transaction_object->coupon ) ){
            $booking_info['_hb_coupon'] = $transaction_object->coupon;
        }
        $booking->set_booking_info(
            $booking_info
        );

        $booking_id = $booking->update();
        if( $booking_id ){
            $prices = array();
            delete_post_meta( $booking_id, '_hb_room_id' );
            if( $rooms ) foreach( $rooms as $room_options ){
                $num_of_rooms = $room_options['quantity'];
                // insert multiple meta value
                for( $i = 0; $i < $num_of_rooms; $i ++ ) {
                    add_post_meta( $booking_id, '_hb_room_id', $room_options['id'] );
                }
                $room = HB_Room::instance( $room_options['id'] );
                $room->set_data(
                    array(
                        'num_of_rooms'      => $num_of_rooms,
                        'check_in_date'     => $check_in,
                        'check_out_date'    => $check_out
                    )
                );
                $prices[ $room_options['id'] ] = $room->get_total( $check_in, $check_out, $num_of_rooms, false );
            }

            add_post_meta( $booking_id, '_hb_room_price', $prices );
        }
        do_action( 'hb_new_booking', $booking_id );
        return $booking_id;
    }

    /**
     * Process checkout
     *
     * @throws Exception
     */
    function process_checkout(){

        if( strtolower( $_SERVER['REQUEST_METHOD'] ) != 'post' ){
            return;
        }

        /*if ( ! isset( $_POST['hb_customer_place_order_field'] ) || ! wp_verify_nonce( $_POST['hb_customer_place_order_field'], 'hb_customer_place_order' ) ){
            return;
        }*/

        $payment_method = hb_get_user_payment_method( hb_get_request( 'hb-payment-method' ) );

        if( ! $payment_method ){
            throw new Exception( __( 'The payment method is not available', 'tp-hotel-booking' ) );
        }

        $customer_id = $this->create_customer();
        $this->payment_method = $payment_method;
        if( $customer_id  ) {
            $booking_id = $this->create_booking();
            if( $booking_id ) {
                if (HB_Cart::instance()->needs_payment()) {
                    set_transient('booking_awaiting_payment', $booking_id, HOUR_IN_SECONDS);
                    $result = $payment_method->process_checkout( $booking_id );
                } else {
                    if (empty($booking)) {
                        $booking = HB_Booking::instance($booking_id);
                    }
                    // No payment was required for order
                    $booking->payment_complete();
                    HB_Cart::instance()->empty_cart();
                    $return_url = $booking->get_checkout_booking_received_url();
                    hb_send_json( array(
                        'result' 	=> 'success',
                        'redirect'  => apply_filters( 'hb_checkout_no_payment_needed_redirect', $return_url, $booking )
                    ) );
                }
            }else{
                die( 'can not create booking' );
            }
        }

        if ( ! empty( $result['result'] ) && $result['result'] == 'success' ) {

            $result = apply_filters( 'hb_payment_successful_result', $result );

            do_action( 'hb_place_order', $result );
            if ( hb_is_ajax() ) {
                hb_send_json( $result );
                exit;
            } else {
                wp_redirect( $result['redirect'] );
                exit;
            }

        }
    }

    /**
     * Get unique instance for this object
     *
     * @return HB_Checkout
     */
    static function instance(){
        if( empty( self::$_instance ) ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}