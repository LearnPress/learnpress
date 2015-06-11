<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Setting page
 */
function learn_press_settings_page() {
    require_once( LPR_PLUGIN_PATH . "/inc/admin/class.lpr-admin-settings.php" );
    require_once( LPR_PLUGIN_PATH . "/inc/admin/settings/class.lpr-settings-base.php" );
    require_once( LPR_PLUGIN_PATH . "/inc/admin/settings/class.lpr-settings-general.php" );
    require_once( LPR_PLUGIN_PATH . "/inc/admin/settings/class.lpr-settings-pages.php" );
    require_once( LPR_PLUGIN_PATH . "/inc/admin/settings/class.lpr-settings-payment.php" );
    require_once( LPR_PLUGIN_PATH . "/inc/admin/settings/class.lpr-settings-emails.php" );

    $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';

    $tabs = learn_press_settings_tabs_array();
    if ( ! empty( $_POST ) ) {
        //	 Check if our nonce is set.
        if (!isset($_POST['learn_press_settings_nonce'])) {
            return;
        }

        // Verify that the nonce is valid.
        if (!wp_verify_nonce($_POST['learn_press_settings_nonce'], 'learn_press_settings')) {
            return;
        }

        do_action('learn_press_settings_save_' . $current_tab );

        echo '<div class="updated"><p><strong>' . __('Settings saved', 'learn_press') . '</strong></p></div>';
    }
    if( !$current_tab && $tabs ){
        $current_tab = reset( array_keys( $tabs ) );
    }
    ?>
    <form method="<?php echo esc_attr( apply_filters( 'learn_press_settings_form_method_tab_' . $current_tab, 'post' ) ); ?>" id="mainform" action="" enctype="multipart/form-data">
        <div id="icon-themes" class="icon32"><br></div>
        <h2 class="nav-tab-wrapper">
            <?php if( $tabs ) foreach ( $tabs as $tab => $name ) {?>
                <?php $class = ( $tab == $current_tab ) ? ' nav-tab-active' : '';?>
                <a class="nav-tab <?php echo $class;?>" href="?page=learn_press_settings&tab=<?php echo $tab;?>"><?php echo $name;?></a>
            <?php }?>
            <?php do_action( 'learn_press_settings_tabs' );?>
        </h2>
        <?php
        do_action( 'learn_press_sections_' . $current_tab );

        do_action( 'learn_press_settings_' . $current_tab );
        ?>
        <p>
            <button class="button button-primary"><?php _e( 'Save settings', 'learn_press' );?></button>
        </p>
        <?php wp_nonce_field( 'learn_press_settings', 'learn_press_settings_nonce' );?>
    </form>
<?php
}