<?php
function learn_press_get_course_order( $course_id, $user_id = null ){
    if( ! $user_id ){
        $user_id = get_current_user_id();
    }

    global $wpdb;
    $order = false;
    $query = $wpdb->prepare("
        SELECT ID, pm2.meta_value
        FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
        WHERE p.post_type = %s AND pm.meta_key = %s AND pm.meta_value = %d
    ", '_learn_press_order_items', 'lpr_order', '_learn_press_customer_id', $user_id );
    if( $orders = $wpdb->get_results( $query ) ){
        foreach( $orders as $order_data ){
            $order_id = $order_data->ID;
            $order_data = maybe_unserialize( $order_data->meta_value );
            if($order_data && ! empty( $order_data->products ) ){
                if( isset( $order_data->products[ $course_id ] ) ){
                    $order = $order_id;
                    // a user only can take a course one time
                    // so it should be existing in one and only one order
                    break;
                }
            }
        }
    }
    return $order;
}

function learn_press_get_order_status_label( $order_id = 0 ){
    $statuses = learn_press_get_order_statuses();
    if( is_numeric( $order_id ) ) {
        $status = get_post_status( $order_id );
    }else{
        $status = $order_id;
    }
    return ! empty( $statuses[ $status ] ) ? $statuses[ $status ] : __( 'Pending', 'learn_press' );
}