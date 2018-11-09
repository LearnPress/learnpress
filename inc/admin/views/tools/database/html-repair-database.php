<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();
?>

<div class="card">
    <h2><?php _e( 'Repair Database', 'learnpress' ); ?></h2>
    <p><?php _e( 'Remove unwanted data and re-calculate relationship.', 'learnpress' ); ?></p>
    <ul id="learn-press-syncs">
<!--        <li>-->
<!--            <label>-->
<!--                <input type="checkbox" name="lp-repair[sync-remove-older-data]" value="yes">-->
<!--			    --><?php //esc_html_e('Remove older meta data such as: post meta, ...', 'learnpress');?>
<!--            </label>-->
<!--        </li>-->
        <li>
            <label>
                <input type="checkbox" name="lp-repair[sync-course-orders]" value="yes">
			    <?php esc_html_e('Re-count orders in each course', 'learnpress');?>
            </label>
        </li>
        <li>
            <label>
                <input type="checkbox" name="lp-repair[sync-user-orders]" value="yes">
			    <?php esc_html_e('Re-count orders for each user', 'learnpress');?>
            </label>
        </li>
        <li>
            <label>
                <input type="checkbox" name="lp-repair[sync-user-courses]" value="yes">
			    <?php esc_html_e('Re-count courses for each user', 'learnpress');?>
            </label>
        </li>
        <li>
            <label>
                <input type="checkbox" name="lp-repair[sync-course-final-quiz]" value="yes">
			    <?php esc_html_e('Re-map final quiz for each course', 'learnpress');?>
            </label>
        </li>
        <li>
            <label>
                <input type="checkbox" name="lp-repair[sync-calculate-course-results]" value="yes">
			    <?php esc_html_e('Re-calculate course result for users', 'learnpress');?>
            </label>
        </li>
        <li>
            <label>
                <input type="checkbox" name="lp-repair[sync-user-completed-items]" value="yes">
			    <?php esc_html_e('Re-calculate completed items for each users', 'learnpress');?>
            </label>
        </li>
    </ul>
    <p class="tools-button">
        <button type="button" class="button lp-button-repair"><?php esc_html_e( 'Repair now', 'learnpress' ); ?></button>
        <label>
            <input type="checkbox" id="learn-press-check-all-syncs">
            <?php esc_html_e('Check All', 'learnpress');?>
        </label>
    </p>
</div>