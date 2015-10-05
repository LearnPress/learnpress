<?php
/**
 * @file
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

global $learn_press_add_ons;

$learn_press_add_ons['bundle_activate'] = array(
    'learnpress-course-review',
    'learnpress-import-export',
    'learnpress-prerequisites-courses',
    'learnpress-wishlist'
);
$wp_plugins = learn_press_get_add_ons_from_wp( array( 'exclude' => $learn_press_add_ons['bundle_activate'] ) );
if( $wp_plugins ) {
    $wp_plugins = array_map( create_function( '$a', 'return $a->slug;' ), $wp_plugins );
    $learn_press_add_ons['more'] = $wp_plugins;
}else{
    $learn_press_add_ons['more'] = array();
}

require_once( LPR_PLUGIN_PATH . '/inc/admin/class-lpr-upgrader.php');
/**
 * Default tabs for add ons page
 *
 * @return array
 */
function learn_press_get_add_on_tabs(){
    global $learn_press_add_ons;
    $all_plugins = learn_press_get_add_ons();
    $defaults = array(
        'all'       => array(
            'text'  => sprintf( __( 'All <span class="count">(%s)</span>', 'learn_press' ), sizeof( $all_plugins ) ),
            'class' => '',
            'url'   => ''
        ),
        'bundle_activate'  => array(
            'text'  => sprintf( __( 'Bundle Activate <span class="count">(%s)</span>', 'learn_press' ), sizeof( $learn_press_add_ons['bundle_activate'] ) ),__( '', 'learn_press' ),
            'class' => '',
            'url'   => ''
        ),
        'more'  => array(
            'text'  => sprintf( __( 'Get more <span class="count">(%s)</span>', 'learn_press' ), sizeof( $learn_press_add_ons['more'] ) ),__( '', 'learn_press' ),
            'class' => '',
            'url'   => ''
        )
    );

    return apply_filters( 'learn_press_add_on_tabs', $defaults );
}

/**
 * Add-on page
 */
function learn_press_add_ons_page() {
    $current = isset( $_GET['tab'] ) ? $_GET['tab'] : '';
    $disable_add_on = isset( $_GET['learn-press-disable-addon'] ) ? $_GET['learn-press-disable-addon'] : false;
    $enable_add_on = isset( $_GET['learn-press-enable-addon'] ) ? $_GET['learn-press-enable-addon'] : false;

    if( $enable_add_on ) {
        learn_press_enable_add_on( $enable_add_on );
    } else if( $disable_add_on ) {
        learn_press_disable_add_on( $disable_add_on );
    }
?>
<div id="learn-press-add-ons-wrap" class="wrap">
    <!-- Add on top page -->
    <h2><?php echo __('LearnPress Add-ons', 'learn_press'); ?></h2>
    <p class="top-description"><?php _e( 'Add-ons are features that you can add or remove depending on your needs', 'learn_press' ); ?></p>
    <!-- Tab -->
    <ul class="subsubsub">
        <?php
        do_action( 'learn_press_add_ons_before_head_tab' );
        if( $tabs = learn_press_get_add_on_tabs() ){
            if( empty( $tabs[ $current ] ) ){
                $tab_ids = array_keys( $tabs );
                $current = reset( $tab_ids );
            }
            $links = array();
            foreach( $tabs as $id => $args ){
                $class = array();
                if( ! empty( $args['class' ] ) ){
                    if( is_array( $args['class'] ) ) {
                        $class = array_merge( $class, $args['class'] );
                    }else{
                        $class[] = $args['class'];
                    }
                }

                $class = join( ' ', $class );
                if( ! empty( $args['url'] ) ) {
                    $url = $args['url'];
                }else{
                    $url = admin_url( 'admin.php?page=learn_press_add_ons&tab=' . $id );
                }
                $text = $args['text'];

                $links[] = sprintf( '<li class="%s"><a href="%s" class="%s">%s</a></li>', $class, $url, ( $current == $id ? 'current' : '' ), $text );
            }
            echo join( '|', $links );
        }
        do_action( 'learn_press_add_ons_after_head_tab' );
        ?>
    </ul>
    <div class="clear"></div>
    <?php do_action( 'learn_press_add_ons_content_tab_' . $current, $current );?>
    <div id="learn-press-add-on-state-changed-message"><?php _e( 'One or more plugins state has changed to activate/deactivate. Click \'Apply\' button to update', 'learn_press' );?></div>
    <?php
    return;
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
    return;
?>
<div id="lpr-add-ons-wrapper">


</div>
   <?php
}

