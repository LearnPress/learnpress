<?php
/**
 * Template for displaying finish step.
 *
 * @author  ThimPres
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;
?>
<h2><?php _e( 'Emails system', 'learnpress' ); ?></h2>

<p><?php _e( 'Emails are sent to users or teachers for each particular action.', 'learnpress' ); ?></p>
<p><?php _e( 'You can enable/disable each email in LearnPress settings later.', 'learnpress' ); ?></p>
<p><?php _e( 'But in right now, you can enable all emails to see how emails work.', 'learnpress' ); ?></p>
<p><?php _e( 'You can skip to next step if you don’t want to.', 'learnpress' ); ?></p>

<p>
    <label>
        <input type="checkbox" name="settings[emails][enable]" value="yes">
		<?php _e( 'Enable emails', 'learnpress' ); ?>
    </label>
</p>