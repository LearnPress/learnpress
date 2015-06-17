<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/**
 * Print all tab
 *
 * @param $current
 */
function learn_press_print_add_on_tab( $current ) {
    $active = ( empty( $current ) || 'all' == $current ) ? 'nav-tab-active' : '';
    ?>
    <h2 class="nav-tab-wrapper">
    <a class="nav-tab <?php echo $active; ?>" href="<?php echo admin_url( 'admin.php?page=learn_press_add_ons' ); ?>"><?php _e( 'All', 'learn_press' ); ?></a>
    <?php do_action('learn_press_print_add_on_page_tab', $current ) ?>
    </h2>
    <?php
}

/**
 * Print enabled add-ons tab
 *
 * @param $current
 */
function learn_press_print_enabled_tab( $current ) {
    $active = ( empty( $current ) || 'enabled' == $current ) ? 'nav-tab-active' : '';
    ?><a class="nav-tab <?php echo $active; ?>" href="<?php echo admin_url( 'admin.php?page=learn_press_add_ons&tab=enabled' ); ?>"><?php _e( 'Enabled', 'learn_press' ); ?></a>
    <?php
}

/**
 * Print disabled add-ons tab
 *
 * @param $current
 */
function learn_press_print_disabled_tab( $current ) {
    $active = ( empty( $current ) || 'disabled' == $current ) ? 'nav-tab-active' : '';
    ?><a class="nav-tab <?php echo $active; ?>" href="<?php echo admin_url( 'admin.php?page=learn_press_add_ons&tab=disabled' ); ?>"><?php _e( 'Disabled', 'learn_press' ); ?></a>
    <?php
}

/**
 * Print get more tab
 *
 * @param $current
 */
function learn_press_print_get_more_tab( $current ) {
    $active = ( empty( $current ) || 'get_more' == $current ) ? 'nav-tab-active' : '';
    ?><a class="nav-tab <?php echo $active; ?>" href="<?php echo admin_url( 'admin.php?page=learn_press_add_ons&tab=get_more' ); ?>"><?php _e( 'Get More', 'learn_press' ); ?></a>
<?php
}

/**
 * @param $slug
 * @param $params
 *
 * @return WP_Error
 */
function learn_press_addon_register( $slug, $params ) {

    $name         = empty( $params['name'] )        ? false   : $params['name'];
    $author       = empty( $params['author'] )      ? false   : $params['author'];
    $author_url   = empty( $params['author_url'] )  ? false   : $params['author_url'];
    $description  = empty( $params['description'] ) ? ''      : $params['description'];
    $file         = empty( $params['file'] )        ? false   : $params['file'];
    $options      = empty( $params['options'] )     ? array() : (array) $params['options'];
    $basename     = empty( $params['basename'] )    ? false   : $params['basename'];


    // Basic Validation
    $slug   = empty( $slug )        ? false : sanitize_key( $slug );
    $name   = empty( $name )        ? false : sanitize_text_field( $name );
    $author = empty( $author )      ? false : sanitize_text_field( $author );
    $file   = file_exists( $file )  ? $file : false;

    if ( ! $slug  )
        return new WP_Error( 'learn_press_add_registration_error', __( 'All LearnPress Add-ons require a slug parameter.', 'learn_press' ) );

    if ( ! $name )
        return new WP_Error( 'learn_press_add_registration_error', __( 'All LearnPress Add-ons require a name parameter.', 'learn_press' ) );

    if ( ! $file )
        return new WP_Error( 'learn_press_add_registration_error', __( 'All LearnPress Add-ons require a file parameter.', 'learn_press' ) );

    $allowed_keys = array( 'category', 'tag', 'supports', 'labels', 'settings-callback', 'icon', 'wizard-icon' );

    foreach ( $params as $key => $value )
        if ( in_array( $key, $allowed_keys ) )
            $options[$key] = $value;

    if ( empty( $options['category'] ) )
        $options['category'] = 'other';

    // Add the add-on to our Global
    $GLOBALS['learn_press']['add_ons']['registered'][$slug] = apply_filters( 'learn_press_register_addon', array(
        'slug' 			=> $slug,
        'name' 			=> $name,
        'author' 		=> $author,
        'author_url' 	=> $author_url,
        'description' 	=> $description,
        'file' 			=> $file,
        'options' 		=> $options,
        'basename'      => $basename,
    ), $params );

}