function learn_press_add_ons_content_tab_all( $current ){
    $add_ons = learn_press_get_add_ons( array( 'show_required' => false ) );
    learn_press_output_add_ons_list( $add_ons, $current );
}
add_action( 'learn_press_add_ons_content_tab_all', 'learn_press_add_ons_content_tab_all' );

function learn_press_add_ons_content_tab_enabled( $current ){
    $add_ons = learn_press_get_enabled_add_ons( array( 'show_required' => false ) );
    learn_press_output_add_ons_list( $add_ons, $current );
}
add_action( 'learn_press_add_ons_content_tab_enabled', 'learn_press_add_ons_content_tab_enabled' );

function learn_press_add_ons_content_tab_disabled( $current ){
    $add_ons = learn_press_get_disabled_add_ons( array( 'show_required' => false ) );
    learn_press_output_add_ons_list( $add_ons, $current );
}
add_action( 'learn_press_add_ons_content_tab_disabled', 'learn_press_add_ons_content_tab_disabled' );

function learn_press_add_ons_content_tab_more( $current ){
    global $learn_press_add_ons;
    require_once LPR_PLUGIN_PATH . '/inc/admin/class-lpr-plugin-install-list-table.php';
    $list_table = new LPR_Plugin_Install_List_Table();
    if( 'more' == $current ) {
        $list_table->prepare_items(array('exclude' => $learn_press_add_ons['bundle_activate']));
    }else{
        $list_table->prepare_items();
    }
    $total_pages = $list_table->get_pagination_arg( 'total_pages' );
    echo '<div class="learn-press-add-ons">';
    $list_table->display();

    if( 'bundle_activate' == $current ){
        echo '<button class="button" type="button" id="learn-press-bundle-activate-add-ons">' . __( 'Install and/or activate all', 'learn_press' ) . '</button>';
    }

    echo '</div>';
    ?>
    <script type="text/html" id="tmpl-add-on-install-error">
        <div class="error">
            <p><?php _e( 'Plugin <i>\'{{data.name}}\'</i> install failed! Please try again' );?></p>
        </div>
    </script>
    <script type="text/html" id="tmpl-add-on-install-success">
        <div class="updated">
            <p><?php _e( 'Plugin <i>\'{{data.name}}\'</i> install completed!' );?></p>
        </div>
    </script>
    <?php
}
add_action( 'learn_press_add_ons_content_tab_more', 'learn_press_add_ons_content_tab_more' );
add_action( 'learn_press_add_ons_content_tab_bundle_activate', 'learn_press_add_ons_content_tab_more' );

