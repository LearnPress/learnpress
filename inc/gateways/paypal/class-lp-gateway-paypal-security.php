<?php
class LP_Gateway_Paypal_Security extends LP_Gateway_Paypal{
	public function get_request_url( $order_id ) {
		$user    = learn_press_get_current_user();
		$sandbox = LP()->settings->get( 'paypal_sandbox' ) == 'yes';

		$payment_form = '';

		$paypal_api_url     = $sandbox ? $this->paypal_nvp_api_sandbox_url : $this->paypal_nvp_api_live_url;// PAYPAL_NVP_API_SANDBOX_URL : PAYPAL_NVP_API_LIVE_URL;
		$paypal_payment_url = $sandbox ? $this->paypal_payment_sandbox_url : $this->paypal_payment_sandbox_url;//'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';

		$paypal_email         = $sandbox ? LP()->settings->get( 'paypal_sandbox_email' ) : LP()->settings->get( 'paypal_email' );
		$paypal_api_username  = $sandbox ? LP()->settings->get( 'paypal_sandbox_api_username' ) : LP()->settings->get( 'paypal_api_username' );
		$paypal_api_password  = $sandbox ? LP()->settings->get( 'paypal_sandbox_api_password' ) : LP()->settings->get( 'paypal_api_password' );
		$paypal_api_signature = $sandbox ? LP()->settings->get( 'paypal_sandbox_api_signature' ) : LP()->settings->get( 'paypal_api_signature' );

		if ( !empty( $paypal_email )
			&& !empty( $paypal_api_username )
			&& !empty( $paypal_api_password )
			&& !empty( $paypal_api_signature )
		) {

			remove_filter( 'the_title', 'wptexturize' ); // remove this because it screws up the product titles in PayPal
			$temp_id = learn_press_uniqid();

			$button_request               = array(
				'USER'        => trim( $paypal_api_username ),
				'PWD'         => trim( $paypal_api_password ),
				'SIGNATURE'   => trim( $paypal_api_signature ),
				'VERSION'     => '96.0', //The PayPal API version
				'METHOD'      => 'BMCreateButton',
				'BUTTONCODE'  => 'ENCRYPTED',
				'BUTTONIMAGE' => 'REG',
				'BUYNOWTEXT'  => 'PAYNOW',
			);
			$button_request['BUTTONTYPE'] = 'BUYNOW';
			//$L_BUTTONVARS[]               = 'amount=' . learn_press_get_cart_total();
			//$L_BUTTONVARS[]               = 'quantity=1';
			$nonce                        = wp_create_nonce( 'learn-press-paypal-nonce' );

			$L_BUTTONVARS[] = 'business=' . $paypal_email;
			//$L_BUTTONVARS[] = 'item_name=' . learn_press_get_cart_description();
			$L_BUTTONVARS[] = 'return=' . add_query_arg( array( 'learn-press-transaction-method' => 'paypal-standard-secure', 'paypal-nonce' => $nonce ), learn_press_get_cart_course_url() );
			$L_BUTTONVARS[] = 'currency_code=' . learn_press_get_currency();//$general_settings['default-currency'];
			$L_BUTTONVARS[] = 'notify_url=' . learn_press_get_web_hook( 'paypal-standard-secure' );
			//http://lessbugs.com/paypal/paypal_ipn.php';// . get_site_url() . '/?paypal-stardard-secure=1' ;
			$L_BUTTONVARS[] = 'no_note=1';
			$L_BUTTONVARS[] = 'shipping=0';
			$L_BUTTONVARS[] = 'email=' . $user->user_email;
			$L_BUTTONVARS[] = 'rm=2'; //Return  Method - https://developer.paypal.com/webapps/developer/docs/classic/button-manager/integration-guide/ButtonManagerHTMLVariables/
			$L_BUTTONVARS[] = 'cancel_return=' . learn_press_get_cart_course_url();
			$L_BUTTONVARS[] = 'custom=' . $temp_id;
			$L_BUTTONVARS[] = 'no_shipping=1';

			foreach($this->get_item_lines() as $k => $v){
				$L_BUTTONVARS[] = "{$k}={$v}";
			}
			$L_BUTTONVARS = apply_filters( 'learn_press_paypal_standard_secure_button_vars', $L_BUTTONVARS );
			$count        = 0;
			foreach ( $L_BUTTONVARS as $L_BUTTONVAR ) {
				$button_request['L_BUTTONVAR' . $count] = $L_BUTTONVAR;
				$count ++;
			}

			//print_r($button_request);die();
			$button_request = apply_filters( 'learn_press_paypal_standard_secure_button_request', $button_request );

			$response = wp_remote_post( $paypal_api_url, array( 'body' => $button_request ) );

			if ( !is_wp_error( $response ) ) {
				parse_str( wp_remote_retrieve_body( $response ), $response_array );
				if ( !empty( $response_array['ACK'] ) && 'Success' === $response_array['ACK'] ) {
					if ( !empty( $response_array['WEBSITECODE'] ) )
						$payment_form = str_replace( array( "\r\n", "\r", "\n" ), '', stripslashes( $response_array['WEBSITECODE'] ) );
				}
			} else {
				print_r( $response );
			}

			if ( preg_match( '/-----BEGIN PKCS7-----.*-----END PKCS7-----/i', $payment_form, $matches ) ) {

				$query              = array(
					'cmd'       => '_s-xclick',
					'encrypted' => $matches[0],
				);
				$paypal_payment_url = $paypal_payment_url . '?' . http_build_query( $query );

				return $paypal_payment_url;
			} else {
				echo $payment_form;
			}
		}

		return false;
	}
}