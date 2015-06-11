<?php
class LPR_Gateway_Paypal extends LPR_Gateway_Abstract{
    protected $paypal_live_url              = null;
    protected $paypal_sandbox_url           = null;
    protected $paypal_payment_live_url      = null;
    protected $paypal_payment_sandbox_url   = null;
    protected $paypal_nvp_api_live_url      = null;
    protected $paypal_vnp_api_sandbox_url   = null;

    static protected $loaded                = false;
    protected $method                       = '';

    function __construct(){
        if( self::$loaded ) return;
        $this->paypal_live_url              = 'https://www.paypal.com/';
        $this->paypal_sandbox_url           = 'https://www.sandbox.paypal.com/';
        $this->paypal_payment_live_url      = 'https://www.paypal.com/cgi-bin/webscr';
        $this->paypal_payment_sandbox_url   = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
        $this->paypal_nvp_api_live_url      = 'https://api-3t.paypal.com/nvp';
        $this->paypal_nvp_api_sandbox_url   = 'https://api-3t.sandbox.paypal.com/nvp';

        $settings = learn_press_settings( 'payment' );

        $this->method = !empty( $_REQUEST['learn-press-transaction-method'] ) ? $_REQUEST['learn-press-transaction-method'] : '';

        if( $settings->get('paypal.enable') ) {

            add_action( 'init', array( $this, 'register_web_hook' ) );
            add_action('learn_press_take_course_paypal', array($this, 'process_payment') );
            add_action('learn_press_do_transaction_paypal-standard-secure', array($this, 'process_order_paypal_standard_secure'));
            add_action('learn_press_do_transaction_paypal-standard', array($this, 'process_order_paypal_standard'));
            //add_action('learn_press_update_order_status', array($this, 'remove_transient'), 5, 2);
            //add_action('learn_press_payment_gateway_form_paypal', array($this, 'payment_form'));

            add_action( 'learn_press_web_hook_learn_press_paypal-standard', array( $this, 'web_hook_process_paypal_standard' ) );
            add_action( 'learn_press_web_hook_learn_press_paypal-standard-secure', array( $this, 'web_hook_process_paypal_standard_secure' ) );
            add_action('init', array($this, 'parse_ipn'));
            add_filter( 'learn_press_payment_method_from_slug_paypal-standard-secure', array( $this, 'payment_method_name' ) );
            add_filter( 'learn_press_payment_method_from_slug_paypal-standard', array( $this, 'payment_method_name' ) );

        }



        if( is_admin() ){
            ob_start();
            ?>
            <script>
                $('#learn_press_paypal_enable').change(function(){
                    var $rows = $(this).closest('tr').siblings('tr');
                    if( this.checked ){
                        $rows.css("display", "");
                    }else{
                        $rows.css("display", "none");
                    }
                }).trigger('change')
            </script>
            <?php
            $script = ob_get_clean();
            $script = preg_replace( '!</?script>!', '', $script );
            learn_press_enqueue_script( $script );
        }
        add_filter( 'learn_press_payment_gateway_available_paypal', array( $this, 'paypal_available' ), 10, 2 );

        parent::__construct();
        self::$loaded = true;
    }

    function register_web_hook(){
        learn_press_register_web_hook( 'paypal-standard', 'learn_press_paypal-standard' );
        learn_press_register_web_hook( 'paypal-standard-secure', 'learn_press_paypal-standard-secure' );
    }

