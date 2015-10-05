<?php

/**
 * Class LPR_Settings_Payment
 */
class LPR_Settings_Payment extends LPR_Settings_Base{
    function __construct(){
        $this->id   = 'payment';
        $this->text = __( 'Payments', 'learn_press' );
        parent::__construct();
    }

    function get_sections(){
        $sections = array(
            'paypal'    => __( 'Paypal', 'learn_press' )
        );

        return apply_filters( 'learn_press_payment_method', $sections );
    }

    function output(){
        $section = $this->section;
        ?>
        <h3 class=""><?php echo $this->section['text'];?></h3>
        <table class="form-table">
            <tbody>
            <?php
            if( 'paypal' == $section['id'] ){
                $this->output_section_paypal();
            }else{
                do_action( 'learn_press_section_' . $this->id . '_' . $section['id'] );
            }
            ?>
            </tbody>
        </table>
        <script type="text/javascript">
            jQuery(function($){
                var $sandbox_mode   = $('#learn_press_paypal_sandbox_mode'),
                    $paypal_type    = $('#learn_press_paypal_type');
                $paypal_type.change(function(){
                    $('.learn_press_paypal_type_security').toggleClass( 'hide-if-js', 'security' != this.value );
                });
                $sandbox_mode.change(function(){
                    this.checked ? $('.sandbox input').removeAttr( 'readonly' ) : $('.sandbox input').attr( 'readonly', true );
                });
            })
        </script>
        <?php
    }