/**
 * @param array $options
 *
 * @return mixed|string|void
 */
function learn_press_get_add_ons( $options=array() ) {
    $plugins = array();
    if( ! function_exists( 'get_plugins' ) ){
        require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
    }
    $all_plugins = get_plugins();


    if( $all_plugins ){
        foreach( $all_plugins as $plugin_file => $plugin_data ){
            if( ! empty( $plugin_data['Tags'] ) && preg_match( '!learnpress!', $plugin_data['Tags'] ) ){
                $plugins[$plugin_file] = _get_plugin_data_markup_translate( $plugin_file, $plugin_data, false, true );

                $plugins[$plugin_file]['Icons'] = array(
                    '2x' => LPR_PLUGIN_URL . '/assets/images/icon-128x128.png',
                    '1x' => LPR_PLUGIN_URL . '/assets/images/icon-128x128.png'
                );

                $plugins[$plugin_file]['rating'] = 0;
                $plugins[$plugin_file]['num_ratings'] = 0;
                $plugins[$plugin_file]['tested'] = null;
                $plugins[$plugin_file]['requires'] = null;
                $plugins[$plugin_file]['active_installs'] = 0;
                $plugins[$plugin_file]['last_updated'] = gmdate ( 'Y-m-d h:iA', strtotime('last Friday', time() ) ) . ' GMT';
            }
            //print_r($plugins[$plugin_file]);
        }
    }

    //learn_press_get_plugin_data( $plugins );

    return $plugins;

    $defaults = array(
        'show_required' => true,
    );
    $options = wp_parse_args( $options, $defaults );

    if ( empty( $GLOBALS['learn_press']['add_ons']['registered'] ) )
        return array();
    else
        $add_ons = $GLOBALS['learn_press']['add_ons']['registered'];

    // Loop through addons and filter out required if not set to show.
    foreach ( $add_ons as $key => $addon ) {
        if ( ! $options['show_required'] && ! empty( $addon['options']['tag'] ) && 'required' === $addon['options']['tag'] )
            unset( $add_ons[$key] );

    }

    ksort( $add_ons );
    return apply_filters( 'learn_press_get_addons', $add_ons, $options );
}

function learn_press_get_installed_plugin_slugs() {
    $slugs = array();

    $plugin_info = get_site_transient( 'update_plugins' );
    if ( isset( $plugin_info->no_update ) ) {
        foreach ( $plugin_info->no_update as $plugin ) {
            $slugs[] = $plugin->slug;
        }
    }

    if ( isset( $plugin_info->response ) ) {
        foreach ( $plugin_info->response as $plugin ) {
            $slugs[] = $plugin->slug;
        }
    }

    return $slugs;
}

/**
 * @param $slug
 *
 * @return mixed|void
 */
function learn_press_get_add_on( $slug ) {
    if ( $add_ons = learn_press_get_add_ons() ) {
        if ( ! empty( $add_ons[$slug] ) )
            return $add_ons[$slug];
    }
    return apply_filters( 'learn_press_get_addon', false, $slug, $add_ons );
}

/**
 * @param $add_ons
 */
function learn_press_load_add_ons( $add_ons ) {
    $enabled_addons = learn_press_get_enabled_add_ons();

    // Init all enabled addons
    foreach( (array) $add_ons as $slug => $params ) {
        if( isset( $enabled_addons[$slug] ) ) {
            if ( ! empty( $params['file'] ) && is_file( $params['file'] ) ) {
                include_once( $params['file'] );
            }
        }
    }
}

