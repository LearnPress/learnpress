<?php
class LP_Upgrader{
    private $strings;
    function __construct( $directories = array() ){
        require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
        require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
        if( ! class_exists( 'WP_Upgrader_Skin' ) ){
            require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php' );

        }

        $this->strings['bad_request'] = __( 'Invalid Data provided.', 'learnpress' );
        $this->strings['fs_unavailable'] = __( 'Could not access filesystem.', 'learnpress' );
        $this->strings['fs_error'] = __( 'Filesystem error.', 'learnpress' );
        $this->strings['fs_no_root_dir'] = __( 'Unable to locate WordPress Root directory.', 'learnpress' );
        $this->strings['fs_no_content_dir'] = __( 'Unable to locate WordPress Content directory (wp-content).', 'learnpress' );
        $this->strings['fs_no_plugins_dir'] = __( 'Unable to locate WordPress Plugin directory.', 'learnpress' );
        $this->strings['fs_no_themes_dir'] = __( 'Unable to locate WordPress Theme directory.', 'learnpress' );
        /* translators: %s: directory name */
        $this->strings['fs_no_folder'] = __( 'Unable to locate needed folder (%s).', 'learnpress' );

        $this->strings['download_failed'] = __( 'Download failed.', 'learnpress' );
        $this->strings['installing_package'] = __( 'Installing the latest version&#8230;', 'learnpress' );
        $this->strings['no_files'] = __( 'The package contains no files.', 'learnpress' );
        $this->strings['folder_exists'] = __( 'Destination folder already exists.', 'learnpress' );
        $this->strings['mkdir_failed'] = __( 'Could not create directory.', 'learnpress' );
        $this->strings['incompatible_archive'] = __( 'The package could not be installed.', 'learnpress' );

        $this->strings['maintenance_start'] = __( 'Enabling Maintenance mode&#8230;', 'learnpress' );
        $this->strings['maintenance_end'] = __( 'Disabling Maintenance mode&#8230;', 'learnpress' );

        $directories = wp_parse_args( $directories, array( WP_CONTENT_DIR ) );

        $this->fs_connect( $directories );
    }
    public function download_package( $package ) {

        if ( ! preg_match('!^(http|https|ftp)://!i', $package) && file_exists($package) ) //Local file or remote?
            return $package; //must be a local file..

        if ( empty($package) )
            return new WP_Error('no_package', $this->strings['no_package']);

        $download_file = download_url($package);

        if ( is_wp_error($download_file) )
            return new WP_Error('download_failed', $this->strings['download_failed'], $download_file->get_error_message());

        return $download_file;
    }

    public function unpack_package( $package, $delete_package = true, $name = '' ) {
        global $wp_filesystem;

        $upgrade_folder = $wp_filesystem->wp_content_dir() . 'upgrade/learnpress/';

        //Clean up contents of upgrade directory beforehand.
        $upgrade_files = $wp_filesystem->dirlist($upgrade_folder);
        if ( !empty($upgrade_files) ) {
            foreach ( $upgrade_files as $file )
                $wp_filesystem->delete($upgrade_folder . $file['name'], true);
        }

        // We need a working directory - Strip off any .tmp or .zip suffixes
        $working_dir = $upgrade_folder . ( $name ? $name : basename( basename( $package, '.tmp' ), '.zip' ) );

        // Clean up working directory
        if ( $wp_filesystem->is_dir($working_dir) )
            $wp_filesystem->delete($working_dir, true);

        // Unzip package to working directory
        $result = unzip_file( $package, $working_dir );

        // Once extracted, delete the package if required.
        if ( $delete_package )
            unlink($package);

        if ( is_wp_error($result) ) {
            $wp_filesystem->delete($working_dir, true);
            if ( 'incompatible_archive' == $result->get_error_code() ) {
                return new WP_Error( 'incompatible_archive', '', $result->get_error_data() );
            }
            return $result;
        }

        return $working_dir;
    }

    function get_plugin_info( $path, $filename = '' ){
        global $wp_filesystem;

        $plugin_file = $filename ? basename( $path ) : $filename;
        $basename = basename( basename( $plugin_file, '.tmp' ), '.zip' );

        $download_path = WP_CONTENT_DIR . '/upgrade';
        $download_file = $download_path . '/' . $plugin_file;
        $working_dir = $download_path . '/' . $basename;
        $readme = $working_dir . '/readme.txt';
        if( ! file_exists( $download_file ) && ! file_exists( $readme ) ){
            //$res = $this->fs_connect( array( $download_path ) );

            $download_file = download_url( $path );
            if( ! is_wp_error( $download_file ) ){

                // Unzip package to working directory
                $result = unzip_file( $download_file, $working_dir );
                if( $result ) $readme = $working_dir . '/readme.txt';
                unlink( $download_file );
            }
        }
        $info = false;
        if( file_exists( $readme ) ){
            require_once( dirname( __FILE__ ) . '/includes/class-readme-parse.php' );
            $parse = new WordPress_Readme_Parser( $readme );
            $info = @$parse->parse_readme_contents( file_get_contents( $readme ) );

            if( file_exists( $file = $working_dir . '/' . $basename . '.php' ) ) {
                $headers = get_plugin_data( $file );
                $info['name'] = $headers['Name'];
            }
        }
        @$wp_filesystem->delete( $working_dir, true );
        return $info;
    }

    public function fs_connect( $directories = array(), $allow_relaxed_file_ownership = false ) {
        $skin = new WP_Upgrader_Skin();

        global $wp_filesystem;

        if ( false === ( $credentials = $skin->request_filesystem_credentials( false, $directories[0], $allow_relaxed_file_ownership ) ) ) {
            return false;
        }

        if ( ! WP_Filesystem( $credentials, $directories[0], $allow_relaxed_file_ownership ) ) {
            $error = true;
            if ( is_object($wp_filesystem) && $wp_filesystem->errors->get_error_code() )
                $error = $wp_filesystem->errors;
            // Failed to connect, Error and request again
            //$this->skin->request_filesystem_credentials( $error, $directories[0], $allow_relaxed_file_ownership );
            return false;
        }

        if ( ! is_object($wp_filesystem) )
            return new WP_Error('fs_unavailable', $this->strings['fs_unavailable'] );

        if ( is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->get_error_code() )
            return new WP_Error('fs_error', $this->strings['fs_error'], $wp_filesystem->errors);

        foreach ( (array)$directories as $dir ) {
            switch ( $dir ) {
                case ABSPATH:
                    if ( ! $wp_filesystem->abspath() )
                        return new WP_Error('fs_no_root_dir', $this->strings['fs_no_root_dir']);
                    break;
                case WP_CONTENT_DIR:
                    if ( ! $wp_filesystem->wp_content_dir() )
                        return new WP_Error('fs_no_content_dir', $this->strings['fs_no_content_dir']);
                    break;
                case WP_PLUGIN_DIR:
                    if ( ! $wp_filesystem->wp_plugins_dir() )
                        return new WP_Error('fs_no_plugins_dir', $this->strings['fs_no_plugins_dir']);
                    break;
                case get_theme_root():
                    if ( ! $wp_filesystem->wp_themes_dir() )
                        return new WP_Error('fs_no_themes_dir', $this->strings['fs_no_themes_dir']);
                    break;
                default:
                    if ( ! $wp_filesystem->find_folder($dir) )
                        return new WP_Error( 'fs_no_folder', sprintf( $this->strings['fs_no_folder'], esc_html( basename( $dir ) ) ) );
                    break;
            }
        }
        return true;
    } //end fs_connect();
}