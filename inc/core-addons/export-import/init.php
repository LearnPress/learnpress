<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( ! defined( 'LPR_EXPORT_IMPORT_PATH' ) ) define( 'LPR_EXPORT_IMPORT_PATH', dirname( __FILE__ ) );
/**
 * Register Export/Import courses addon
 */
function learn_press_register_export_import() {

    $args = array(
        'name'              => __( 'Export/Import', 'learn_press' ),
        'description'       => __( 'Export and Import your courses with all lesson and quiz in easiest way', 'learn_press' ),
        'author'            => 'foobla',
        'author_url'        => 'http://thimpress.com',
        'file'              => LPR_EXPORT_IMPORT_PATH . '/export-import.php',
        'category'          => 'courses',
        'tag'               => 'core',
        'settings-callback' => '',
    );
    $args = apply_filters( 'learn_press_export_import_args', $args );
    learn_press_addon_register( 'export-import-add-on', $args );
}
add_action( 'learn_press_register_add_ons', 'learn_press_register_export_import' );
