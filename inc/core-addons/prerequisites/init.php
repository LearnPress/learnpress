<?php
/**
 * Created by PhpStorm.
 * User: foobla
 * Date: 4/3/2015
 * Time: 10:49 AM
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'LPR_PREREQUISITES_PLUGIN_PATH', dirname( __FILE__ ) );
/**
 * Register prerequisite course addon
 */
function learn_press_register_prerequisites() {

    $prerequisite = array(
        'prerequisite-add-on' => array(
            'name'              => __( 'Prerequisite Courses', 'learn_press' ),
            'description'       => __( 'Adding prerequisite course when add new a course ', 'learn_press' ),
            'author'            => 'foobla',
            'author_url'        => 'http://thimpress.com',
            'file'              => LPR_PREREQUISITES_PLUGIN_PATH . '/prerequisites.php',
            'category'          => 'courses',
            'tag'               => 'core',
            'settings-callback' => '',
        )
    );
    $prerequisite = apply_filters( 'learn_press_prerequisites', $prerequisite );
    learn_press_addon_register( 'prerequisite-add-on', $prerequisite['prerequisite-add-on'] );
}
add_action( 'learn_press_register_add_ons', 'learn_press_register_prerequisites' );