    function web_hook_process_paypal_standard( $request ){


        $payload['cmd'] = '_notify-validate';
        foreach( $_POST as $key => $value ) {
            $payload[$key] = stripslashes( $value );
        }
        $paypal_api_url = !empty( $_REQUEST['test_ipn'] ) ? $this->paypal_payment_sandbox_url : $this->paypal_payment_live_url;
        $response = wp_remote_post( $paypal_api_url, array( 'body' => $payload ) );
        $body = wp_remote_retrieve_body( $response );

        if ( 'VERIFIED' === $body ) {
            if (!empty($request['txn_type'])) {
                if (!empty($request['transaction_subject']) && $transient_data = learn_press_get_transient_transaction('lpps', $request['transaction_subject'])) {
                    learn_press_delete_transient_transaction('lpps', $request['transaction_subject']);
                    return learn_press_add_transaction(
                        array(
                            'method'    => 'paypal-standard',
                            'method_id' => $request['txn_id'],
                            'status'    => $request['payment_status'],
                            'user_id'   => $transient_data['user_id'],
                            'transaction_object' => $transient_data['transaction_object']
                        )
                    );
                }
                switch ($request['txn_type']) {
                    case 'web_accept':
                        switch (strtolower($request['payment_status'])) {
                            case 'completed' :
                                learn_press_update_order_status( $this->get_order_id( $request['txn_id'] ), $request['payment_status']);
                                break;
                        }
                        break;
                }
            }
        }
    }

    function web_hook_process_paypal_standard_secure( $request ){
        $payload['cmd'] = '_notify-validate';
        foreach( $_POST as $key => $value ) {
            $payload[$key] = stripslashes( $value );
        }
        $paypal_api_url = !empty( $_REQUEST['test_ipn'] ) ? $this->paypal_payment_sandbox_url : $this->paypal_payment_live_url;
        $response = wp_remote_post( $paypal_api_url, array( 'body' => $payload ) );
        $body = wp_remote_retrieve_body( $response );

        if ( 'VERIFIED' === $body ) {

            if (!empty($request['txn_type'])) {

                if (!empty($request['transaction_subject']) && $transient_data = learn_press_get_transient_transaction('lppss', $request['transaction_subject'])) {
                    learn_press_delete_transient_transaction('lppss', $request['transaction_subject']);
                    return learn_press_add_transaction(
                        array(
                            'method' => 'paypal-standard-secure',
                            'method_id' => $request['txn_id'],
                            'status' => $request['payment_status'],
                            'user_id' => $transient_data['user_id'],
                            'transaction_object' => $transient_data['transaction_object']
                        )
                    );
                }

                switch ($request['txn_type']) {
                    case 'web_accept':
                        switch (strtolower($request['payment_status'])) {
                            case 'completed':
                                learn_press_update_order_status($request['txn_id'], $request['payment_status']);
                                break;
                        }
                        break;
                }
            }
        }
    }

    function payment_method_name( $slug ){
        return $slug == 'paypal-standard-secure' ? 'Paypal Standard - Secure' : ( 'paypal-standard' == $slug ? 'Paypal Standard - Basic' : $slug );
    }

    function paypal_available( $a, $b ){
        return LPR_Settings::instance('payment')->get('paypal.enable');
    }



    /**
     * Retrieve order by paypal txn_id
     *
     * @param $txn_id
     * @return int
     */
    function get_order_id( $txn_id ){

        $args = array(
            'meta_key'    => '_learn_press_transaction_method_id',
            'meta_value'  => $txn_id,
            'numberposts' => 1, //we should only have one, so limit to 1
        );

        $orders = learn_press_get_orders( $args );
        print_r($orders);
        if( $orders ) foreach( $orders as $order ){
            return $order->ID;
        }
        return 0;
    }

    function parse_ipn(){
        if ( !isset( $_REQUEST['ipn'] ) ) {
            return;
        }
        require_once( 'paypal-ipn/ipn.php' );
    }

    function payment_form(){
    ?>
        Pay with Paypal
    <?php
    }


