<?php
/**
 * Template for displaying order details tab in user profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/tabs/order-details.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 * @deprecated 4.2.6.4
 */
return;

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$profile = LP_Profile::instance();

do_action( 'learn-press/profile/order-details' );
