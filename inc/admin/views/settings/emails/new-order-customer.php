<<<<<<< HEAD
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
<h3><?php _e( 'New order customer', 'learnpress' ); ?></h3>
<p class="description">
    <?php _e( 'Send email to a specific address when a new order is placed.', 'learnpress' ); ?>
</p>
<table class="form-table">
    <tbody>
        <?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
        <tr>
            <th scope="row">
                <label for="learn-press-emails-new-order-enable"><?php _e( 'Enable', 'learnpress' ); ?></label></th>
            <td>
                <input type="hidden" name="<?php echo $settings_class->get_field_name( 'emails_new_order_customer[enable]' ); ?>" value="no" />
                <input id="learn-press-emails-new-order-enable" type="checkbox" name="<?php echo $settings_class->get_field_name( 'emails_new_order_customer[enable]' ); ?>" value="yes" <?php checked( $settings->get( 'emails_new_order_customer.enable' ), 'yes' ); ?> />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="learn-press-emails-new-order-subject"><?php _e( 'Subject', 'learnpress' ); ?></label></th>
            <td>
                <?php $default = $this->default_subject; ?>
                <input id="learn-press-emails-new-order-subject" class="regular-text" type="text" name="<?php echo $settings_class->get_field_name( 'emails_new_order_customer[subject]' ); ?>" value="<?php echo $settings->get( 'emails_new_order_customer.subject', $default ); ?>" />

                <p class="description">
                    <?php printf( __( 'Email subject, default: <code>%s</code>', 'learnpress' ), $default ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="learn-press-emails-new-order-heading"><?php _e( 'Heading', 'learnpress' ); ?></label></th>
            <td>
                <?php $default = $this->default_heading; ?>
                <input id="learn-press-emails-new-order-heading" class="regular-text" type="text" name="<?php echo $settings_class->get_field_name( 'emails_new_order_customer[heading]' ); ?>" value="<?php echo $settings->get( 'emails_new_order_customer.heading', $default ); ?>" />

                <p class="description">
                    <?php printf( __( 'Email heading, default: <code>%s</code>', 'learnpress' ), $default ); ?>
                </p>
            </td>
        </tr>
        <!--
        <tr>
            <th scope="row">
                <label for="learn-press-emails-new-order-email-format"><?php _e( 'Email format', 'learnpress' ); ?></label>
            </th>
            <td>
                <?php learn_press_email_formats_dropdown( array( 'name' => $settings_class->get_field_name( 'emails_new_order_customer[email_format]' ), 'id' => 'learn_press_email_formats', 'selected' => $settings->get( 'emails_new_order_customer.email_format', $default ) ) ); ?>
            </td>
        </tr>
        -->
        <?php
        $view = learn_press_get_admin_view( 'settings/emails/email-template.php' );
        include_once $view;
        ?>
        <?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
    </tbody>
=======
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
<h3><?php _e( 'New order customer', 'learnpress' ); ?></h3>
<p class="description">
    <?php _e( 'Send email to a specific address when a new order is placed.', 'learnpress' ); ?>
</p>
<table class="form-table">
    <tbody>
        <?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
        <tr>
            <th scope="row">
                <label for="learn-press-emails-new-order-enable"><?php _e( 'Enable', 'learnpress' ); ?></label></th>
            <td>
                <input type="hidden" name="<?php echo $settings_class->get_field_name( 'emails_new_order_customer[enable]' ); ?>" value="no" />
                <input id="learn-press-emails-new-order-enable" type="checkbox" name="<?php echo $settings_class->get_field_name( 'emails_new_order_customer[enable]' ); ?>" value="yes" <?php checked( $settings->get( 'emails_new_order_customer.enable' ), 'yes' ); ?> />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="learn-press-emails-new-order-subject"><?php _e( 'Subject', 'learnpress' ); ?></label></th>
            <td>
                <?php $default = $this->default_subject; ?>
                <input id="learn-press-emails-new-order-subject" class="regular-text" type="text" name="<?php echo $settings_class->get_field_name( 'emails_new_order_customer[subject]' ); ?>" value="<?php echo $settings->get( 'emails_new_order_customer.subject', $default ); ?>" />

                <p class="description">
                    <?php printf( __( 'Email subject, default: <code>%s</code>', 'learnpress' ), $default ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="learn-press-emails-new-order-heading"><?php _e( 'Heading', 'learnpress' ); ?></label></th>
            <td>
                <?php $default = $this->default_heading; ?>
                <input id="learn-press-emails-new-order-heading" class="regular-text" type="text" name="<?php echo $settings_class->get_field_name( 'emails_new_order_customer[heading]' ); ?>" value="<?php echo $settings->get( 'emails_new_order_customer.heading', $default ); ?>" />

                <p class="description">
                    <?php printf( __( 'Email heading, default: <code>%s</code>', 'learnpress' ), $default ); ?>
                </p>
            </td>
        </tr>
        <!--
        <tr>
            <th scope="row">
                <label for="learn-press-emails-new-order-email-format"><?php _e( 'Email format', 'learnpress' ); ?></label>
            </th>
            <td>
                <?php learn_press_email_formats_dropdown( array( 'name' => $settings_class->get_field_name( 'emails_new_order_customer[email_format]' ), 'id' => 'learn_press_email_formats', 'selected' => $settings->get( 'emails_new_order_customer.email_format', $default ) ) ); ?>
            </td>
        </tr>
        -->
        <?php
        $view = learn_press_get_admin_view( 'settings/emails/email-template.php' );
        include_once $view;
        ?>
        <?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
    </tbody>
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
</table>