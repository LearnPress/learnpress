<?php

defined( 'ABSPATH' ) || exit();

if ( !class_exists( 'LP_Statistic_Status' ) ) :

    class LP_Statistic_Status {

        /**
         * LearnPress statistic layout
         * 
         * @since 2.0
         */
        public static function render() {
            global $lp_order_statuses;
            $eduma_data = self::get_eduma_info( 14058034 );
//            $total_orders = learn_press_get_orders( array( 'post_status' => array_keys( $lp_order_statuses ) ) );
            $specific_statuses = array( 'lp-completed', 'lp-failed', 'lp-on-hold' );
            ?>
                <ul class="learnpress-statistic-status">
                    <li class="full-width">
                        <a href="#" class="total-raised">
                            <span>
                                <?php echo _learn_press_total_raised() ?>
                                <small><?php _e( 'Total Raised', 'learnpress' ); ?></small>
                            </span>
                        </a>
                    </li>
                    <?php foreach ( $specific_statuses as $status ) : $count = count( learn_press_get_orders( array( 'post_status' => $status ) ) ); ?>
                        <li>
                            <a href="<?php echo esc_url( admin_url( 'edit.php?post_status=' . LP_ORDER_CPT . '&post_type=' . $status ) ); ?>" class="<?php echo esc_attr( $status ) ?>">
                                <span><?php printf( translate_nooped_plural( _n_noop( '%d order', '%d orders' ), $count, 'learnpress' ), $count ) ?></span>
                                <small><?php printf( '%s', get_post_status_object( $status )->label ) ?></small>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <?php foreach ( $lp_order_statuses as $status => $args ) : if ( in_array( $status, $specific_statuses ) ) continue; $count = count( learn_press_get_orders( array( 'post_status' => $status ) ) ); ?>
                        <li class="halp-width">
                            <a href="<?php echo esc_url( admin_url( 'edit.php?post_status=' . LP_ORDER_CPT . '&post_type=' . $status ) ); ?>" class="<?php echo esc_attr( $status ) ?>">
                                <span><?php printf( translate_nooped_plural( _n_noop( '%d order', '%d orders' ), $count, 'learnpress' ), $count ) ?></span>
                                <small><?php printf( '%s', get_post_status_object( $status )->label ) ?></small>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li class="full-width featured-theme">
                        <span><?php _e( 'Featured: ', 'learnpress' ); ?></span>
                        <a href="<?php echo esc_url( $eduma_data['item']['url'] ) ?>">
                            <?php echo esc_html( $eduma_data['item']['item'] ) ?>
                        </a>-
                        <span class="price"><?php printf( '%s%s', '$', $eduma_data['item']['cost'] ) ?></span>
                        <span><?php _e( 'Created by: ', 'learnpress' ) ?></span>
                        <a href="https://thimpress.com/" class="author"><?php echo esc_html( $eduma_data['item']['user'] ); ?></a>
                    </li>
                </ul>
            <?php
        }

        /**
         * @param String $item_id - The ID of an Envato Marketplace item
         * @returns Array - The item informations
         */
        public static function get_eduma_info( $item_id ) {

            /* Data cache timeout in seconds - It send a new request each hour instead of each page refresh */
            $CACHE_EXPIRATION = 3600;

            /* Set the transient ID for caching */
            $transient_id = 'learnpress_envato_item_data';

            /* Get the cached data */
            $cached_item = get_transient( $transient_id );

            /* Check if the function has to send a new API request */
            if ( !$cached_item || ( $cached_item->item_id != $item_id ) ) {

                /* Set the API URL, %s will be replaced with the item ID  */
                $api_url = "http://marketplace.envato.com/api/edge/item:%s.json";

                /* Fetch data using the WordPress function wp_remote_get() */
                $response = wp_safe_remote_get( sprintf( $api_url, $item_id ) );

                /* Check for errors, if there are some errors return false */
                if ( is_wp_error( $response ) or ( wp_remote_retrieve_response_code( $response ) != 200 ) ) {
                    return false;
                }

                /* Transform the JSON string into a PHP array */
                $item_data = json_decode( wp_remote_retrieve_body( $response ), true );

                /* Check for incorrect data */
                if ( !is_array( $item_data ) ) {
                    return false;
                }
                /* Prepare data for caching */
                $data_to_cache = new stdClass();
                $data_to_cache->item_id = $item_id;
                $data_to_cache->item_info = $item_data;

                /* Set the transient - cache item data */
                set_transient( $transient_id, $data_to_cache, $CACHE_EXPIRATION );

                /* Return item info array */
                return $item_data;
            }

            /* If the item is already cached return the cached info */
            return $cached_item->item_info;
        }

    }

endif;