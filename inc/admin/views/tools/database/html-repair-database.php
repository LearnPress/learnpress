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
    <ul>
        <li>
            <label>
                <input type="checkbox">
            </label>
        </li>
    </ul>
    <p class="tools-button">
        <a class="button lp-button-upgrade"
           data-context="tool"
           href="<?php echo esc_url( admin_url( 'index.php?do-update-learnpress=yes' ) ); ?>"><?php esc_html_e( 'Repair now', 'learnpress' ); ?></a>
    </p>
</div>