    function process_order_paypal_standard_secure()
    {
        if( !empty( $_REQUEST['learn-press-transaction-method'] ) && ( 'paypal-standard-secure' == $_REQUEST['learn-press-transaction-method'] ) ) {
            // if we have a paypal-nonce in $_REQUEST that meaning user has clicked go back to our site after finished the transaction
            // so, create a new order

            if( !empty( $_REQUEST['paypal-nonce'] ) && wp_verify_nonce( $_REQUEST['paypal-nonce'], 'learn-press-paypal-nonce' )  ) {
                if (!empty($_REQUEST['tx'])) //if PDT is enabled
                    $transaction_id = $_REQUEST['tx'];
                else if (!empty($_REQUEST['txn_id'])) //if PDT is not enabled
                    $transaction_id = $_REQUEST['txn_id'];
                else
                    $transaction_id = NULL;

                if (!empty($_REQUEST['cm']))
                    $transient_transaction_id = $_REQUEST['cm'];
                else if (!empty($_REQUEST['custom']))
                    $transient_transaction_id = $_REQUEST['custom'];
                else
                    $transient_transaction_id = NULL;

                if ( !empty( $_REQUEST['st'] ) ) //if PDT is enabled
                    $transaction_status = $_REQUEST['st'];
                else if ( !empty( $_REQUEST['payment_status'] ) ) //if PDT is not enabled
                    $transaction_status = $_REQUEST['payment_status'];
                else
                    $transaction_status = NULL;

                $settings = get_option( '_lpr_settings_payment' );
                if( empty( $settings['paypal'] ) ) return;
                $paypal_settings = $settings['paypal'];

                $user = learn_press_get_current_user();

                if (!empty($transaction_id) && !empty($transient_transaction_id) && !empty($transaction_status)) {

                    try {

                        $paypal_api_url = ($paypal_settings['sandbox']) ? $this->paypal_nvp_api_sandbox_url : $this->paypal_nvp_api_live_url;
                        $paypal_api_username = ($paypal_settings['sandbox']) ? $paypal_settings['paypal_sandbox_api_username'] : $paypal_settings['paypal_api_username'];
                        $paypal_api_password = ($paypal_settings['sandbox']) ? $paypal_settings['paypal_sandbox_api_password'] : $paypal_settings['paypal_api_password'];
                        $paypal_api_signature = ($paypal_settings['sandbox']) ? $paypal_settings['paypal_sandbox_api_signature'] : $paypal_settings['paypal_api_signature'];

                        $request = array(
                            'USER' => trim($paypal_api_username),
                            'PWD' => trim($paypal_api_password),
                            'SIGNATURE' => trim($paypal_api_signature),
                            'VERSION' => '96.0', //The PayPal API version
                            'METHOD' => 'GetTransactionDetails',
                            'TRANSACTIONID' => $transaction_id,
                        );

                        $response = wp_remote_post($paypal_api_url, array('body' => $request));

                        if (!is_wp_error($response)) {

                            $array = array();
                            parse_str(wp_remote_retrieve_body($response), $response_array);

                            $transaction_status = $response_array['PAYMENTSTATUS'];

                            if ($transaction_id != $response_array['TRANSACTIONID'])
                                throw new Exception(__('Error: Transaction IDs do not match! %s, %s', 'learn_press'));

                            if ($transaction_object = learn_press_get_transient_transaction('lppss', $transient_transaction_id)) {
                                //If the transient still exists, delete it and add the official transaction

                                learn_press_delete_transient_transaction('lppss', $transient_transaction_id);
                                $order_id = $this->get_order_id( $transaction_id );
                                $order_id = learn_press_add_transaction(
                                    array(
                                        'order_id'  => $order_id,
                                        'method'    => 'paypal-standard-secure',
                                        'method_id' => $transaction_id,
                                        'status'    => $transaction_status,
                                        'user_id'   => $user->ID,
                                        'transaction_object' => $transaction_object['transaction_object']
                                    )
                                );
                                wp_redirect( ( $confirm_page_id = learn_press_get_page_id( 'taken_course_confirm' ) ) && get_post( $confirm_page_id ) ? learn_press_get_order_confirm_url( $order_id ) : get_site_url()  );
                                die();
                            }

                        } else {
                            throw new Exception($response->get_error_message());
                        }

                    } catch (Exception $e) {

                    }
                    wp_redirect( get_site_url() );
                    die();
                }
            }
        }
    }
    function process_order_paypal_standard(){

        if( !empty( $_REQUEST['learn-press-transaction-method'] ) && ( 'paypal-standard' == $_REQUEST['learn-press-transaction-method'] ) ) {
            // if we have a paypal-nonce in $_REQUEST that meaning user has clicked go back to our site after finished the transaction
            // so, create a new order
            if( !empty( $_REQUEST['paypal-nonce'] ) && wp_verify_nonce( $_REQUEST['paypal-nonce'], 'learn-press-paypal-nonce' )  ) {
                if ( !empty( $_REQUEST['tx'] ) ) //if PDT is enabled
                    $transaction_id = $_REQUEST['tx'];
                else if ( !empty( $_REQUEST['txn_id'] ) ) //if PDT is not enabled
                    $transaction_id = $_REQUEST['txn_id'];
                else
                    $transaction_id = NULL;

                if ( !empty( $_REQUEST['cm'] ) )
                    $transient_transaction_id = $_REQUEST['cm'];
                else if ( !empty( $_REQUEST['custom'] ) )
                    $transient_transaction_id = $_REQUEST['custom'];
                else
                    $transient_transaction_id = NULL;

                if ( !empty( $_REQUEST['st'] ) ) //if PDT is enabled
                    $transaction_status = $_REQUEST['st'];
                else if ( !empty( $_REQUEST['payment_status'] ) ) //if PDT is not enabled
                    $transaction_status = $_REQUEST['payment_status'];
                else
                    $transaction_status = NULL;


                if ( !empty( $transaction_id ) && !empty( $transient_transaction_id ) && !empty( $transaction_status ) ) {
                    $user = learn_press_get_current_user();


                    try {
                        //If the transient still exists, delete it and add the official transaction
                        if ( $transaction_object = learn_press_get_transient_transaction( 'lpps', $transient_transaction_id ) ) {

                            learn_press_delete_transient_transaction( 'lpps', $transient_transaction_id  );
                            $order_id = $this->get_order_id( $transaction_id );
                            $order_id = learn_press_add_transaction(
                                array(
                                    'order_id'  => $order_id,
                                    'method'    => 'paypal-standard',
                                    'method_id' => $transaction_id,
                                    'status'    => $transaction_status,
                                    'user_id'   => $user->ID,
                                    'transaction_object' => $transaction_object['transaction_object']
                                )
                            );

                            wp_redirect( ( $confirm_page_id = learn_press_get_page_id( 'taken_course_confirm' ) ) && get_post( $confirm_page_id ) ? learn_press_get_order_confirm_url( $order_id ) : get_site_url()  );
                            die();
                        }

                    }
                    catch ( Exception $e ) {
                        return false;

                    }

                } else if ( is_null( $transaction_id ) && is_null( $transient_transaction_id ) && is_null( $transaction_status ) ) {
                }
            }
        }

        wp_redirect( get_site_url() );
        die();
    }

