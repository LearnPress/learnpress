<?php


if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Add-on page
 */
function learn_press_add_ons_page() {
    //Add action print page tab
    add_action('learn_press_print_add_on_page_tab', 'learn_press_print_enabled_tab');
    add_action('learn_press_print_add_on_page_tab', 'learn_press_print_disabled_tab');
    add_action('learn_press_print_add_on_page_tab', 'learn_press_print_get_more_tab');

    //print add on page
    learn_press_add_ons_list();
}

/**
 * Add-ons list
 */
function learn_press_add_ons_list(){
?>

<div id="learn-press-add-ons-wrap" class="wrap">

    <!-- Add on top page -->
    <h2><?php echo __('LearnPress Add-ons', 'learn_press'); ?></h2>
    <p class="top-description"><?php _e( 'Add-ons are features that you can add or remove depending on your needs', 'learn_press' ); ?></p>

    <!-- Tab -->
    <?php
//        do_action( 'learn_press_register_prerequisites' );

        $current = isset( $_GET['tab'] ) ? $_GET['tab'] : 'all';

        $disable_add_on = isset( $_GET['learn-press-disable-addon'] ) ? $_GET['learn-press-disable-addon'] : false;
        $enable_add_on = isset( $_GET['learn-press-enable-addon'] ) ? $_GET['learn-press-enable-addon'] : false;
        if( $enable_add_on ) {
            learn_press_enable_add_on( $enable_add_on );
        } else if( $disable_add_on ) {
                learn_press_disable_add_on( $disable_add_on );
            }

        learn_press_print_add_on_tab($current);

        do_action( 'learn_press_add_on_page_on_top' );

        switch($current){
            case 'enabled':
                $add_ons = learn_press_get_enabled_add_ons( array( 'show_required' => false ) );
                break;

            case 'disabled':
                $add_ons = learn_press_get_disabled_add_ons( array( 'show_required' => false ) );
                break;

            case 'get_more':
                $add_ons = learn_press_get_more_add_ons( array( 'show_required' => false ) );
                break;

            case 'all':
            default:
                $add_ons = learn_press_get_add_ons( array( 'show_required' => false ) );
                break;
        }

        if( isset( $add_ons ) && is_array( $add_ons ) ) {
            foreach( $add_ons as $add_on ) {

            }
        }
    ?>
    <div id="lpr-add-ons-wrapper">
        <?php if( isset( $add_ons ) && is_array( $add_ons ) ) { ?>
        <?php foreach( $add_ons as $add_on ) { ?>
            <?php

            if ( !empty( $addon['options']['tag'] ) && 'required' === $addon['options']['tag'] )
                continue;

                $is_core = learn_press_is_core_addon( $add_on['slug'] );
            ?>
            <div class="lpr-add-on-block">
                <div class="lpr-admin-thumbnail">
                    <?php if( !empty( $add_on['preview'] ) ){?>
                    <img src="<?php echo $add_on['preview'];?>">
                    <?php }else{?>
                    <img src="<?php echo admin_url( 'admin-ajax.php?action=learnpress_dummy_image' );?>&text=<?php echo $add_on['name'];?>&color=777777">
                    <?php }?>
                </div>
                <div class="lpr-add-on-info">
                    <h3> <?php echo $add_on['name'] ?><?php echo $is_core ? '<span>'.__('Core', 'learn_press').'</span>' : '';?></h3>
                    <h4> <?php echo $add_on['description'] ?> </h4>
                    <?php if ( learn_press_is_addon_enabled( $add_on['slug'] ) ) : ?>
                        <?php $url = $is_core ? wp_nonce_url( get_site_url() . '/wp-admin/admin.php?page=learn_press_add_ons&learn-press-disable-addon=' . $add_on['slug'] . '&tab=' . $current, 'learn-press-disable-add-on' ) : admin_url() . 'plugins.php'; ?>
                        <input type="checkbox" class="lpr-fancy-checkbox" checked="checked" data-url="<?php echo $url;?>" data-iscore="<?php echo $is_core ? 1 : 0;?>">
                    <?php else : ?>
                        <input type="checkbox" class="lpr-fancy-checkbox" data-url="<?php echo wp_nonce_url( get_site_url() . '/wp-admin/admin.php?page=learn_press_add_ons&learn-press-enable-addon=' . $add_on['slug'] . '&tab=' . $current, 'learn-press-enable-add-on' );?>"  data-iscore="<?php echo $is_core ? 1 : 0;?>">
                    <?php endif; ?>

                    <?php if ( ! empty( $add_on['options']['settings-callback'] ) && is_callable( $add_on['options']['settings-callback'] ) ) : ?>
                        <div class="add-on-settings"><a href="<?php echo admin_url( 'admin.php?page=learn_press_add_ons&add-on-settings=' . $add_on['slug'] ); ?>">S</a></div>
                    <?php endif; ?>
                </div>
                <div class="lpr-add-on-action">
                    <!--
                    <?php if ( learn_press_is_addon_enabled( $add_on['slug'] ) ) : ?>
                        <?php $url = learn_press_is_core_addon( $add_on['slug'] ) ? wp_nonce_url( get_site_url() . '/wp-admin/admin.php?page=


                        &learn-press-disable-addon=' . $add_on['slug'] . '&tab=' . $current, 'learn-press-disable-add-on' ) : admin_url() . 'plugins.php'; ?>
                        <div class="add-on-enabled"><a href="<?php echo $url; ?>" data-text-disable="&times;&nbsp; Disable" data-text-enabled="&#x2714;&nbsp; Enabled">&#x2714;Enabled</a></div>
                    <?php else : ?>
                        <div class="add-on-disabled"><a href="<?php echo wp_nonce_url( get_site_url() . '/wp-admin/admin.php?page=learn_press_add_ons&learn-press-enable-addon=' . $add_on['slug'] . '&tab=' . $current, 'learn-press-enable-add-on' ); ?>" data-text-enable="&#x2714;&nbsp; Enable" data-text-disabled="&times;&nbsp; Disabled">&times;&nbsp; Disabled</a></div>
                    <?php endif; ?>

                    <?php if ( ! empty( $add_on['options']['settings-callback'] ) && is_callable( $add_on['options']['settings-callback'] ) ) : ?>
                        <div class="add-on-settings"><a href="<?php echo admin_url( 'admin.php?page=learn_press_add_ons&add-on-settings=' . $add_on['slug'] ); ?>">S</a></div>
                    <?php endif; ?>
                    -->
                </div>
            </div>
        <?php }} ?>
    </div>

</div>

<?php }?>