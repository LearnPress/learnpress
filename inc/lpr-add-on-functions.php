<?php


if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Initiate add on page
 *
 */


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

//    // Possibly filter by category
//    if ( ! empty( $options['category'] ) )
//        $add_ons = learn_press_filter_addons_by_category( $add_ons, $options['category'] );

    ksort( $add_ons );
    return apply_filters( 'learn_press_get_addons', $add_ons, $options );
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

//    if ( ! empty( $options['category'] ) )
//        $enabled = learn_press_filter_addons_by_category( $enabled, $options['category'] );

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

//    if ( ! empty( $options['category'] ) )
//        $disabled = it_exchange_filter_addons_by_category( $disabled, $options['category'] );

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
//            update_option( '_learn_press-flush-rewrites', true );
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
//            update_option( '_learn-press-flush-rewrites', true );
            flush_rewrite_rules();
            $success = true;
        }
    }

    return $success;
}