/**
 * @param array $options
 *
 * @return mixed|void
 */
function learn_press_get_enabled_add_ons( $options=array() ) {
    $defaults = array(
        'show_required' => true,
        'break_cache'   => false,
    );
    $options = wp_parse_args( $options, $defaults );

    // Grab all registered add-ons
    $registered = learn_press_get_add_ons();
    $enabled = array();

    // Grab enabled add-ons from options
    if ( false === $enabled_addons = get_option( 'enabled_add_ons' ) )
        $enabled_addons = array();

    // Set each enabled with registered params
    if ( ! empty( $enabled_addons ) ) {
        foreach ( $enabled_addons as $addon => $params ) {
            if ( ! empty( $registered[$addon] ) ) {
                if ( $options['show_required'] )
                    $enabled[$addon] = $registered[$addon];
                else if ( empty( $registered[$addon]['options']['tag'] ) || 'required' !== $registered[$addon]['options']['tag'] )
                    $enabled[$addon] = $registered[$addon];
            }
        }
    }

    ksort( $enabled );
    return apply_filters( 'learn_press_get_enabled_addons', empty( $enabled ) ? array() : $enabled, $options );
}

/**
 * @param array $options
 *
 * @return mixed|void
 */
function learn_press_get_disabled_add_ons( $options=array() ) {
    // Grab all registered add-ons
    $registered = learn_press_get_add_ons();
    $disabled = array();

    // Grab enabled add-ons from options
    if ( false === $enabled_addons = get_option( 'enabled_add_ons' ) )
        $enabled_addons = array();

    // Loop through registered addons
    foreach ( $registered as $slug => $params )
        if ( ! in_array( $slug, array_keys( $enabled_addons ) ) )
            $disabled[$slug] = $params;

    if ( ! empty( $disabled ) )
        ksort( $disabled );

    return apply_filters( 'it_exchange_get_disabled_addons', empty( $disabled ) ? array() : $disabled, $options );
}

function learn_press_get_more_add_ons() {

}

/**
 * @param $slug
 *
 * @return bool
 */
function learn_press_is_core_addon( $slug ) {
    $addon = learn_press_get_add_on( $slug );
    if ( empty( $addon['file'] ) )
        return false;

    $addon_file = str_replace (array( "\\", '/'), '', $addon['file'] );
    $core_path = str_replace( array( '\\', '/'), '', LPR_PLUGIN_PATH) . 'inccore-addons';
    // Don't add a filter here.

    // if $addon_file contains $core_path that meaning it is core "tuber banana" :)
    return strpos( $addon_file, $core_path ) !== false;

}

/**
 * @param $add_on
 *
 * @return mixed|void
 */
function learn_press_enable_add_on( $add_on ) {
    $registered = learn_press_get_add_ons();
    $enabled_add_ons = learn_press_get_enabled_add_ons( array( 'break_cache' => true ) );
    $success = false;

    if ( isset( $registered[$add_on] ) && ! isset( $enabled_add_ons[$add_on] ) ) {
        $enabled_add_ons[$add_on] = $registered[$add_on];
        if ( update_option( 'enabled_add_ons', $enabled_add_ons ) ) {
            include( $registered[$add_on]['file'] );
            echo $registered[$add_on]['file'];
            do_action( 'learn_press_add_on_enabled', $registered[$add_on] );
            flush_rewrite_rules();
            $success = true;
        }
    }
    return apply_filters( 'learn_press_enable_addon', $success, $add_on );
};

/**
 * @param $add_on_slug
 *
 * @return mixed|void
 */
function learn_press_is_addon_enabled( $add_on_slug ) {
    $enabled = array_keys( learn_press_get_enabled_add_ons( array( 'break_cache' => true ) ) );
    $success = false;

    if ( in_array( $add_on_slug, $enabled ) )
        $success = true;

    return apply_filters( 'learn_press_is_addon_enabled', $success, $add_on_slug );
}

