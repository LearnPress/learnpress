<?php
/**
 *  PHP-PayPal-IPN Example
 *
 *  This shows a basic example of how to use the IpnListener() PHP class to
 *  implement a PayPal Instant Payment Notification (IPN) listener script.
 *
 *  For a more in depth tutorial, see my blog post:
 *  http://www.micahcarrick.com/paypal-ipn-with-php.html
 *
 *  This code is available at github:
 *  https://github.com/Quixotix/PHP-PayPal-IPN
 *
 * @package        PHP-PayPal-IPN
 * @author         Micah Carrick
 * @copyright  (c) 2011 - Micah Carrick
 * @license        http://opensource.org/licenses/gpl-3.0.html
 */


/*
Since this script is executed on the back end between the PayPal server and this
script, you will want to log errors to a file or email. Do not try to use echo
or print--it will not work! 
Here I am turning on PHP error logging to a file called "ipn_errors.log". Make
sure your web server has permissions to write to that file. In a production 
environment it is better to have that log file outside of the web root.
*/
ini_set( 'log_errors', true );
ini_set( 'error_log', dirname( __FILE__ ) . '/ipn_errors.log' );
// instantiate the IpnListener class
ob_start();
    print_r( $_REQUEST );
file_put_contents( 'ipn.txt', ob_get_clean() );
die( );
if ( isset( $_POST ) ) {

return;
	$post_password     = substr( 'lpr_order_' . $_POST['txn_id'], 0, 20 );
	$date              = gmdate( 'Y-m-d H:i:s', ( strtotime( $_POST['payment_date'] ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) );
	$purchased_items   = array();
	$purchased_items[] = array(
		'course_id' => $_POST['item_number'],
		'cost'      => $_POST['mc_gross']
	);
	$order_data        = array(
		'ID'            => $order_id, //Order ID
		'post_author'   => $_POST['custom'], //Buyer ID
		'post_date'     => $date, //Course ID
		'post_type'     => LP_ORDER_CPT,
		'post_password' => $post_password,
		'post_title'    => __( 'Order on ', 'learnpress' ) . ' ' . date( "l jS F Y h:i:s A", strtotime( $date ) )
	);
	$order_meta        = array(
		'lpr_cost'        => $_POST['mc_gross'], //Total price
		'lpr_methods'     => 'paypal', //Payment methods
		'lpr_items'       => $purchased_items,
		'lpr_status'      => 0,
		'lpr_courses'     => $_POST['item_number'],
		'lpr_information' => $_POST
	);

	learn_press_update_order( $order_data, $order_meta, $purchased_items );

}
include( 'ipnlistener.php' );
$listener = new IpnListener();
/*
When you are testing your IPN script you should be using a PayPal "Sandbox"
account: https://developer.paypal.com
When you are ready to go live change use_sandbox to false.
*/
$listener->use_sandbox = true;
/*
By default the IpnListener object is going  going to post the data back to PayPal
using cURL over a secure SSL connection. This is the recommended way to post
the data back, however, some people may have connections problems using this
method. 
To post over standard HTTP connection, use:
$listener->use_ssl = false;
To post using the fsockopen() function rather than cURL, use:
$listener->use_curl = false;
*/
/*
The processIpn() method will encode the POST variables sent by PayPal and then
POST them back to the PayPal server. An exception will be thrown if there is 
a fatal error (cannot connect, your server is not configured properly, etc.).
Use a try/catch block to catch these fatal errors and log to the ipn_errors.log
file we setup at the top of this file.
The processIpn() method will send the raw data on 'php://input' to PayPal. You
can optionally pass the data to processIpn() yourself:
$verified = $listener->processIpn($my_post_data);
*/

try {
	$listener->requirePostMethod();
	$verified = $listener->processIpn();
} catch ( Exception $e ) {
	error_log( $e->getMessage() );
	exit( 0 );
}
/*
The processIpn() method returned true if the IPN was "VERIFIED" and false if it
was "INVALID".
*/
if ( $verified ) {
    return;
	/*
	Once you have a verified IPN you need to do a few more checks on the POST
	fields--typically against data you stored in your database during when the
	end user made a purchase (such as in the "success" page on a web payments
	standard button). The fields PayPal recommends checking are:

		1. Check the $_POST['payment_status'] is "Completed"
		2. Check that $_POST['txn_id'] has not been previously processed
		3. Check that $_POST['receiver_email'] is your Primary PayPal email
		4. Check that $_POST['payment_amount'] and $_POST['payment_currency']
		   are correct

	Since implementations on this varies, I will leave these checks out of this
	example and just send an email using the getTextReport() method to get all
	of the details about the IPN.
	*/
	if ( strtoupper( $_POST['payment_status'] ) == 'COMPLETED' ) {
		global $wpdb;
		$course_id    = $_POST['item_number'];
		$params       = get_option( '_lpr_general_settings', array() );
		$course_price = get_post_meta( $course_id, '_lpr_course_price', true );
		if ( $course_price == $_POST['mc_gross'] && $params['currency'] == $_POST['mc_currency'] ) {
			$user_id = $_POST['custom'];
			learn_press_update_user_course( $user_id, $course_id );
		}
		$purchased_items = array();
		$post_password   = substr( 'lpr_order_' . $_POST['txn_id'], 0, 20 );
		$order_id        = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts
				WHERE post_password=%s",
				$post_password
			)
		);
		if ( $order_id ) {
			learn_press_update_order_status( $order_id, 2 );
		}
	}
} else {
	/*
	An Invalid IPN *may* be caused by a fraudulent transaction attempt. It's
	a good idea to have a developer or sys admin manually investigate any
	invalid IPN.
	*/

}
?>