    /**
     * Print admin options for paypal section
     */
    function output_section_paypal(){
        $settings = LPR_Admin_Settings::instance( 'payment' );
    ?>

        <?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings );?>
        <tr>
            <th scope="row"><label for="learn_press_paypal_enable"><?php _e( 'Enable', 'learn_press' );?></label></th>
            <td>
                <input type="checkbox" id="learn_press_paypal_enable" name="lpr_settings[<?php echo $this->id;?>][enable]" <?php checked( $settings->get('paypal.enable', '') ? 1 : 0, 1);?> />
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="learn_press_paypal_type"><?php _e( 'Type', 'learn_press' );?></label></th>
            <td>
                <select id="learn_press_paypal_type" name="lpr_settings[<?php echo $this->id;?>][type]" value="<?php echo $settings->get('paypal.type', '');?>">
                    <option value="basic"<?php selected( $settings->get('paypal.type') == 'basic' ? 1 : 0, 1 );?>><?php _e( 'Basic', 'learn_press' );?></option>
                    <option value="security" <?php selected( $settings->get('paypal.type') == 'security' ? 1 : 0, 1 );?>><?php _e( 'Security', 'learn_press' );?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="learn_press_paypal_email"><?php _e( 'Email Address', 'learn_press' );?></label></th>
            <td>
                <input type="email" class="regular-text" id="learn_press_paypal_email" name="lpr_settings[<?php echo $this->id;?>][paypal_email]" value="<?php echo $settings->get('paypal.paypal_email', '');?>" />
            </td>
        </tr>
        <tr class="learn_press_paypal_type_security<?php echo $settings->get( 'paypal.type' ) != 'security' ? ' hide-if-js' : '';?>">
            <th scope="row"><label for="learn_press_paypal_api_name"><?php _e( 'API Username', 'learn_press' );?></label></th>
            <td>
                <input type="text" class="regular-text" id="learn_press_paypal_api_name" name="lpr_settings[<?php echo $this->id;?>][paypal_api_username]" value="<?php echo $settings->get('paypal.paypal_api_username', '');?>" />
            </td>
        </tr>
        <tr class="learn_press_paypal_type_security<?php echo $settings->get( 'paypal.type' ) != 'security' ? ' hide-if-js' : '';?>">
            <th scope="row"><label for="learn_press_paypal_api_pass"><?php _e( 'API Password', 'learn_press' );?></label></th>
            <td>
                <input type="password" class="regular-text" id="learn_press_paypal_api_pass" name="lpr_settings[<?php echo $this->id;?>][paypal_api_password]" value="<?php echo $settings->get('paypal.paypal_api_password', '');?>" />
            </td>
        </tr>
        <tr class="learn_press_paypal_type_security<?php echo $settings->get( 'paypal.type' ) != 'security' ? ' hide-if-js' : '';?>">
            <th scope="row"><label for="learn_press_paypal_api_sign"><?php _e( 'API Signature', 'learn_press' );?></label></th>
            <td>
                <input type="text" class="regular-text" id="learn_press_paypal_api_sign" name="lpr_settings[<?php echo $this->id;?>][paypal_api_signature]" value="<?php echo $settings->get('paypal.paypal_api_signature', '');?>" />
            </td>
        </tr>
        <!-- sandbox mode -->
        <?php
        $show_or_hide = $settings->get('paypal.type') == 'security' ? '' : ' hide-if-js';
        $readonly = $settings->get('paypal.sandbox') ? '' : ' readonly="readonly"';
        ?>
        <tr>
            <th scope="row"><label for="learn_press_paypal_sandbox_mode"><?php _e( 'Sandbox Mode', 'learn_press' );?></label></th>
            <td>
                <input type="checkbox" id="learn_press_paypal_sandbox_mode" name="lpr_settings[<?php echo $this->id;?>][sandbox]" value="1" <?php checked( $settings->get('paypal.sandbox') ? 1 : 0,1 );?> />
            </td>
        </tr>
        <tr class="sandbox">
            <th scope="row"><label for="learn_press_paypal_sandbox_email"><?php _e( 'Sandbox Email Address', 'learn_press' );?></label></th>
            <td>
                <input type="email" id="learn_press_paypal_sandbox_email" class="regular-text"<?php echo $readonly;?> name="lpr_settings[<?php echo $this->id;?>][paypal_sandbox_email]" value="<?php echo $settings->get('paypal.paypal_sandbox_email', '');?>" />
            </td>
        </tr>
        <tr class="learn_press_paypal_type_security sandbox<?php echo $show_or_hide;?>">
            <th scope="row"><label for="learn_press_paypal_sandbox_name"><?php _e( 'Sandbox API Username', 'learn_press' );?></label></th>
            <td>
                <input type="text" id="learn_press_paypal_sandbox_name" class="regular-text"<?php echo $readonly;?> name="lpr_settings[<?php echo $this->id;?>][paypal_sandbox_api_username]" value="<?php echo $settings->get('paypal.paypal_sandbox_api_username', '');?>" />
            </td>
        </tr>
        <tr class="learn_press_paypal_type_security sandbox<?php echo $show_or_hide;?>">
            <th scope="row"><label for="learn_press_paypal_sandbox_pass"><?php _e( 'Sandbox API Password', 'learn_press' );?></label></th>
            <td>
                <input type="password" id="learn_press_paypal_sandbox_pass" class="regular-text"<?php echo $readonly;?> name="lpr_settings[<?php echo $this->id;?>][paypal_sandbox_api_password]" value="<?php echo $settings->get('paypal.paypal_sandbox_api_password', '');?>" />
            </td>
        </tr>
        <tr class="learn_press_paypal_type_security sandbox<?php echo $show_or_hide;?>">
            <th scope="row"><label for="learn_press_paypal_sandbox_sign"><?php _e( 'Sandbox API Signature', 'learn_press' );?></label></th>
            <td>
                <input type="text" id="learn_press_paypal_sandbox_sign" class="regular-text"<?php echo $readonly;?> name="lpr_settings[<?php echo $this->id;?>][paypal_sandbox_api_signature]" value="<?php echo $settings->get('paypal.paypal_sandbox_api_signature', '');?>" />
            </td>
        </tr>
        <?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings );?>

    <?php
    }
    function output_section_third_party(){

    }

    function save(){

		$settings = LPR_Admin_Settings::instance( 'payment' );
        $section = $this->section['id'];
        if( 'paypal' == $section ){
            $post_data = $_POST['lpr_settings'][$this->id];

            $settings->set( 'paypal', $post_data );
        }else{
            do_action( 'learn_press_save_' . $this->id . '_' . $section );
        }
        $settings->update();
        return;
        $payment_options = get_option( '_lpr_payment_settings', array() );
        $section         = isset( $_GET['section'] ) ? $_GET['section'] : 'paypal';
        $params          = isset( $_POST['lpr_settings']['payment'][$section] ) ? $_POST['lpr_settings']['payment'][$section] : $payment_options[$section];
        $payment_options[$section]  = $params;
        $payment_options['method'] = isset( $_POST['lpr_settings']['payment']['method'] ) ? $_POST['lpr_settings']['payment']['method'] : '';
        $payment_options['third_party'] = isset( $_POST['lpr_settings']['payment']['third_party'] ) ? $_POST['lpr_settings']['payment']['third_party'] : '';
        update_option( '_lpr_payment_settings', $payment_options );
        return;
        $payment_options                = get_option( '_lpr_payment_settings', array() );
        $payment_tab                    = isset( $_GET['section'] ) ? $_GET['section'] : 'paypal';
        $params                         = isset( $_POST['lpr_settings']['payment'][$payment_tab] ) ? $_POST['lpr_settings']['payment'][$payment_tab] : $payment_options[$payment_tab];
        $payment_options[$payment_tab]  = $params;
        $payment_options['woocommerce'] = isset( $_POST['lpr_settings']['payment']['woocommerce'] ) ? $_POST['lpr_settings']['payment']['woocommerce'] : array();
        update_option( '_lpr_payment_settings', $payment_options );
    }
}
new LPR_Settings_Payment();