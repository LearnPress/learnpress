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
<div class="notice notice-warning lp-notice">
    <h4><?php _e( 'Welcome to LearnPress 3.0.0', 'learnpress' ); ?></h4>
    <p><?php _e( 'LearnPress 3.0.0 is a BIG UPDATE with a lots of features to creating, managing and selling your online courses.', 'learnpress' ); ?></p>
    <p><?php _e( 'If you have any issue, please contact our supporters.', 'learnpres' ); ?></p>
    <p>
        <a class="button"
           href="https://thimpress.com/learnpress-3-0/"
           target="_blank"><?php _e( 'Check what\'s new', 'learnpress' ); ?></a>
        <a class="button" href="https://thimpress.com/help/"
           target="_blank"><?php _e( 'Get support now', 'learnpress' ); ?></a>
    </p>
</div>