<?php
/**
 * Template for displaying Newsletter button.
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.10
 */

defined( 'ABSPATH' ) or die();
?>
<div class="lp-notice notice notice-warning" id="learn-press-newsletter-button">
    <p>
        <strong><?php echo __( 'If you don\'t want to miss exclussive offers from us, join our newsletter.', 'learnpress' ); ?></strong>
    </p>
    <p>
        <a class="button button-primary lp-button-newsletter" data-dismiss-notice="newsletter-button"
           data-context="newsletter"><?php echo __( 'Sure! I want to get the latest news.', 'learnpress' ); ?></a>
    </p>
    <button class="notice-dismiss" data-dismiss-notice="newsletter-button"></button>
</div>
