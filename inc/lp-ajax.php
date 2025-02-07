<?php
/**
 * LP Ajax Process Execution
 *
 * @since 4.2.7.6
 */
define( 'LP_AJAX', true );

/** Load WordPress Bootstrap */
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

/** Allow for cross-domain requests (from the front end). */
send_origin_headers();

header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
header( 'X-Robots-Tag: noindex' );

send_nosniff_header();
nocache_headers();
