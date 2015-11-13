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
<h3><?php _e( 'New order', 'learn_press' ); ?></h3>
<p class="description">
	<?php _e( 'Settings for email when a new order placed', 'learn_press' );?>
</p>
<table class="form-table">
	<tbody>
	<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	<tr>
		<th scope="row"><label for="learn-press-emails-new-order-enable"><?php _e( 'Enable', 'learn_press' ); ?></label></th>
		<td>
			<input type="hidden" name="<?php echo $this->get_field_name('emails_new_order[enable]');?>" value="no" />
			<input class="regular-text" id="learn-press-emails-new-order-enable" type="text" name="<?php echo $this->get_field_name('emails_new_order[enable]');?>" value="yes" <?php checked( $settings->get( 'emails_new_order.enable', 'yes' ) == 'yes' ); ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="learn-press-emails-new-order-recipients"><?php _e( 'Recipient(s)', 'learn_press' ); ?></label></th>
		<td>
			<input id="learn-press-emails-new-order-recipients" class="regular-text" type="text" name="<?php echo $this->get_field_name( 'emails_new_order[recipients]');?>" value="<?php echo $settings->get( 'emails_new_order.recipients', get_option( 'admin_email' ) ); ?>" />
			<p class="description">
				<?php printf( __( 'Email recipient(s), default: %s', 'learn_press' ),  get_option( 'admin_email' ) );?>
			</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="learn-press-emails-new-order-subject"><?php _e( 'Subject', 'learn_press' ); ?></label></th>
		<td>
			<input id="learn-press-emails-new-order-subject" class="regular-text" type="text" name="<?php echo $this->get_field_name( 'emails_new_order[subject]');?>" value="<?php echo $settings->get( 'emails_new_order.subject', '[{site_title}] New customer order ({order_number}) - {order_date}' ); ?>" />
			<p class="description">
				<?php printf( __( 'Email subject, default: <code>%s</code>', 'learn_press' ), '[{site_title}] New customer order ({order_number}) - {order_date}' );?>
			</p>
		</td>
	</tr>
	<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	</tbody>
</table>