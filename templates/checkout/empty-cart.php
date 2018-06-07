<?php
/**
 * Template for displaying notice empty cart form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/empty-cart.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

do_action( 'learn-press/before-empty-cart-message' );

learn_press_display_message( __( 'Your cart is currently empty.', 'learnpress' ), 'error' );

do_action( 'learn-press/after-empty-cart-message' );
