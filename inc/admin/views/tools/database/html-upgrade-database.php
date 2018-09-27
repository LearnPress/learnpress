<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();

if ( LP_Updater::instance()->has_update() ) {
	return;
}
?>

<div class="card">
    <h2><?php _e( 'Upgrade Database', 'learnpress' ); ?></h2>
    <p><?php _e( 'Force upgrade database to latest version. Please be careful before taking this action.', 'learnpress' ); ?></p>
    <!--    <p class="tools-button">-->
    <!--        <a class="button lp-button-upgrade"-->
    <!--           data-context="tool"-->
    <!--           href="--><?php //echo esc_url( admin_url( 'index.php?do-update-learnpress=yes' ) ); ?><!--">-->
	<?php //esc_html_e( 'Upgrade now', 'learnpress' ); ?><!--</a>-->
    <!--    </p>-->

    <div id="learn-press-updater" class="lp-notice-update-database">

        <p class="updating-message" v-show="status=='updating'">
			<?php _e( '<strong>LearnPress Updater</strong> is running. Until process is complete, please don\'t close this page.', 'learnpress' ); ?>
        </p>
        <p class="completed-message" v-show="status=='completed'">
			<?php _e( '<strong>LearnPress</strong> update completed.', 'learnpress' ); ?>
        </p>
        <div v-show="status=='updating'" class="updater-progress">
            <ul>
                <li v-for="(package, version) in getPackages()" :data-version="version">
                </li>
            </ul>
            <div class="updater-progress-status" data-value="0">
                <div class="updater-progress-animation"></div>
            </div>
        </div>
        <p v-show="status!='completed'">
            <a class="button button-primary lp-button-upgrade"
               data-context="message"
               :class="{disabled: status == 'updating'}"
               href="<?php echo esc_url( admin_url( 'index.php?do-update-learnpress=yes' ) ); ?>"
               @click.prevent="start($event, true)"><?php _e( 'Update Now', 'learnpress' ); ?></a>
        </p>
    </div>
	<?php //learn_press_admin_view( 'updates/html-update-message' ); ?>
</div>