    function update_order_status( $method_id, $status ){

        $orders = $this->get_order_id( $method_id );
        if( $orders ) foreach( $orders as $order ) { //really only one
            learn_press_update_order_status( $order->ID, $status );
            return true;
        }
        return false;
    }

    function process_payment( $order ){
        $settings = learn_press_settings('payment');
        $redirect = $settings->get( 'paypal.type' ) == 'basic' ? $this->get_paypal_basic_request_url( $order ) : $this->get_request_url( $order );
        $json = array(
            'result'    => $redirect ? 'success' : 'fail',
            'redirect'  => $redirect
        );

        return $json;
    }

    function get_request_url( $order ){

        $settings = get_option( '_lpr_settings_payment' );
        if( empty( $settings['paypal'] ) ) return;
        $paypal_settings = $settings['paypal'];

        $user = learn_press_get_current_user();

        $payment_form = '';

        $paypal_api_url       = ( $paypal_settings['sandbox'] ) ? $this->paypal_nvp_api_sandbox_url : $this->paypal_nvp_api_live_url;// PAYPAL_NVP_API_SANDBOX_URL : PAYPAL_NVP_API_LIVE_URL;
        $paypal_payment_url   = ( $paypal_settings['sandbox'] ) ? $this->paypal_payment_sandbox_url : $this->paypal_payment_sandbox_url;//'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';

        $paypal_email         = ( $paypal_settings['sandbox'] ) ? $paypal_settings['paypal_sandbox_email']          : $paypal_settings['paypal_email'];
        $paypal_api_username  = ( $paypal_settings['sandbox'] ) ? $paypal_settings['paypal_sandbox_api_username']   : $paypal_settings['paypal_api_username'];
        $paypal_api_password  = ( $paypal_settings['sandbox'] ) ? $paypal_settings['paypal_sandbox_api_password']   : $paypal_settings['paypal_api_password'];
        $paypal_api_signature = ( $paypal_settings['sandbox'] ) ? $paypal_settings['paypal_sandbox_api_signature']  : $paypal_settings['paypal_api_signature'];

        if ( ! empty( $paypal_email )
            && ! empty( $paypal_api_username )
            && ! empty( $paypal_api_password )
            && ! empty( $paypal_api_signature ) ) {

            $subscription = false;


            remove_filter( 'the_title', 'wptexturize' ); // remove this because it screws up the product titles in PayPal

            $transaction    = learn_press_generate_transaction_object();
            $temp_id        = learn_press_uniqid();

            learn_press_set_transient_transaction( 'lppss', $temp_id, $user->ID, $transaction );


            $button_request = array(
                'USER'           => trim( $paypal_api_username ),
                'PWD'            => trim( $paypal_api_password ),
                'SIGNATURE'      => trim( $paypal_api_signature ),
                'VERSION'        => '96.0', //The PayPal API version
                'METHOD'         => 'BMCreateButton',
                'BUTTONCODE'     => 'ENCRYPTED',
                'BUTTONIMAGE'    => 'REG',
                'BUYNOWTEXT'     => 'PAYNOW',
            );
            $button_request['BUTTONTYPE'] = 'BUYNOW';
            $L_BUTTONVARS[] = 'amount=' . learn_press_get_cart_total();
            $L_BUTTONVARS[] = 'quantity=1';
            $nonce = wp_create_nonce( 'learn-press-paypal-nonce' );

            $L_BUTTONVARS[] = 'business=' . $paypal_email;
            $L_BUTTONVARS[] = 'item_name=' . learn_press_get_cart_description();
            $L_BUTTONVARS[] = 'return=' . add_query_arg( array( 'learn-press-transaction-method' => 'paypal-standard-secure', 'paypal-nonce' => $nonce ), learn_press_get_cart_course_url() );
            $L_BUTTONVARS[] = 'currency_code=' . learn_press_get_currency();//$general_settings['default-currency'];
            $L_BUTTONVARS[] = 'notify_url=' . learn_press_get_web_hook('paypal-standard-secure');
            //http://lessbugs.com/paypal/paypal_ipn.php';// . get_site_url() . '/?paypal-stardard-secure=1' ;
            $L_BUTTONVARS[] = 'no_note=1';
            $L_BUTTONVARS[] = 'shipping=0';
            $L_BUTTONVARS[] = 'email=' . $user->user_email;
            $L_BUTTONVARS[] = 'rm=2'; //Return  Method - https://developer.paypal.com/webapps/developer/docs/classic/button-manager/integration-guide/ButtonManagerHTMLVariables/
            $L_BUTTONVARS[] = 'cancel_return=' . learn_press_get_cart_course_url();
            $L_BUTTONVARS[] = 'custom=' . $temp_id;
            $L_BUTTONVARS[] = 'no_shipping=1';

            $L_BUTTONVARS = apply_filters( 'learn_press_paypal_standard_secure_button_vars', $L_BUTTONVARS );
            $count = 0;
            foreach( $L_BUTTONVARS as $L_BUTTONVAR ) {
                $button_request['L_BUTTONVAR' . $count] = $L_BUTTONVAR;
                $count++;
            }


            $button_request = apply_filters( 'learn_press_paypal_standard_secure_button_request', $button_request );

            $response = wp_remote_post( $paypal_api_url, array( 'body' => $button_request ) );

            if ( !is_wp_error( $response ) ) {
                parse_str( wp_remote_retrieve_body( $response ), $response_array );
                if ( !empty( $response_array['ACK'] ) && 'Success' === $response_array['ACK'] ) {
                    if ( !empty( $response_array['WEBSITECODE'] ) )
                        $payment_form = str_replace( array( "\r\n", "\r", "\n" ), '', stripslashes( $response_array['WEBSITECODE'] ) );
                }
            }else{
                print_r( $response );
            }

            if ( preg_match( '/-----BEGIN PKCS7-----.*-----END PKCS7-----/i', $payment_form, $matches ) ) {

                $query = array(
                    'cmd'       => '_s-xclick',
                    'encrypted' => $matches[0],
                );
                $paypal_payment_url = $paypal_payment_url . '?' .  http_build_query( $query );

                return $paypal_payment_url;
            }else{
                echo $payment_form;
            }
        }

        return false;

    }

