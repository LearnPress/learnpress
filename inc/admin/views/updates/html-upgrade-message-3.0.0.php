<?php
/**
 * Template for displaying message in admin after upgraded LearnPress to version 3.0.0
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();

?>
<div class="notice notice-warning lp-notice lp-upgrade-notice">
    <h4><?php printf( __( 'Welcome to LearnPress %s', 'learnpress' ), LEARNPRESS_VERSION ); ?></h4>
    <p><?php _e( 'This is a <strong>BIG UPDATE</strong> and it allows you to do so much more!', 'learnpress' ); ?></p>
    <p><?php _e( 'If there\'s any issue, please be sure to backup your site, update your theme, contact supporter.', 'learnpres' ); ?></p>
    <p>
        <a class="button"
           href="https://thimpress.com/learnpress-3-0/"
           target="_blank"><?php _e( 'Check what\'s new', 'learnpress' ); ?></a>
        <a class="button" href="https://thimpress.com/help/"
           target="_blank"><?php _e( 'Get support now', 'learnpress' ); ?></a>
    </p>
    <a class="close-notice" href=""><?php _e( 'Got it!', 'learnpress' ); ?></a>
</div>