function learn_press_output_add_ons_list( $add_ons, $tab = '' ){

    if( ! is_array( $add_ons ) ) {
        return false;
    }

    echo '<ul class="learn-press-add-ons ' . $tab . '">';
    foreach( $add_ons as $file => $add_on ) {
        if ( ! empty( $add_on['options']['tag'] ) && 'required' === $add_on['options']['tag'] ){
           continue;
        }

        $action_links = array();
        if( is_plugin_active( $file ) ){
            $action_links[] = '<input data-state="enabled" type="checkbox" class="lpr-fancy-checkbox" checked="checked" data-plugin="' . $file . '" data-url="'.wp_nonce_url( "plugins.php?action=deactivate&plugin={$file}", 'deactivate-plugin_' . $file ).'" />';
        }else{
            $action_links[] = '<input data-state="disabled" type="checkbox" class="lpr-fancy-checkbox" data-plugin="' . $file . '" data-url="'.wp_nonce_url( "plugins.php?action=activate&plugin={$file}", 'activate-plugin_' . $file ).'" />';
        }

        $date_format = 'M j, Y @ H:i';
        $last_updated_timestamp = strtotime( $add_on['last_updated'] );

        ?>
        <li class="plugin-card plugin-card-learnpress">
			<div class="plugin-card-top">
				<a href="" class="thickbox plugin-icon"><img src="<?php echo $add_on['Icons']['2x'];?>"></a>
				<div class="name column-name">
					<h4><a href="" class="thickbox"><?php echo $add_on['Name'];?></a></h4>
				</div>
				<div class="action-links">
					<?php
                    if ( $action_links ) {
                        echo '<ul class="plugin-action-buttons"><li>' . implode( '</li><li>', $action_links ) . '</li></ul>';
                    }
                    ?>
                </div>
				<div class="desc column-description">
					<p><?php echo $add_on['Description'];?></p>
					<p class="authors"><?php printf( __( '<cite>By <a href="%s">%s</a></cite>', 'learn_press'  ), $add_on['AuthorURI'], $add_on['Author'] );?></p>
				</div>
			</div>
			<div class="plugin-card-bottom">
				<div class="vers column-rating">
                        <?php wp_star_rating( array( 'rating' => $add_on['rating'], 'type' => 'percent', 'number' => $add_on['num_ratings'] ) ); ?>
                        <span class="num-ratings">(<?php echo number_format_i18n( $add_on['num_ratings'] ); ?>)</span>
                    </div>
                    <div class="column-updated">
                        <strong><?php _e( 'Last Updated:', 'learn_press'  ); ?></strong> <span title="<?php echo esc_attr( date_i18n( $date_format, $last_updated_timestamp ) ); ?>">
						<?php printf( __( '%s ago', 'learn_press'  ), human_time_diff( $last_updated_timestamp ) ); ?>
					    </span>
                    </div>
                    <div class="column-downloaded">
                        <?php
                        if ( $add_on['active_installs'] >= 1000000 ) {
                            $active_installs_text = _x( '1+ Million', 'Active plugin installs' );
                        } else {
                            $active_installs_text = number_format_i18n( $add_on['active_installs'] ) . '+';
                        }
                        printf( __( '%s Active Installs', 'learn_press'  ), $active_installs_text );
                        ?>
                    </div>
                    <div class="column-compatibility">
                        <?php
                        if ( ! empty( $add_on['tested'] ) && version_compare( substr( $GLOBALS['wp_version'], 0, strlen( $add_on['tested'] ) ), $add_on['tested'], '>' ) ) {
                            echo '<span class="compatibility-untested">' . __( 'Untested with your version of WordPress', 'learn_press'  ) . '</span>';
                        } elseif ( ! empty( $plugin['requires'] ) && version_compare( substr( $GLOBALS['wp_version'], 0, strlen( $add_on['requires'] ) ), $add_on['requires'], '<' ) ) {
                            echo '<span class="compatibility-incompatible">' . __( '<strong>Incompatible</strong> with your version of WordPress', 'learn_press'  ) . '</span>';
                        } else {
                            echo '<span class="compatibility-compatible">' . __( '<strong>Compatible</strong> with your version of WordPress', 'learn_press'  ) . '</span>';
                        }
                        ?>
                    </div>
			</div>
        </li>

        <?php
        continue;
        ?>
        <li>
            <div class="add-on-inner">
                <div class="add-on-thumbnail">
                    <?php if( !empty( $add_on['preview'] ) ){?>
                        <img src="<?php echo $add_on['preview'];?>">
                    <?php }else{?>
                        <img src="<?php echo admin_url( 'admin-ajax.php?action=learnpress_dummy_image' );?>&text=<?php echo $add_on['name'];?>&color=777777&width=400&height=250&padding=40">
                    <?php }?>
                    <div class="add-on-overlay"></div>
                    <p class="add-on-description"><?php echo $add_on['description'] ?></p>
                </div>
                <div class="add-on-info">
                    <h3> <?php echo $add_on['name'] ?><?php echo $is_core ? '<span>'.__('Core', 'learn_press').'</span>' : '';?></h3>
                    <p class="add-on-actions">
                    <?php if ( learn_press_is_addon_enabled( $add_on['slug'] ) ) : ?>
                        <?php $url = $is_core ? wp_nonce_url( get_site_url() . '/wp-admin/admin.php?page=learn_press_add_ons&learn-press-disable-addon=' . $add_on['slug'] . '&tab=' . $tab, 'learn-press-disable-add-on' ) : admin_url() . 'plugins.php'; ?>
                        <input data-state="enabled" type="checkbox" class="lpr-fancy-checkbox" name="<?php echo $add_on['slug'];?>" checked="checked" data-url="<?php echo $url;?>" data-iscore="<?php echo $is_core ? 1 : 0;?>">
                    <?php else : ?>
                        <input data-state="disabled" type="checkbox" class="lpr-fancy-checkbox" name="<?php echo $add_on['slug'];?>" data-url="<?php echo wp_nonce_url( get_site_url() . '/wp-admin/admin.php?page=learn_press_add_ons&learn-press-enable-addon=' . $add_on['slug'] . '&tab=' . $tab, 'learn-press-enable-add-on' );?>"  data-iscore="<?php echo $is_core ? 1 : 0;?>">
                    <?php endif; ?>
                    <span class="add-on-state dashicons dashicons-yes"></span>
                    <?php if ( ! empty( $add_on['options']['settings-callback'] ) && is_callable( $add_on['options']['settings-callback'] ) ) : ?>
                       <a href="<?php echo admin_url( 'admin.php?page=learn_press_add_ons&add-on-settings=' . $add_on['slug'] ); ?>" class="add-on-settings"><?php _e( 'Settings', 'learn_press' );?></a>
                    <?php endif; ?>
                    </p>
                </div>
            </div>
        </li>
    <?php
    }
    echo '</ul>';
}

