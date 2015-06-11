<?php

/**
 * FileName: Learn Press Order
 * Author: Andy(tu@thimpress.com)
 * Modified: TuNN
 * Created: Fed 2015
 * AuthorURI: thimpress.com
 * Copyright 2007-2015 thimpress.com. All rights reserved.
 */
if( ! class_exists( 'LPR_Order_Post_Type' ) ) {
    // class LPR_Order_Post_Type

    final class LPR_Order_Post_Type
    {
        private static $loaded = false;
        function __construct() {
            if( self::$loaded ) return;
            add_action('init', array($this, 'register_post_type'));
            /*Add Coulumn*/
            add_filter( 'manage_edit-lpr_order_columns', array( $this, 'lpr_order_columns' ) );
            add_action( 'manage_lpr_order_posts_custom_column', array( $this, 'render_learn_press_order_columns' ) );
            add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
            add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 10, 2 );
            add_filter( 'page_row_actions', array( $this, 'post_row_actions' ), 10, 2 );
            add_filter( 'manage_edit-lpr_order_sortable_columns', array( $this, 'sortable_columns' ) );

            add_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );
            add_filter( 'posts_join_paged', array( $this, 'posts_join_paged' ) );
            add_filter( 'posts_fields', array( $this, 'posts_fields' ) );
            add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ) );

            add_action( 'wp_ajax_update_order_status', array($this, 'update_status'));
            add_action( 'admin_head', array($this, 'admin_head'));

            add_action( 'wp_trash_post', array( $this, 'preparing_to_trash_order' ) );

            add_action( 'before_delete_post', array( $this, 'delete_transaction' ) );


            self::$loaded = true;
        }

        function delete_transaction( $post_id ){
            $order_data = get_post_meta( $post_id, '_learn_press_order_items', true );

            // find all courses stored in current order and remove it from user's courses
            if( $order_data && ! empty( $order_data->products ) ) {
                $products = $order_data->products;

                // loop through the list and find the course need to remove
                if( is_array( $products ) ) foreach( $products as $course_id => $data ) {
                    $user_order = get_post_meta($post_id, '_learn_press_customer_id', true);

                    // all courses user has enrolled
                    $user_courses = get_user_meta($user_order, '_lpr_user_course', true);

                    // find the position of the course in the array and remove it if find out
                    if( false !== ( $pos = array_search( $course_id, $user_courses ) ) ){
                        unset( $user_courses[$pos] );

                        // update the meta if we have the courses in the list else delete
                        if( sizeof( $user_courses ) ) {
                            update_user_meta( $user_order, '_lpr_user_course', $user_courses );
                        }else{
                            delete_user_meta( $user_order, '_lpr_user_course' );
                            break;
                        }
                    }
                }
            }
        }

        function posts_where_paged( $where ){
            global $wpdb;
            global $post_type;
            if( 'lpr_order' != $post_type ) return $where;
            $where .= " AND (
                {$wpdb->postmeta}.meta_key='_learn_press_customer_id'
            )";
            return $where;
        }

        function posts_fields( $fields ){
            global $post_type;
            if( 'lpr_order' != $post_type ) return $fields;
            $fields .= ", uu.ID as user_ID, uu.display_name as user_display_name";
            return $fields;
        }
        function posts_orderby( $orderby ){
            global $post_type;
            if( 'lpr_order' != $post_type ) return $orderby;
            $args = wp_parse_args(
                $_REQUEST,
                array(
                    'orderby'   => '',
                    'order'     => ''
                )
            );
            if( $args['orderby'] == 'student' ) {
                $orderby = "user_display_name ";
                if( $args['order'] ) $orderby .= $args['order'];
            }
            return $orderby;
        }

        function posts_join_paged( $join ){
            global $post_type;
            if( 'lpr_order' != $post_type ) return $join;
            global $wpdb;
            $join .= " INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id";
            $join .= " INNER JOIN {$wpdb->users} uu ON uu.ID = {$wpdb->postmeta}.meta_value";
            return $join;
        }

        /**
         * Make our custom columns can be sortable
         *
         * @param $columns
         * @return mixed
         */
        function sortable_columns( $columns ) {
            $columns['order_student'] = 'student';
            return $columns;
        }

        function admin_head()
        {

            global $post, $wp_query;

            if ('lpr_order' != get_post_type()) return;
            ob_start();
            ?>
            <script>
                $('#update-order-status').click(function(){
                    var $button = $(this).attr('disabled', 'disabled').html('<?php _e( 'Processing...', 'learn_press' );?>');
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data:{
                            action: 'update_order_status',
                            order_id: '<?php echo $post->ID;?>',
                            status: $('select[name="learn_press_order_status"]').val()
                        },
                        success: function (res) {
                            if (res.status) {
                                $('.order-data-status')
                                    .removeClass('pending completed')
                                    .html(res.status)
                                    .addClass(res.class);
                            }
                            $button.removeAttr('disabled').html('<?php _e( 'Apply', 'learn_press' );?>');
                        },
                        error: function(){
                            $button.removeAttr('disabled').html('<?php _e( 'Apply', 'learn_press' );?>');
                        }
                    });
                })
            </script>
            <?php
            $js = preg_replace('!</?script>!', '', ob_get_clean());
            learn_press_enqueue_script($js);
        }

        function update_status()
        {
            $order_id = !empty($_REQUEST['order_id']) ? $_REQUEST['order_id'] : 0;
            $status = !empty($_REQUEST['status']) ? $_REQUEST['status'] : 'Pending';
            learn_press_update_order_status($order_id, $status);

            wp_send_json(
                array(
                    'status' => $status,
                    'class' => sanitize_title($status)
                )
            );
        }

        function post_row_actions($actions, $post)
        {

            if ('lpr_order' == $post->post_type) {

                $_actions = array();

                if (!empty($actions['edit'])) {
                    $_actions['edit'] = '<a href="' . get_edit_post_link($post->ID, true) . '" title="' . esc_attr(__('View the transaction details', 'learn_press')) . '">' . __('Details', 'learn_press') . '</a>';
                }

                if (!empty($actions['trash'])) $_actions['trash'] = $actions['trash'];
                $actions = $_actions;
            }
            return $actions;
        }

        /**
         * re-order the orders by newest
         * @param $wp_query
         * @return mixed
         */
        function pre_get_posts($wp_query)
        {
            if (is_admin()) {
                if ( !empty( $wp_query->query['post_type'] ) && ( $wp_query->query['post_type'] == 'lpr_order' ) ) {
                    $wp_query->set('orderby', 'date');
                    $wp_query->set('order', 'desc');
                }
            }
            return $wp_query;
        }

        /**
         *
         */
        function lpr_order_columns($existing)
        {

            // Remove Checkbox - adding it back below
            if (isset($existing['cb'])) {
                $check = $existing['cb'];
                unset($existing['cb']);
            }

            // Remove Title - adding it back below
            if (isset($existing['title']))
                unset($existing['title']);

            // Remove Format
            if (isset($existing['format']))
                unset($existing['format']);

            // Remove Author
            if (isset($existing['author']))
                unset($existing['author']);

            // Remove Comments
            if (isset($existing['comments']))
                unset($existing['comments']);

            // Remove Date
            if (isset($existing['date']))
                unset($existing['date']);

            // Remove Builder
            if (isset($existing['builder_layout']))
                unset($existing['builder_layout']);

            add_filter('the_title', array($this, 'replace_transaction_title_with_order_number'), 5, 2);

            $columns['cb'] = '<input type="checkbox" />';
            $columns['title'] = __('Order', 'learn_press');
            $columns['order_student'] = __('Student', 'learn_press');
            $columns['order_items'] = __('Course', 'learn_press');
            $columns['order_date'] = __('Date', 'learn_press');
            $columns['order_total'] = __('Total', 'learn_press');
            $columns['order_status'] = '<span class="status_head tips" data-tip="' . esc_attr__('Status', 'learn_press') . '">' . esc_attr__('Status', 'learn_press') . '</span>';

            $columns = array_merge($columns, $existing);

            return $columns;
        }

        function replace_transaction_title_with_order_number($title, $post_id)
        {
            global $post;
            if ('lpr_order' == get_post_type($post_id))
                $title = learn_press_transaction_order_number($post_id);
            return $title;
        }

        /**
         * Render column data
         *
         */
        function render_learn_press_order_columns($column)
        {
            global $post;

            $the_order = learn_press_get_order($post->ID);

            $order_items = learn_press_get_order_items($post->ID);
            $status = get_post_meta($post->ID, '_learn_press_transaction_status', true);

            switch ($column) {
                case 'order_student':
                    //$user = new LPR_User( $post->uID );

                    ?><a href="user-edit.php?user_id=<?php echo $post->user_ID ?>"><?php echo $post->user_display_name ?></a><?php
                    break;
                case 'order_status' :
                    //echo learn_press_get_status_text( $the_order->meta['zzlpr_status'] );
                    //				printf( '<mark class="%s tips" data-tip="%s">%s</mark>', sanitize_title( $the_order->get_status() ), wc_get_order_status_name( $the_order->get_status() ), wc_get_order_status_name( $the_order->get_status() ) );
                    echo $status;
                    break;
                case 'order_date' :

                    $t_time = get_the_time( __( 'Y/m/d g:i:s a' ) );
                    $m_time = $post->post_date;
                    $time = get_post_time( 'G', true, $post );

                    $time_diff = time() - $time;

                    if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS )
                        $h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
                    else
                        $h_time = mysql2date( __( 'Y/m/d' ), $m_time );

                    echo '<abbr title="' . esc_attr($t_time) . '">' . esc_html(apply_filters('learn_press_order_column_time', $h_time, $the_order)) . '</abbr>';

                    break;
                case 'order_items' :
                    if ($products = learn_press_get_transition_products($post->ID)):
                        $links = array();
                        foreach ($products as $pro) {
                            $links[] = '<a href="' . get_the_permalink($pro->ID) . '">' . get_the_title($pro->ID) . '</a>';
                        }
                        echo join("<br />", $links);
                    else:
                        _e( "Course has been removed");
                    endif;

                    break;
                case 'order_total' :
                    echo learn_press_format_price(empty($order_items->total) ? 0 : $order_items->total, learn_press_get_currency_symbol($order_items->currency));
                    break;
                case 'order_title' :

                    $order_number = sprintf("%'.010d", $the_order->ID);
                    ?>
                    <div class="tips">
                        <a href="post.php?post=<?php echo $the_order->ID ?>&action=edit"><strong><?php echo learn_press_transaction_order_number($order_number); ?></strong></a>

                    </div>
                    <?php break;
            }
        }

        /**
         * Register post type
         */
        function register_post_type()
        {
            register_post_type( LPR_ORDER_CPT,
                array(
                    'labels' => array(
                        'name' => __('Orders', 'learn_press'),
                        'menu_name' => __('Orders', 'learn_press'),
                        'singular_name' => __('Order', 'learn_press'),
                        'add_new_item' => __('Add New Order', 'learn_press'),
                        'edit_item' => __('Order Details', 'learn_press'),
                        'all_items' => __('Orders', 'learn_press'),
                    ),

                    'public' => false,
                    'show_ui' => true,
                    'show_in_nav_menus' => false,
                    'show_in_admin_bar' => false,
                    'publicly_queryable' => true,
                    'show_in_menu' => 'learn_press',
                    'supports' => array(
                        //'title',
                        //'editor',
                        //'author',
                        //'revisions',
                    ),

                    'capabilities' => array(
                        'create_posts' => 'do_not_allow'
                    ),
                    'map_meta_cap' => true,
                    'capability_type' => 'lpr_order',
                    'hierarchical' => true,
                    'rewrite' => array('slug' => 'lpr_order', 'hierarchical' => true, 'with_front' => true)
                )
            );
            add_action('add_meta_boxes', array($this, 'register_metabox'));
            register_post_status( 'lpr-draft', array(
                'label'                     => _x( 'Draft Order', 'Order status', 'learn_press' ),
                'public'                    => false,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Draf order <span class="count">(%s)</span>', 'Draf order <span class="count">(%s)</span>', 'learn_press' )
            ) );
        }

        function register_metabox()
        {
            // Remove Publish metabox
            remove_meta_box('submitdiv', 'lpr_order', 'side');

            // Remove Slug metabox
            remove_meta_box('slugdiv', 'lpr_order', 'normal');

            // Remove screen options tab
            add_filter('screen_options_show_screen', '__return_false');

            add_meta_box('order_details', __('Order Details'), array($this, 'order_details'), 'lpr_order', 'normal', 'core');
        }

        function order_details($post)
        {
            $user = learn_press_get_user(get_post_meta($post->ID, '_learn_press_customer_id', true));
            $order_items = learn_press_get_order_items($post->ID);
            $status = strtolower( get_post_meta( $post->ID, '_learn_press_transaction_status', true) );
            if ($status && !in_array($status, array('completed', 'pending'))) {
                $status = 'Pending';
            }
            $currency_symbol = learn_press_get_currency_symbol($order_items->currency);
            ?>
            <div class="order-details">
                <div class="order-data">
                    <div class="order-data-number"><?php echo learn_press_transaction_order_number($post->ID); ?></div>
                    <div
                        class="order-data-date"><?php echo learn_press_transaction_order_date($post->post_date); ?></div>
                    <div class="order-data-status <?php echo sanitize_title($status); ?>"><?php echo $status; ?></div>
                    <div
                        class="order-data-payment-method"><?php echo learn_press_payment_method_from_slug($post->ID); ?></div>
                </div>
                <div class="order-user-data clearfix">
                    <div class="order-user-avatar">
                        <?php echo get_avatar($user->ID, 120); ?>
                    </div>
                    <div class="order-user-meta">
                        <h2 class="user-display-name">
                            <?php esc_attr_e(empty($user->display_name) ? 'Unknow' : $user->display_name); ?>
                        </h2>

                        <div class="user-email">
                            <?php esc_attr_e(empty($user->user_email) ? 'Unknow' : $user->user_email); ?>
                        </div>
                        <div class="user-ip-address">
                            <?php esc_attr_e(get_post_meta($post->ID, '_learn_press_customer_ip', true)); ?>
                        </div>
                    </div>
                </div>
                <div class="order-products">
                    <table>
                        <thead>
                        <tr>
                            <th colspan="2"><?php _e('Courses', 'learn_press'); ?></th>
                            <th class="align-right"><?php _e('Amount', 'learn_press'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($products = learn_press_get_transition_products($post->ID)): foreach ($products as $pro) { ?>
                            <tr>
                                <td colspan="2">
                                    <a href="<?php the_permalink($pro->ID); ?>"><?php echo get_the_title($pro->ID); ?></a>
                                </td>
                                <td class="align-right"><?php echo $pro->amount ? learn_press_format_price($pro->amount, $currency_symbol) : __('Free!', 'learn_press'); ?></td>
                            </tr>
                        <?php } endif; ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td></td>
                            <td width="300" class="align-right"><?php _e('Sub Total', 'learn_press'); ?></td>
                            <td width="100"
                                class="align-right"><?php echo learn_press_format_price($order_items->sub_total, $currency_symbol); ?></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="align-right"><?php _e('Total', 'learn_press'); ?></td>
                            <td class="align-right total"><?php echo learn_press_format_price($order_items->total, $currency_symbol); ?></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="align-right" colspan="2">
                                <?php _e('Status', 'learn_press'); ?>
                                <select name="learn_press_order_status">
                                    <option value="" <?php selected($status == '' ? 1 : 0, 1); ?>><?php _e( 'Unpublished', 'learn_press' );?></option>
                                    <option
                                        value="Pending" <?php selected($status && ( $status != 'completed' ) ? 1 : 0, 1); ?>><?php _e('Pending', 'learn_press'); ?></option>
                                    <option
                                        value="Completed" <?php selected($status == 'completed' ? 1 : 0, 1); ?>><?php _e('Completed', 'learn_press'); ?></option>
                                </select>
                                <button id="update-order-status" class="button button-primary" type="button"><?php _e( 'Apply', 'learn_press' );?></button>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php
        }

        function preparing_to_trash_order( $post_id ){
            if( 'lpr_order' != get_post_type( $post_id ) ) return;

        }
    }
} // end LPR_Order_Post_Type
new LPR_Order_Post_Type();
// deprecated new Learn_Press_Order();
