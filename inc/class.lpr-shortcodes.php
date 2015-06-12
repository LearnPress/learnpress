<?php
/**
 * LPR_Shortcodes class
 */
class LPR_Shortcodes{
    static function init(){
        $shortcodes = array(
            'learn_press_confirm_order' => __CLASS__ . '::confirm_order',
            'learn_press_profile' => __CLASS__ . '::profile'
        );

        foreach ( $shortcodes as $shortcode => $function ) {
            add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
        }
    }

    static function confirm_order( $atts = null ){
        $atts = shortcode_atts(
            array(
                'order_id' => ! empty( $_REQUEST['order_id'] ) ? intval( $_REQUEST['order_id'] ) : 0
            ),
            $atts
        );

        $order_id = null;

        extract( $atts );
        ob_start();

        $order = learn_press_get_order( $order_id );

        if( $order ) {
            learn_press_get_template( 'order/confirm.php', array( 'order' => $order ) );
        }
        return ob_get_clean();
    }

    static function profile() {

        global $wp_query;

        if ( isset( $wp_query->query['user'] ) ) {
            $user = get_user_by( 'login', $wp_query->query['user'] );
        } else {
            $user = get_user_by( 'id', get_current_user_id() );
        }

        $output = '';
        if ( !$user ) {
            $output .= '<strong>' . __( 'This user in not available!', 'learn_press' ) . '</strong>';
            return $output;
        }

        do_action( 'learn_press_before_profile_content' );

        ?>
        <div id="profile-tabs">
            <?php do_action( 'learn_press_add_profile_tab', $user ); ?>
        </div>
        <script>
            jQuery(document).ready(function ($) {
                $("#profile-tabs").tabs();
                $( "#quiz-accordion" ).accordion();
            });
        </script>
        <?php
        do_action( 'learn_press_after_profile_content' );

    }
}
LPR_Shortcodes::init();