function learn_press_add_on_admin_script(){
    wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'learn_press_add_on_admin_script' );
return;
class LPR_Plugins_List_Table extends WP_List_Table {
    public function __construct( $args = array() ) {
        global $status, $page;

        parent::__construct( array(
            'plural' => 'plugins',
            'screen' => isset( $args['screen'] ) ? $args['screen'] : null,
        ) );

        $status = 'all';
        if ( isset( $_REQUEST['plugin_status'] ) && in_array( $_REQUEST['plugin_status'], array( 'active', 'inactive', 'recently_activated', 'upgrade', 'mustuse', 'dropins', 'search' ) ) )
            $status = $_REQUEST['plugin_status'];

        if ( isset($_REQUEST['s']) )
            $_SERVER['REQUEST_URI'] = add_query_arg('s', wp_unslash($_REQUEST['s']) );

        $page = $this->get_pagenum();
    }

    public function get_hidden_columns(){
        return array();
    }

    public function get_sortable_columns(){
        return array();
    }

    public function get_columns() {
        global $status;

        return array(
            'cb'          => !in_array( $status, array( 'mustuse', 'dropins' ) ) ? '<input type="checkbox" />' : '',
            'name'        => __( 'Plugin', 'learn_press'  ),
            'description' => __( 'Description', 'learn_press'  ),
        );
    }

    public function prepare_items(){
        global $status, $plugins, $totals, $page, $orderby, $order, $s;

        wp_reset_vars(array('orderby', 'order', 's'));

        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        /**
         * Filter the full array of plugins to list in the Plugins list table.
         *
         * @since 3.0.0
         *
         * @see get_plugins()
         *
         * @param array $plugins An array of plugins to display in the list table.
         */
        $plugins = array(
            'all' => learn_press_get_add_ons(),//apply_filters('all_plugins', get_plugins()),
            'search' => array(),
            'active' => array(),
            'inactive' => array(),
            'recently_activated' => array(),
            'upgrade' => array(),
            'mustuse' => array(),
            'dropins' => array()
        );
        $this->items = array();
        foreach ( $plugins[ $status ] as $plugin_file => $plugin_data ) {
            // Translate, Don't Apply Markup, Sanitize HTML
            $this->items[$plugin_file] = _get_plugin_data_markup_translate( $plugin_file, $plugin_data, false, true );
        }

        //$this->items = learn_press_get_add_ons();
        //$this->items = $plugins;// array_slice( $this->items, $start, $plugins_per_page );
    }

    public function column_default( $item, $column_name ){
        switch( $column_name ) {
            case 'cb':
            case 'name':
            case 'description':
                return print_r( $item, true ) ;
        }
    }

    protected function get_bulk_actions() {
        global $status;

        $actions = array();

        if ( 'active' != $status )
            $actions['activate-selected'] = $this->screen->in_admin( 'network' ) ? __( 'Network Activate', 'learn_press'  ) : __( 'Activate', 'learn_press'  );

        if ( 'inactive' != $status && 'recent' != $status )
            $actions['deactivate-selected'] = $this->screen->in_admin( 'network' ) ? __( 'Network Deactivate', 'learn_press'  ) : __( 'Deactivate', 'learn_press'  );

        if ( !is_multisite() || $this->screen->in_admin( 'network' ) ) {
            if ( current_user_can( 'update_plugins' ) )
                $actions['update-selected'] = __( 'Update', 'learn_press'  );
            if ( current_user_can( 'delete_plugins' ) && ( 'active' != $status ) )
                $actions['delete-selected'] = __( 'Delete', 'learn_press'  );
        }

        return $actions;
    }

