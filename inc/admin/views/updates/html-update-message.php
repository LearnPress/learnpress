<?php
/**
 * Template for displaying update message
 *
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;
?>

<div id="learn-press-updater" class="notice notice-warning lp-notice-update-database">
    <p v-show="!status">
		<?php _e( '<strong>LearnPress update</strong> – We need to update your database to the latest version.', 'learnpress' ); ?>
    </p>
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
           @click.prevent="start"><?php _e( 'Update Now', 'learnpress' ); ?></a>
    </p>
</div>