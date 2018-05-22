<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();
?>

<div class="card">
    <h2><?php _e( 'Upgrade Database', 'learnpress' ); ?></h2>
    <p><?php _e( 'Force upgrade database to latest version. Please be careful before taking this action.', 'learnpress' ); ?></p>
    <p class="tools-button">
        <a class="button lp-button-upgrade"
           data-context="tool"
           href="<?php echo esc_url( admin_url( 'index.php?do-update-learnpress=yes' ) ); ?>"><?php esc_html_e( 'Upgrade now', 'learnpress' ); ?></a>
    </p>
</div>