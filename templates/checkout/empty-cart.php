<?php
/**
 * Template for displaying notice empty cart form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/empty-cart.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

do_action( 'learn-press/before-empty-cart-message' );

learn_press_display_message( esc_html__( 'Your cart is currently empty.', 'learnpress' ), 'error' );

do_action( 'learn-press/after-empty-cart-message' );
