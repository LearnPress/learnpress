<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();
?>

<div class="card">
    <h2><?php _e( 'Remove outdated Data', 'learnpress' ); ?></h2>
    <p><?php _e( 'Remove all courses, lessons, quizzes and questions from version older than 1.0.', 'learnpress' ); ?></p>
    <form method="post" name="learn-press-form-remove-data">
        <div class="learn-press-message lp-error">
            <p><?php _e( 'Be careful before using this action! Only use this action in case all outdated data has been upgraded.', 'learnpress' ); ?></p>
        </div>
        <label class="hide-if-js">
            <input type="checkbox" name="action" value="learn-press-remove-old-data"/>
			<?php _e( 'Check this box and click this button again to confirm.', 'learnpress' ); ?>
        </label>
        <p class="tools-button">
            <button class="button button-fade"><?php esc_html_e( 'Remove', 'learnpress' ); ?></button>
        </p>
		<?php wp_nonce_field( 'learn-press-remove-old-data', 'remove-old-data-nonce' ); ?>

    </form>
</div>