/**
 * @param $add_on
 *
 * @return bool
 */
function learn_press_disable_add_on( $add_on ) {    
    $registered = learn_press_get_add_ons();
    $enabled_addons = learn_press_get_enabled_add_ons( array( 'break_cache' => true ) );
    $success = false;

    if ( ! empty( $enabled_addons[$add_on] ) ) {
        unset( $enabled_addons[$add_on] );
        if ( update_option( 'enabled_add_ons', $enabled_addons ) ) {
            if ( ! empty( $registered[$add_on] ) )
                do_action( 'learn_press_add_on_disabled', $registered[$add_on] );
            flush_rewrite_rules();
            $success = true;
        }
    }

    return $success;
}

function learn_press_get_core_add_ons(){
    $add_ons = array(
        'bbpress-add-on' => array(
            'name'              => __( 'bbPress Integration', 'learn_press' ),
            'description'       => sprintf( __( 'Using the forum for courses provided by bbPress.%s', 'learn_press' ), $m ),
            'author'            => 'foobla',
            'author_url'        => 'http://thimpress.com',
            'file'              => LPR_BBP_PATH . '/init.php',
            'category'          => 'courses',
            'tag'               => 'core',
            'settings-callback' => '',
        ),
        'buddypress-add-on' => array(
            'name'              => __( 'BuddyPress Integration', 'learn_press' ),
            'description'       => sprintf( __( 'Using the profile system provided by BuddyPress.%s', 'learn_press' ), $m ),
            'author'            => 'foobla',
            'author_url'        => 'http://thimpress.com',
            'file'              => LPR_BP_PATH . '/init.php',
            'category'          => 'courses',
            'tag'               => 'core',
            'settings-callback' => '',
        ),
        'learn-press-certificate' => array(
            'name'              => __( 'Certificate', 'learn_press' ),
            'description'       => __( 'Allow create a certificate for student after they finish a course', 'learn_press' ),
            'author'            => 'foobla',
            'author_url'        => 'http://thimpress.com',
            'file'              => LPR_CERTIFICATE_PATH . '/incs/load.php',
            'category'          => 'courses',
            'tag'               => 'core',
            'settings-callback' => '',
        ),
        array(
            'collections-add-on' => array(
                'name'              => __( 'Courses Collection', 'learn_press' ),
                'description'       => __( 'Collecting related courses into one collection by administrator', 'learn_press' ),
                'author'            => 'foobla',
                'author_url'        => 'http://thimpress.com',
                'file'              => LPR_COLLECTIONS_PATH . '/init.php',
                'category'          => 'courses',
                'tag'               => 'core',
                'settings-callback' => '',
            )
        ),
        'course-review-add-on' => array(
            'name'              => __( 'Course Review', 'learn_press' ),
            'description'       => __( 'Adding review for course ', 'learn_press' ),
            'author'            => 'foobla',
            'author_url'        => 'http://thimpress.com',
            'file'              => LPR_COURSE_REVIEW_PATH . '/init.php',
            'category'          => 'courses',
            'tag'               => 'core',
            'settings-callback' => '',
        ),
        array(
            'name'              => __( 'Export/Import', 'learn_press' ),
            'description'       => __( 'Export and Import your courses with all lesson and quiz in easiest way', 'learn_press' ),
            'author'            => 'foobla',
            'author_url'        => 'http://thimpress.com',
            'file'              => LPR_EXPORT_IMPORT_PATH . '/init.php',
            'category'          => 'courses',
            'tag'               => 'core',
            'settings-callback' => '',
        ),
        'prerequisite-add-on' => array(
            'name'              => __( 'Prerequisite Courses', 'learn_press' ),
            'description'       => __( 'Adding prerequisite course when add new a course ', 'learn_press' ),
            'author'            => 'foobla',
            'author_url'        => 'http://thimpress.com',
            'file'              => LPR_PREREQUISITES_PLUGIN_PATH . '/init.php',
            'category'          => 'courses',
            'tag'               => 'core',
            'settings-callback' => '',
        ),
        array(
            'name'              => __( 'Stripe', 'learn_press' ),
            'description'       => __( 'Make the payment with Stripe', 'learn_press' ),
            'author'            => 'foobla',
            'author_url'        => 'http://thimpress.com',
            'file'              => LPR_STRIPE_PATH . '/incs/load.php',
            'category'          => 'payment',
            'tag'               => '',
            'settings-callback' => '',
        ),
        'wishlist-add-on' => array(
            'name'              => __( 'Courses Wishlist', 'learn_press' ),
            'description'       => __( 'Wishlist feature', 'learn_press' ),
            'author'            => 'foobla',
            'author_url'        => 'http://thimpress.com',
            'file'              => LPR_WISHLIST_PATH . '/init.php',
            'category'          => 'courses',
            'tag'               => 'core',
            'settings-callback' => '',
        ),
        array(
            'name'              => __( 'WooCommerce Payment', 'learn_press' ),
            'description'       => sprintf( __( 'Using the payment system provided by WooCommerce.%s', 'learn_press' ), $m ),
            'author'            => 'foobla',
            'author_url'        => 'http://thimpress.com',
            'file'              => LPR_WOO_PAYMENT_ADDON . '/init.php',
            'category'          => 'courses',
            'tag'               => 'core',
            'settings-callback' => ''
        )
    );

    $add_ons = apply_filters( 'learn_press_core_add_ons', $add_ons );

    return $add_ons;
}