    function get_paypal_basic_request_url( $order ){
        $settings = get_option( '_lpr_settings_payment' );
        if( empty( $settings['paypal'] ) ) return;
        $paypal_settings = $settings['paypal'];

        $user = learn_press_get_current_user();

        $paypal_args = array (
            'cmd'      => '_xclick',
            'amount'   => learn_press_get_cart_total(),
            'quantity' => '1',
        );

        $transaction    = learn_press_generate_transaction_object();
        $temp_id        = learn_press_uniqid();
        $xxx = @file_get_contents(LPR_PLUGIN_PATH . '/temp.txt');
        learn_press_set_transient_transaction( 'lpps', $temp_id, $user->ID, $transaction );
        file_put_contents(LPR_PLUGIN_PATH . '/temp.txt', $xxx . "=" . $temp_id);

        /*$order_id = LearnPress()->session->get( 'learn_press_user_order' );

        $order_id = learn_press_add_transaction(
            array(
                'method'        => $this->method,
                'method_id'     => '',
                'status'        => '',
                'user_id'       => null,
                'order_id'      => $order_id,
                'parent'        => 0,
                'transaction_object' => $transaction
            )
        );

        //LearnPress()->session->set( 'learn_press_user_order', $order_id );

        $order = new LPR_Order( $order_id );*/

        $nonce = wp_create_nonce( 'learn-press-paypal-nonce' );
        $paypal_email = $paypal_settings['sandbox'] ? $paypal_settings['paypal_sandbox_email'] : $paypal_settings['paypal_email'];
        $query = array(
            'business'      => $paypal_email,
            'item_name'     => learn_press_get_cart_description(),
            'return'        => add_query_arg( array( 'learn-press-transaction-method' => 'paypal-standard', 'paypal-nonce' => $nonce ), learn_press_get_cart_course_url() ),
            'currency_code' => learn_press_get_currency(),
            'notify_url'    => get_site_url() . '/?' . learn_press_get_web_hook( 'paypal-standard' ) . '=1',//get_site_url() . '/?learn-press-transaction-method=paypal-standard',
            'no_note'       => '1',
            'shipping'      => '0',
            'email'         => $user->user_email,
            'rm'            => '2',
            'cancel_return' => learn_press_get_cart_course_url(),
            'custom'        => $temp_id,
            'no_shipping'   => '1'
        );

        $query = array_merge( $paypal_args, $query );
        $query = apply_filters( 'it_exchange_paypal_standard_query', $query );

        $paypal_payment_url = ( $paypal_settings['sandbox'] ? $this->paypal_payment_sandbox_url : $this->paypal_payment_live_url ) . '?' .  http_build_query( $query );

        return $paypal_payment_url;
    }

    function __toString(){
        return 'Paypal';
    }
}