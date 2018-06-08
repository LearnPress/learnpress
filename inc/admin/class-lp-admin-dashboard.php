<?php
if ( ! class_exists( 'LP_Admin_Dashboard' ) ) {
	/**
	 * Class LP_Admin_Dashboard
	 *
	 * Displays widgets in admin.
	 */
	class LP_Admin_Dashboard {

		/**
		 * LP_Admin_Dashboard constructor.
		 */
		public function __construct() {
			add_action( 'wp_dashboard_setup', array( $this, 'register' ) );
		}

		public function register() {
			if ( current_user_can( 'manage_options' ) || current_user_can( 'publish_lp_orders' ) ) {
				wp_add_dashboard_widget( 'learn_press_dashboard_order_statuses', __( 'LearnPress order status', 'learnpress' ), array(
					$this,
					'order_statuses'
				) );
			}
			wp_add_dashboard_widget( 'learn_press_dashboard_plugin_status', __( 'LearnPress status', 'learnpress' ), array(
				$this,
				'plugin_status'
			) );
		}

		/**
		 * Order status widget
		 */
		public function order_statuses() {
			?>
            <div id="lp-dashboard-order-status">
				<?php esc_html_e( 'Loading...', 'learnpress' ); ?>
                <script>
                    jQuery(function ($) {
                        setTimeout(function () {
                            $(document).trigger('learn-press/load-dashboard-order-status');
                        }, 300)
                    })
                </script>
            </div>
			<?php
		}

		/**
		 * Get total value of LP orders has completed.
		 *
		 * @return int|string
		 */
		public function get_order_total_raised() {
			$orders = learn_press_get_orders( array( 'post_status' => 'lp-completed', 'fields' => 'ids' ) );
			$total  = 0;
			if ( $orders ) {
				foreach ( $orders as $order_id ) {
					$order = learn_press_get_order( $order_id );
					$total = $total + floatval( $order->get_total() );
				}
			}

			return learn_press_format_price( $total, true );
		}

		/**
		 * @param String $item_id - The ID of an Envato Marketplace item
		 *
		 * @returns mixed
		 */
		public function get_theme_info( $item_id ) {

			/* Data cache timeout in seconds - It send a new request each hour instead of each page refresh */
			$CACHE_EXPIRATION = 3600;

			/* Set the transient ID for caching */
			$transient_id = sprintf( 'lp_envato_item_data_%s', $item_id );

			/* Get the cached data */
			$cached_item = get_transient( $transient_id );

			/* Check if the function has to send a new API request */
			if ( ! $cached_item || ( $cached_item->item_id != $item_id ) ) {

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
				if ( ! is_array( $item_data ) ) {
					return false;
				}

				/* Prepare data for caching */
				$cached_item            = new stdClass();
				$cached_item->item_id   = $item_id;
				$cached_item->item_info = $item_data;

				/* Set the transient - cache item data */
				set_transient( $transient_id, $cached_item, $CACHE_EXPIRATION );

			}

			/* If the item is already cached return the cached info */

			return $cached_item->item_info;
		}

		/**
		 * Get data from wordpress.org
		 *
		 * @since 2.0
		 */
		public function get_data() {

			if ( ! function_exists( 'plugins_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}

			if ( false === ( $api = get_transient( 'lp_plugin_status' ) ) ) {
				// get plugin information from wordpress.org
				$api = plugins_api( 'plugin_information', array(
					'slug'   => 'learnpress',
					'fields' => array(
						'active_installs'   => true,
						'short_description' => true,
						'description'       => true,
						'ratings'           => true
					)
				) );
				set_transient( 'lp_plugin_status', $api, 12 * HOUR_IN_SECONDS );
			}

			return $api;
		}

		/**
		 * Plugin status widget
		 */
		public function plugin_status() {
			?>
            <div id="lp-dashboard-plugin-status">
				<?php esc_html_e( 'Loading...', 'learnpress' ); ?>
                <script>
                    jQuery(function ($) {
                        setTimeout(function () {
                            $(document).trigger('learn-press/load-dashboard-plugin-status');
                        }, 300)
                    })
                </script>
            </div>
			<?php
		}
	}
}
new LP_Admin_Dashboard();