function learn_press_add_on_header( $headers ){
    $headers['LearnPress'] = 'LearnPress';
    $headers['Tags']        = 'Tags';
    return $headers;
}
add_filter( 'extra_plugin_headers', 'learn_press_add_on_header' );

function learn_press_get_plugin_data( $plugins ){
    global $wp_version;
    //$plugins = get_plugins();
    $translations = wp_get_installed_translations( 'plugins' );

    $active  = get_option( 'active_plugins', array() );
    $current = get_site_transient( 'update_plugins' );

    $to_send = compact( 'plugins', 'active' );

    $locales = array( get_locale() );

    $options = array(
        'timeout' => 30,
        'body' => array(
            'plugins'      => wp_json_encode( $to_send ),
            'translations' => wp_json_encode( $translations ),
            'locale'       => wp_json_encode( $locales ),
            'all'          => wp_json_encode( true ),
        ),
        'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
    );

    /*if ( $extra_stats ) {
        $options['body']['update_stats'] = wp_json_encode( $extra_stats );
    }*/

    $url = $http_url = 'http://api.wordpress.org/plugins/update-check/1.1/';
    if ( $ssl = wp_http_supports( array( 'ssl' ) ) )
        $url = set_url_scheme( $url, 'https' );

    $raw_response = wp_remote_post( $url, $options );
    if ( $ssl && is_wp_error( $raw_response ) ) {
        trigger_error( __( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="https://wordpress.org/support/">support forums</a>.' ) . ' ' . __( '(WordPress could not establish a secure connection to WordPress.org. Please contact your server administrator.)' ), headers_sent() || WP_DEBUG ? E_USER_WARNING : E_USER_NOTICE );
        $raw_response = wp_remote_post( $http_url, $options );
    }
    $response = json_decode( wp_remote_retrieve_body( $raw_response ), true );
    //print_r($response);
}

/**
 * @return array
 */
function get_installed_plugin_slugs(){
    $slugs = array();

    $plugin_info = get_site_transient('update_plugins');
    if (isset($plugin_info->no_update)) {
        foreach ($plugin_info->no_update as $plugin) {
            $slugs[] = $plugin->slug;
        }
    }

    if (isset($plugin_info->response)) {
        foreach ($plugin_info->response as $plugin) {
            $slugs[] = $plugin->slug;
        }
    }

    return $slugs;
}
/**
 * Query the list of add-ons from wordpress.org with keyword 'learnpress'
 * This requires have a keyword named 'learnpress' in plugin header Tags
 *
 * @param array
 * @return array
 */
function learn_press_get_add_ons_from_wp( $args = null ){
    $args = wp_parse_args(
        $args,
        array(
            'search'    => 'learnpress',
            'include'   => null,
            'exclude'   => null
        )
    );
    // the number of plugins on each page queried, when we can reach to this figure?
    $per_page   = 300;
    $paged      = 1;
    $query_args = array(
        'page' => $paged,
        'per_page' => $per_page,
        'fields' => array(
            'last_updated' => true,
            'icons' => true,
            'active_installs' => true
        ),
        // Send the locale and installed plugin slugs to the API so it can provide context-sensitive results.
        'locale' => get_locale(),
        'installed_plugins' => get_installed_plugin_slugs(),
        'search' => $args['search']
    );

    $transient_key = "learn_press_add_ons" . md5( serialize( $query_args ) );

    if( $plugins = get_transient( $transient_key ) ){
        $this->items = $plugins;
        return;
    }

    $api = plugins_api( 'query_plugins', $query_args );

    if ( is_wp_error( $api ) ) {
        $this->error = $api;
        return;
    }
    if( is_array( $api->plugins ) ) {
        // filter plugins with tag contains 'learnpress'
        $plugins = array_filter( $api->plugins, create_function( '$plugin', 'return $plugin->slug != \'learnpress\';' ));
        set_transient( $transient_key, $plugins, 60 * 5 );
    }
    return $plugins;
}

function learn_press_install_and_active_add_on( $slug ){

    include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); //for plugins_api..
    if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    }
    $api    = plugins_api('plugin_information', array('slug' => $slug, 'fields' => array('sections' => false) ) );
    $title  = sprintf( __('Installing Plugin: %s'), $api->name . ' ' . $api->version );
    $nonce  = 'install-plugin_' . $slug;
    $url    = 'update.php?action=install-plugin&plugin=' . urlencode( $slug );

    $plugin = learn_press_plugin_basename_from_slug( $slug );
    $return = array();
    if ( ! learn_press_is_plugin_install( $plugin ) ) {

        $upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( compact('title', 'url', 'nonce', 'plugin', 'api') ) );
        $result = $upgrader->install( $api->download_link );

        if ( ! is_wp_error( $result ) ) {
            $return['status'] = 'not_install';
            $return['status_text'] = __( 'Not install', 'learn_press' );
        } else {
            $return = $result;
            $return['status'] = 'installed';
            $return['status_text'] = __( 'Installed', 'learn_press' );
        }
    }

    if ( learn_press_is_plugin_install( $plugin ) ) {
        activate_plugin($plugin, false, is_network_admin());
        // ensure that plugin is enabled
        $is_activate = is_plugin_active( $plugin );
        $return['status'] = $is_activate ? 'activate' : 'deactivate';
        $return['status_text'] = $is_activate ? __( 'Enabled', 'learn_press' ) : __( 'Disabled', 'learn_press' );
    }
    return $return;
}

function learn_press_upgrader_post_install( $a, $b, $result){
    if( ! empty( $_REQUEST['learnpress'] ) && $_REQUEST['learnpress'] == 'active' ) {
        if( is_wp_error( $result ) ) {

        }else{
            $slug = $_REQUEST['plugin'];
            $plugin = learn_press_plugin_basename_from_slug( $slug );


            activate_plugin($plugin, false, is_network_admin());
            // ensure that plugin is enabled
            $is_activate = is_plugin_active( $plugin );
            $result['status'] = $is_activate ? 'activate' : 'deactivate';
            $result['status_text'] = $is_activate ? __('Enabled', 'learn_press') : __('Disabled', 'learn_press');
        }
        learn_press_send_json($result);
    }
}
add_filter( 'upgrader_post_install', 'learn_press_upgrader_post_install', 10, 3 );