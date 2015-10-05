<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LPR_Autoloader {

    /**
     * Path to the includes directory
     * @var string
     */
    private $include_path = '';

    /**
     * The Constructor
     */
    public function __construct() {
        if ( function_exists( "__autoload" ) ) {
            spl_autoload_register( "__autoload" );
        }

        spl_autoload_register( array( $this, 'autoload' ) );

        $this->include_path = untrailingslashit( LPR_PLUGIN_PATH ) . '/inc/';
    }

    /**
     * Take a class name and turn it into a file name
     * @param  string $class
     * @return string
     */
    private function get_file_name_from_class( $class ) {
        return 'class.' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
    }

    /**
     * Include a class file
     * @param  string $path
     * @return bool successful or not
     */
    private function load_file( $path ) {
        if ( $path && is_readable( $path ) ) {
            include_once( $path );
            return true;
        }
        return false;
    }

    /**
     * Auto-load WC classes on demand to reduce memory consumption.
     *
     * @param string $class
     */
    public function autoload( $class ) {
        $class = strtolower( $class );
        $file  = $this->get_file_name_from_class( $class );
        $path  = '';

        // payment gateways
        if ( strpos( $class, 'lpr_gateway_' ) === 0 ) {
            $path = $this->include_path . 'gateways/' . substr( str_replace( '_', '-', $class ), 12 ) . '/';
        }

        // meta box fields
        if( strpos( $class, 'rwmb_' ) === 0 ){
            $file = 'class.' . substr( str_replace( '_', '-', $class ), 5, strlen( $class ) - 11 ) . '.php';
            $path = $this->include_path . 'admin/meta-boxes/fields/';
        }

        //if ( empty( $path ) || ( ! $this->load_file( $path . $file ) && strpos( $class, 'lpr_' ) === 0 ) ) {
            $this->load_file( $path . $file );
        //}
    }
}

new LPR_Autoloader();
