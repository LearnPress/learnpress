<?php

$url           = 'http://localhost/foobla/learnpress/dev/?learn_press_paypal=1';
$fields_string = 'mc_gross=10.00&protection_eligibility=Ineligible&address_status=confirmed&item_number1=&payer_id=JZH37HUFZZX2E&tax=0.00&address_street=1 Main St&payment_date=18:55:29 Jan 08, 2017 PST&payment_status=Completed&charset=windows-1252&address_zip=95131&mc_shipping=0.00&mc_handling=0.00&first_name=Test&mc_fee=0.59&address_country_code=US&address_name=Test Buyer&notify_version=3.8&custom={"order_id":2062,"order_key":"order5872fb662254f"}&payer_status=verified&business=tunnhn-facilitator@gmail.com&address_country=United States&num_cart_items=1&mc_handling1=0.00&address_city=San Jose&verify_sign=A2S1fniRGsoquzRDbs4f5rc383f8ABiFW26JrgUjHbl3YdrZbMyqUvqw&payer_email=tunnhn-buyer@gmail.com&mc_shipping1=0.00&tax1=0.00&txn_id=6VJ20259T0902784E&payment_type=instant&last_name=Buyer&address_state=CA&item_name1=What is co-instructor&receiver_email=tunnhn-facilitator@gmail.com&payment_fee=0.59&quantity1=1&receiver_id=9QLNG2KT79DZ4&pending_reason=paymentreview&txn_type=cart&mc_gross_1=10.00&mc_currency=USD&residence_country=US&test_ipn=1&transaction_subject=&payment_gross=10.00&ipn_track_id=aa2857529c827';
$fields        = explode( '&', $fields_string );
$ch            = curl_init();

//set the url, number of POST vars, POST data
curl_setopt( $ch, CURLOPT_URL, $url );
curl_setopt( $ch, CURLOPT_POST, count( $fields ) );
curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );

//execute post
$result = curl_exec( $ch );

//close connection
curl_close( $ch );


