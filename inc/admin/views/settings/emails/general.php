<?php
/**
 * Display general settings for emails
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views/Emails
 * @version 1.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$settings = LP()->settings;
?>
<h3><?php _e( 'Email Options', 'learn_press' ); ?></h3>
<table class="form-table">
	<tbody>
	<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	<tr>
		<th scope="row"><label for="lpr_from_name"><?php _e( 'From Name', 'learn_press' ); ?></label></th>
		<td>
			<input id="lpr_from_name" class="regular-text" type="text" name="lpr_settings[<?php echo $this->id; ?>][from_name]" value="<?php echo $settings->get( 'general.from_name', get_option( 'blogname' ) ); ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="lpr_from_email"><?php _e( 'From Email', 'learn_press' ); ?></label></th>
		<td>
			<input id="lpr_from_email" class="regular-text" type="email" name="lpr_settings[<?php echo $this->id; ?>][from_email]" value="<?php echo $settings->get( 'general.from_email', get_option( 'admin_email' ) ); ?>" />
		</td>
	</tr>
	<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	</tbody>
</table>