    public function single_row( $item ){
        global $status, $page, $s, $totals;

        list( $plugin_file, $plugin_data ) = $item;
        $context = $status;
        $screen = $this->screen;

        $actions = array(
            'deactivate' => '',
            'activate' => '',
            'details' => '',
            'edit' => '',
            'delete' => '',
        );

        if ( $screen->in_admin( 'network' ) )
            $is_active = is_plugin_active_for_network( $plugin_file );
        else
            $is_active = is_plugin_active( $plugin_file );

        $class = $is_active ? 'active' : 'inactive';

        $description = '<p>' . ( $plugin_data['Description'] ? $plugin_data['Description'] : '&nbsp;' ) . '</p>';
        $plugin_name = $plugin_data['Name'];
        $id = sanitize_title( $plugin_name );
        $plugin_slug = ( isset( $plugin_data['slug'] ) ) ? $plugin_data['slug'] : '';

        printf( "<tr id='%s' class='%s' data-slug='%s'>",
            $id,
            $class,
            $plugin_slug
        );

        $prefix = $screen->in_admin( 'network' ) ? 'network_admin_' : '';

        list( $columns, $hidden ) = $this->get_column_info();



        if ( $is_active ) {
            $actions['deactivate'] = '<a href="' . wp_nonce_url('plugins.php?action=deactivate&amp;plugin=' . $plugin_file . '&amp;plugin_status=' . $context . '&amp;paged=' . $page . '&amp;s=' . $s, 'deactivate-plugin_' . $plugin_file) . '" title="' . esc_attr__('Disable this plugin', 'learn_press' ) . '">' . __('Disable', 'learn_press' ) . '</a>';
        } else {
            $actions['activate'] = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin_file . '&amp;plugin_status=' . $context . '&amp;paged=' . $page . '&amp;s=' . $s, 'activate-plugin_' . $plugin_file) . '" title="' . esc_attr__('Enable this plugin', 'learn_press' ) . '" class="edit">' . __('Enable', 'learn_press' ) . '</a>';
        }

        $actions = apply_filters( $prefix . 'plugin_action_links', array_filter( $actions ), $plugin_file, $plugin_data, $context );

        $actions = apply_filters( $prefix . "plugin_action_links_$plugin_file", $actions, $plugin_file, $plugin_data, $context );

        foreach ( $columns as $column_name => $column_display_name ) {
            $style = '';
            if (in_array($column_name, $hidden))
                $style = ' style="display:none;"';

            $checkbox_id =  "checkbox_" . md5($plugin_data['Name']);
            $checkbox = "<label class='screen-reader-text' for='" . $checkbox_id . "' >" . sprintf( __( 'Select %s', 'learn_press'  ), $plugin_data['Name'] ) . "</label>"
                . "<input type='checkbox' name='checked[]' value='" . esc_attr( $plugin_file ) . "' id='" . $checkbox_id . "' />";

            switch ($column_name) {
                case 'cb':
                    echo "<th scope='row' class='check-column'>$checkbox</th>";
                    break;
                case 'name':
                    echo "<td class='plugin-title'$style><strong>$plugin_name</strong>";
                    echo $this->row_actions($actions, true);
                    echo "</td>";
                    break;
                case 'description':
                    echo "<td class='column-description desc'$style>";
					echo "<div class='plugin-description'>$description</div>";
                    /*echo '<pre>';
                    print_r( $item);
                    echo '</pre>';*/
                    echo "</td>";
                    break;
            }
        }
    }

    public function display_rows() {
        global $status;

        if ( is_multisite() && ! $this->screen->in_admin( 'network' ) && in_array( $status, array( 'mustuse', 'dropins' ) ) )
            return;

        foreach ( $this->items as $plugin_file => $plugin_data )
            $this->single_row( array( $plugin_file, $plugin_data ) );
    }
}