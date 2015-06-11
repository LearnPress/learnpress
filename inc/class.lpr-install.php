<?php
class LPR_Install{
    static function init(){
        add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
    }

    static function check_version(){
        if ( ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'learnpress_version' ) != LearnPress()->version || get_option( 'learnpress_version' ) != LearnPress()->version ) ) {
            self::install();
        }

        //print_r(learn_press_admin_settings('emails'));
    }

    static function install_options(){
        $options = array(
            '_lpr_settings_general' => 'a:6:{s:8:"set_page";s:11:"lpr_profile";s:8:"currency";s:3:"USD";s:12:"currency_pos";s:15:"left_with_space";s:19:"thousands_separator";s:1:",";s:18:"decimals_separator";s:1:".";s:18:"number_of_decimals";s:1:"2";}',
            '_lpr_settings_pages'   => 'a:3:{s:7:"general";a:2:{s:15:"courses_page_id";s:0:"";s:28:"taken_course_confirm_page_id";s:0:"";}s:6:"course";a:1:{s:13:"retake_course";s:1:"0";}s:4:"quiz";a:1:{s:11:"retake_quiz";s:1:"0";}}',
            '_lpr_settings_payment' => 'a:1:{s:6:"paypal";a:9:{s:4:"type";s:5:"basic";s:12:"paypal_email";s:0:"";s:19:"paypal_api_username";s:0:"";s:19:"paypal_api_password";s:0:"";s:20:"paypal_api_signature";s:0:"";s:20:"paypal_sandbox_email";s:0:"";s:27:"paypal_sandbox_api_username";s:0:"";s:27:"paypal_sandbox_api_password";s:0:"";s:28:"paypal_sandbox_api_signature";s:0:"";}}',
            '_lpr_settings_emails'  => 'a:3:{s:16:"published_course";a:3:{s:6:"enable";s:1:"1";s:7:"subject";s:15:"Approved Course";s:7:"message";s:215:"<p><strong>Dear {user_name}</strong>,</p>
<p>Congratulation! The course you created ({course_name}) is available now.</p>
<p>Visit our website at {log_in}.</p>
<p>Best regards,</p>
<p><em>Administration</em></p>";}s:15:"enrolled_course";a:3:{s:6:"enable";s:1:"1";s:7:"subject";s:19:"Course Registration";s:7:"message";s:183:"<p><strong>Dear {user_name}</strong>,</p>
<p>You have been enrolled in {course_name}.</p>
<p>Visit our website at {log_in}.</p>
<p>Best regards,</p>
<p><em>Administration</em></p>";}s:13:"passed_course";a:3:{s:6:"enable";s:1:"1";s:7:"subject";s:18:"Course Achievement";s:7:"message";s:203:"<p><strong>Dear {user_name}</strong>,</p>
<p>You have been finished in {course_name} with {course_result}</p>
<p>Visit our website at {log_in}.</p>
<p>Best regards,</p>
<p><em>Administration</em></p>";}}'
        );
        foreach( $options as $k => $option ){
            update_option( $k, maybe_unserialize( $option ) );
        }
    }

    static function install(){
        self::install_options();

        // Update version
        delete_option( 'learnpress_version' );


        add_option( 'learnpress_version', LearnPress()->version );

        $s = learn_press_admin_settings('emails');
        $s->set('general', array(
                'from_name' => get_option( 'blogname' ),
                'from_email' =>  get_option( 'admin_email' )
            )
        );
        $s->update();

    }
}

LPR_Install::init();