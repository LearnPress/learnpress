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

            <ul class="lp-order-statuses"> </ul>
			<?php
		}

		/**
		 * Get total value of LP orders has completed.
		 *
		 * @return int|string
		 */
		private function _get_order_total_raised() {
			// Fix learnpress order status in dashboard
			$orders = learn_press_get_orders( array( 'post_status' => 'lp-completed','post_parent'=>'0','posts_per_page' => -1  ) );
			$total  = 0;
			if ( $orders ) {
				foreach ( $orders as $order ) {
					$order = learn_press_get_order( $order->ID );
					$total = $total + floatval( $order->order_total );
				}
			}

			return learn_press_format_price( $total, true );
		}

		/**
		 * @param String $item_id - The ID of an Envato Marketplace item
		 *
		 * @returns mixed
		 */
		private function _get_theme_info( $item_id ) {

			$alls = LP_Plugins_Helper::get_related_themes();

			if ( ! $alls ) {
				return false;
			}

			foreach ( $alls as $k => $types ) {
				if ( isset( $types[ $item_id ] ) ) {
					return $types[ $item_id ];
				}
			}

			return false;
		}

		/**
		 * Get data from wordpress.org
		 *
		 * @since 2.0
		 */
		private function _get_data() {

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
						'ratings'           => true,
						// Need pass this fields because WP may be changed it default from true to false
						'downloaded'        => true
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
			$plugin_data = $this->_get_data();
			if ( ! $plugin_data || is_wp_error( $plugin_data ) ) {
				learn_press_admin_view( 'dashboard/plugin-status/html-no-data' );
			} else {
				learn_press_admin_view( 'dashboard/plugin-status/html-results', array( 'plugin_data' => $plugin_data ) );
			}
		}
	}
}
new LP_Admin_Dashboard();